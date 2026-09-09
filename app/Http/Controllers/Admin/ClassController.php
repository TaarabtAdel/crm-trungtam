<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\ClassSession;
use App\Models\CourseClass;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Services\ClassSessionJournalService;
use App\Services\ClassTimetableGenerator;
use App\Services\ScheduleConflictService;
use App\Support\CurrentBranch;
use App\Support\Notifier;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ClassController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $status = $request->get('status');
        $subjectId = $request->get('subject_id');

        $classes = CourseClass::with(['branch', 'subject', 'teacher'])
            ->withCount(['sessions', 'students'])
            ->tap(fn ($query) => CurrentBranch::apply($query))
            ->when($request->user()?->isRestrictedTeacher(), function ($query) use ($request) {
                $teacher = $request->user()->linkedTeacher();
                if (! $teacher) {
                    $query->whereRaw('1 = 0');

                    return;
                }
                $query->where(function ($inner) use ($teacher) {
                    $inner->where('teacher_id', $teacher->id)
                        ->orWhereHas('sessions', fn ($s) => $s->where('teacher_id', $teacher->id));
                });
            })
            ->when($q, function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('code', 'like', "%{$q}%")
                        ->orWhere('room', 'like', "%{$q}%");
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($subjectId, fn ($query) => $query->where('subject_id', $subjectId))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $subjects = CurrentBranch::apply(Subject::query())->where('status', 'active')->orderBy('name')->get();
        $teachers = CurrentBranch::apply(Teacher::query())->where('status', 'active')->orderBy('name')->get();

        return view('admin.training.classes', compact(
            'classes', 'branches', 'subjects', 'teachers', 'q', 'status', 'subjectId'
        ));
    }

    public function show(Request $request, CourseClass $class)
    {
        $this->authorizeTeacherClassAccess($request->user(), $class);

        $tab = $request->get('tab', 'info');
        if (! in_array($tab, ['info', 'students', 'timetable', 'tuition', 'journal'], true)) {
            $tab = 'info';
        }

        // GV thường: ưu tiên tab nhật ký, không vào học phí
        if ($request->user()?->isRestrictedTeacher() && $tab === 'tuition') {
            $tab = 'journal';
        }

        $class->load(['branch', 'subject', 'teacher']);
        $class->loadCount(['students', 'sessions']);

        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $subjects = CurrentBranch::apply(Subject::query())->where('status', 'active')->orderBy('name')->get();
        $teachers = CurrentBranch::apply(Teacher::query())->where('status', 'active')->orderBy('name')->get();
        $sessionTeachers = Teacher::query()->where('status', 'active')->orderBy('name')->get();
        $days = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];

        $availableStudents = collect();
        $classStudents = collect();
        $sessions = collect();
        $cancelledSessions = collect();
        $month = '';
        $availableMonths = [];
        $attendancesByDate = collect();
        $defaultFrom = now()->startOfMonth()->toDateString();
        $defaultTo = now()->addMonths(2)->endOfMonth()->toDateString();
        $invoices = collect();
        $billingMonth = now()->format('Y-m');
        $suggestion = null;
        $billingPreviews = [];
        $defaultFeeType = 'monthly';
        $selectableSessions = collect();
        $unitFee = 0;
        $journals = collect();

        if ($tab === 'students') {
            $classStudents = $class->students()->orderBy('name')->get();
        }

        if ($tab === 'timetable' || $tab === 'journal') {
            $month = (string) $request->get('month', '');
            if ($month !== '' && ! preg_match('/^\d{4}-\d{2}$/', $month)) {
                $month = '';
            }

            $availableMonths = $class->sessions()
                ->selectRaw("DATE_FORMAT(session_date, '%Y-%m') as ym")
                ->groupBy('ym')
                ->orderByDesc('ym')
                ->pluck('ym')
                ->filter()
                ->values()
                ->all();
        }

        if ($tab === 'timetable') {
            $sessionsQuery = $class->sessions()->with(['teacher', 'journal', 'makeupOf']);
            if ($month !== '') {
                [$year, $monthNum] = array_map('intval', explode('-', $month));
                $sessionsQuery
                    ->whereYear('session_date', $year)
                    ->whereMonth('session_date', $monthNum);
            }
            $sessions = $sessionsQuery
                ->orderBy('session_date')
                ->orderBy('start_time')
                ->get();

            $cancelledSessions = $class->sessions()
                ->where('status', 'cancelled')
                ->orderByDesc('session_date')
                ->limit(50)
                ->get();

            $classStudents = $class->students()->orderBy('name')->get();
            $sessionDates = $sessions->map(fn ($s) => $s->session_date->format('Y-m-d'))->unique()->values()->all();
            if ($sessionDates !== []) {
                $attendancesByDate = Attendance::query()
                    ->where('class_id', $class->id)
                    ->whereIn('session_date', $sessionDates)
                    ->get()
                    ->groupBy(fn (Attendance $a) => $a->session_date->format('Y-m-d'))
                    ->map(fn ($rows) => $rows->keyBy('student_id'));
            }

            $defaultFrom = optional($class->start_date)->format('Y-m-d') ?: now()->startOfMonth()->toDateString();
            $defaultTo = optional($class->end_date)->format('Y-m-d') ?: now()->addMonths(2)->endOfMonth()->toDateString();
        }

        if ($tab === 'journal') {
            $journalService = app(ClassSessionJournalService::class);
            $completedQuery = $class->sessions()
                ->with(['teacher', 'journal.filledByUser'])
                ->where('status', 'completed');
            if ($request->user()?->isRestrictedTeacher()) {
                $linked = $request->user()->linkedTeacher();
                if ($linked) {
                    $completedQuery->where('teacher_id', $linked->id);
                } else {
                    $completedQuery->whereRaw('1 = 0');
                }
            }
            if ($month !== '') {
                [$year, $monthNum] = array_map('intval', explode('-', $month));
                $completedQuery
                    ->whereYear('session_date', $year)
                    ->whereMonth('session_date', $monthNum);
            }
            $sessions = $completedQuery
                ->orderByDesc('session_date')
                ->orderByDesc('start_time')
                ->get();

            foreach ($sessions as $session) {
                if (! $session->journal) {
                    $journalService->ensureForSession($session);
                    $session->load('journal');
                }
            }

            $journals = $sessions->pluck('journal')->filter()->values();
        }

        if ($tab === 'tuition') {
            $billingMonth = $request->get('billing_month', now()->format('Y-m'));
            $classStudents = $class->students()->orderBy('name')->get();
            $invoices = Invoice::with('student')
                ->where('class_id', $class->id)
                ->latest()
                ->paginate(20)
                ->withQueryString();
            $defaultFeeType = $class->isPerSessionFee() ? 'per_session' : 'monthly';
            $billingPreviews = [
                'monthly' => $class->suggestInvoiceAmount($billingMonth, 'monthly'),
                'per_session' => $class->suggestInvoiceAmount($billingMonth, 'per_session'),
                'course' => $class->suggestInvoiceAmount($billingMonth, 'course'),
            ];
            $suggestion = $billingPreviews[$defaultFeeType];
            $selectableSessions = $class->sessionsInMonth($billingMonth);
            $unitFee = (float) $class->tuition_fee;
        }

        if ($tab === 'info') {
            $class->loadCount(['invoices']);
        }

        return view('admin.training.class_show', compact(
            'class', 'tab', 'branches', 'subjects', 'teachers', 'sessionTeachers', 'days',
            'availableStudents', 'classStudents',
            'sessions', 'cancelledSessions', 'month', 'availableMonths', 'attendancesByDate', 'defaultFrom', 'defaultTo',
            'invoices', 'billingMonth', 'suggestion', 'billingPreviews', 'defaultFeeType',
            'selectableSessions', 'unitFee', 'journals'
        ));
    }

    public function store(Request $request)
    {
        CourseClass::create($this->validated($request));

        return back()->with('success', 'Đã thêm lớp học.');
    }

    public function update(Request $request, CourseClass $class)
    {
        $class->update($this->validated($request));

        if ($request->boolean('from_detail')) {
            return redirect()
                ->route('admin.classes.show', ['class' => $class, 'tab' => 'info'])
                ->with('success', 'Đã cập nhật thông tin lớp.');
        }

        return back()->with('success', 'Đã cập nhật lớp học.');
    }

    public function destroy(CourseClass $class)
    {
        $class->delete();

        return redirect()->route('admin.classes.index')->with('success', 'Đã xóa lớp học.');
    }

    public function timetable(Request $request, CourseClass $class)
    {
        return redirect()->route('admin.classes.show', [
            'class' => $class,
            'tab' => 'timetable',
            'month' => $request->get('month'),
        ]);
    }

    public function exportTimetablePdf(Request $request, CourseClass $class)
    {
        $class->load(['branch', 'subject', 'teacher']);

        $month = (string) $request->get('month', '');
        if ($month !== '' && ! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = '';
        }

        $sessionsQuery = $class->sessions()->with(['teacher', 'makeupOf']);
        if ($month !== '') {
            [$year, $monthNum] = array_map('intval', explode('-', $month));
            $sessionsQuery
                ->whereYear('session_date', $year)
                ->whereMonth('session_date', $monthNum);
        }
        $sessions = $sessionsQuery
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get();

        $calendarWeeks = [];
        if ($month !== '') {
            $cursor = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->startOfWeek(Carbon::MONDAY);
            $end = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->endOfWeek(Carbon::SUNDAY);
            $byDate = $sessions->groupBy(fn ($s) => $s->session_date->format('Y-m-d'));

            while ($cursor->lte($end)) {
                $week = [];
                for ($i = 0; $i < 7; $i++) {
                    $key = $cursor->format('Y-m-d');
                    $week[] = [
                        'date' => $cursor->copy(),
                        'in_month' => $cursor->format('Y-m') === $month,
                        'sessions' => $byDate->get($key, collect()),
                    ];
                    $cursor->addDay();
                }
                $calendarWeeks[] = $week;
            }
        }

        $filename = 'TKB-'.($class->code ?: 'lop-'.$class->id);
        if ($month !== '') {
            $filename .= '-'.$month;
        }
        $filename .= '.pdf';

        $pdf = Pdf::loadView('pdf.timetable', compact(
            'class', 'sessions', 'month', 'calendarWeeks'
        ))->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }

    public function generateTimetable(Request $request, CourseClass $class, ClassTimetableGenerator $generator)
    {
        $data = $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'replace_scheduled' => 'nullable|boolean',
        ]);

        try {
            $result = $generator->generate(
                $class,
                $data['from'],
                $data['to'],
                $request->boolean('replace_scheduled'),
            );
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['from' => $e->getMessage()]);
        }

        $msg = "Đã tạo {$result['created']} buổi";
        if ($result['deleted'] > 0) {
            $msg .= ", xóa {$result['deleted']} buổi scheduled cũ";
        }
        if ($result['skipped'] > 0) {
            $msg .= ", giữ/bỏ qua {$result['skipped']} buổi";
        }
        $msg .= '.';

        return redirect()
            ->route('admin.classes.show', [
                'class' => $class,
                'tab' => 'timetable',
            ])
            ->with('success', $msg);
    }

    public function storeSession(Request $request, CourseClass $class, ClassSessionJournalService $journals, ScheduleConflictService $conflicts)
    {
        $data = $request->validate([
            'session_date' => 'required|date',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'teacher_id' => 'required|exists:teachers,id',
            'status' => 'nullable|in:scheduled,completed,cancelled',
            'notes' => 'nullable|string',
            'makeup_of_session_id' => 'nullable|exists:class_sessions,id',
            'force_conflict' => 'nullable|boolean',
        ]);

        $exists = ClassSession::query()
            ->where('class_id', $class->id)
            ->whereDate('session_date', $data['session_date'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'Đã có buổi học vào ngày này.');
        }

        if (! empty($data['makeup_of_session_id'])) {
            $orig = ClassSession::query()->find($data['makeup_of_session_id']);
            if (! $orig || (int) $orig->class_id !== (int) $class->id) {
                return back()->withInput()->with('error', 'Buổi gốc bù không thuộc lớp này.');
            }
        }

        if (($data['status'] ?? 'scheduled') !== 'cancelled' && ! $request->boolean('force_conflict')) {
            $hits = $conflicts->conflicts(
                $class,
                $data['session_date'],
                $data['start_time'] ?? null,
                $data['end_time'] ?? null,
                (int) $data['teacher_id'],
            );
            if ($hits !== []) {
                return back()->withInput()->with('error', 'Trùng lịch: '.collect($hits)->pluck('message')->unique()->implode(' | ')
                    .' — tick “Bỏ qua cảnh báo trùng” nếu vẫn muốn lưu.');
            }
        }

        if (($data['status'] ?? 'scheduled') === 'completed') {
            $attendanceError = $this->attendanceRequiredMessage($class, $data['session_date']);
            if ($attendanceError) {
                return back()->withInput()->with('error', $attendanceError);
            }
        }

        $session = ClassSession::query()->create([
            'class_id' => $class->id,
            'teacher_id' => $data['teacher_id'],
            'session_date' => $data['session_date'],
            'start_time' => $data['start_time'] ?? $class->start_time,
            'end_time' => $data['end_time'] ?? $class->end_time,
            'status' => $data['status'] ?? 'scheduled',
            'notes' => $data['notes'] ?? null,
            'makeup_of_session_id' => $data['makeup_of_session_id'] ?? null,
        ]);

        if ($session->status === 'completed') {
            $journals->ensureForSession($session);
            Notifier::remindSessionJournal($session, auth()->id());

            return redirect()
                ->route('admin.classes.show', [
                    'class' => $class,
                    'tab' => 'timetable',
                    'month' => $session->session_date->format('Y-m'),
                ])
                ->with('success', 'Đã thêm buổi hoàn thành. Nhật ký đã tạo sẵn — giáo viên sẽ cập nhật nội dung.');
        }

        return back()->with('success', 'Đã thêm buổi học.');
    }

    public function updateSession(Request $request, CourseClass $class, ClassSession $session, ClassSessionJournalService $journals, ScheduleConflictService $conflicts)
    {
        abort_unless($session->class_id === $class->id, 404);

        $data = $request->validate([
            'session_date' => 'required|date',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'teacher_id' => 'required|exists:teachers,id',
            'status' => 'required|in:scheduled,completed,cancelled',
            'notes' => 'nullable|string',
            'makeup_of_session_id' => 'nullable|exists:class_sessions,id',
            'force_conflict' => 'nullable|boolean',
        ]);

        $dup = ClassSession::query()
            ->where('class_id', $class->id)
            ->whereDate('session_date', $data['session_date'])
            ->where('id', '!=', $session->id)
            ->exists();

        if ($dup) {
            return back()->with('error', 'Đã có buổi học khác vào ngày này.');
        }

        if (! empty($data['makeup_of_session_id'])) {
            if ((int) $data['makeup_of_session_id'] === (int) $session->id) {
                return back()->withInput()->with('error', 'Buổi bù không thể trỏ chính nó.');
            }
            $orig = ClassSession::query()->find($data['makeup_of_session_id']);
            if (! $orig || (int) $orig->class_id !== (int) $class->id) {
                return back()->withInput()->with('error', 'Buổi gốc bù không thuộc lớp này.');
            }
        }

        if ($data['status'] !== 'cancelled' && ! $request->boolean('force_conflict')) {
            $hits = $conflicts->conflicts(
                $class,
                $data['session_date'],
                $data['start_time'] ?? null,
                $data['end_time'] ?? null,
                (int) $data['teacher_id'],
                (int) $session->id,
            );
            if ($hits !== []) {
                return back()->withInput()->with('error', 'Trùng lịch: '.collect($hits)->pluck('message')->unique()->implode(' | ')
                    .' — tick “Bỏ qua cảnh báo trùng” nếu vẫn muốn lưu.');
            }
        }

        $wasCompleted = $session->status === 'completed';
        $nowCompleted = $data['status'] === 'completed';

        if ($nowCompleted && ! $wasCompleted) {
            $attendanceError = $this->attendanceRequiredMessage($class, $data['session_date']);
            if ($attendanceError) {
                return back()->withInput()->with('error', $attendanceError);
            }
        }

        // Đổi ngày khi đã hoàn thành: vẫn cần điểm danh ngày mới
        if ($nowCompleted && $wasCompleted) {
            $oldDate = $session->session_date?->format('Y-m-d');
            $newDate = \Carbon\Carbon::parse($data['session_date'])->format('Y-m-d');
            if ($oldDate !== $newDate) {
                $attendanceError = $this->attendanceRequiredMessage($class, $data['session_date']);
                if ($attendanceError) {
                    return back()->withInput()->with('error', $attendanceError);
                }
            }
        }

        $session->update([
            'session_date' => $data['session_date'],
            'start_time' => $data['start_time'] ?? null,
            'end_time' => $data['end_time'] ?? null,
            'teacher_id' => $data['teacher_id'],
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
            'makeup_of_session_id' => $data['makeup_of_session_id'] ?? null,
        ]);

        if ($nowCompleted) {
            $journals->ensureForSession($session->fresh());

            if (! $wasCompleted) {
                Notifier::remindSessionJournal($session->fresh(), auth()->id());

                return redirect()
                    ->route('admin.classes.show', [
                        'class' => $class,
                        'tab' => 'timetable',
                        'month' => $session->session_date->format('Y-m'),
                    ])
                    ->with('success', 'Đã đánh dấu hoàn thành. Nhật ký đã tạo sẵn — giáo viên sẽ cập nhật nội dung.');
            }
        }

        return back()->with('success', 'Đã cập nhật buổi học.');
    }

    /**
     * Buổi hoàn thành bắt buộc đã điểm danh đủ học viên đang ghi danh.
     */
    protected function attendanceRequiredMessage(CourseClass $class, string $sessionDate): ?string
    {
        $studentIds = $class->students()->pluck('students.id');
        if ($studentIds->isEmpty()) {
            return null;
        }

        $markedIds = Attendance::query()
            ->where('class_id', $class->id)
            ->whereDate('session_date', $sessionDate)
            ->whereIn('student_id', $studentIds)
            ->pluck('student_id')
            ->unique();

        $missing = $studentIds->count() - $markedIds->count();
        if ($missing > 0) {
            $dateLabel = \Carbon\Carbon::parse($sessionDate)->format('d/m/Y');

            return "Chưa điểm danh đủ cho buổi {$dateLabel} ({$markedIds->count()}/{$studentIds->count()} HV). Hãy lưu điểm danh trước khi đánh dấu hoàn thành.";
        }

        return null;
    }

    public function updateJournal(
        Request $request,
        CourseClass $class,
        ClassSession $session,
        ClassSessionJournalService $journals
    ) {
        abort_unless($session->class_id === $class->id, 404);
        $this->authorizeTeacherSessionJournal($request->user(), $session);

        if ($session->status !== 'completed') {
            return back()->with('error', 'Chỉ cập nhật nhật ký khi buổi đã hoàn thành.');
        }

        $data = $request->validate([
            'lesson_title' => 'nullable|string|max:255',
            'content' => 'nullable|string|max:5000',
            'remarks' => 'nullable|string|max:5000',
        ]);

        $journal = $journals->ensureForSession($session, true);
        if (! $journal) {
            return back()->with('error', 'Không tạo được nhật ký.');
        }

        $journal->update([
            'lesson_title' => $data['lesson_title'] ?? null,
            'content' => $data['content'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'filled_by' => auth()->id(),
            'filled_at' => now(),
        ]);

        return back()->with('success', 'Đã lưu nhật ký buổi học.');
    }

    public function destroySession(CourseClass $class, ClassSession $session)
    {
        abort_unless($session->class_id === $class->id, 404);
        $session->delete();

        return back()->with('success', 'Đã xóa buổi học.');
    }

    public function availableStudents(Request $request, CourseClass $class)
    {
        $q = trim((string) $request->get('q', ''));
        $enrolledIds = $class->students()->pluck('students.id')->all();
        $branchId = CurrentBranch::id() ?: $class->branch_id;

        $students = Student::query()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->where('status', 'studying')
            ->when($enrolledIds, fn ($query) => $query->whereNotIn('id', $enrolledIds))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('parent_name', 'like', "%{$q}%")
                        ->orWhere('parent_phone', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'name', 'phone', 'parent_name', 'parent_phone', 'status']);

        return response()->json([
            'students' => $students->map(fn (Student $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'phone' => $s->phone ?: $s->parent_phone,
                'parent_name' => $s->parent_name,
                'status' => $s->statusLabel(),
            ])->values(),
            'total' => $students->count(),
            'max_students' => (int) $class->max_students,
            'enrolled_count' => count($enrolledIds),
        ]);
    }

    public function attachStudent(Request $request, CourseClass $class)
    {
        $data = $request->validate([
            'student_id' => 'nullable|exists:students,id',
            'student_ids' => 'nullable|array|min:1',
            'student_ids.*' => 'integer|exists:students,id',
        ]);

        $ids = collect($data['student_ids'] ?? [])
            ->when(! empty($data['student_id']), fn ($c) => $c->push((int) $data['student_id']))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return back()->with('error', 'Vui lòng chọn ít nhất một học viên.');
        }

        $enrolled = $class->students()->pluck('students.id')->all();
        $ids = array_values(array_diff($ids, $enrolled));
        if ($ids === []) {
            return back()->with('error', 'Các học viên đã chọn đều đã ở trong lớp.');
        }

        if ($class->max_students > 0) {
            $remaining = max(0, (int) $class->max_students - count($enrolled));
            if (count($ids) > $remaining) {
                return back()->with('error', "Lớp chỉ còn chỗ cho {$remaining} học viên (sĩ số tối đa {$class->max_students}).");
            }
        }

        $class->students()->syncWithoutDetaching($ids);

        return redirect()
            ->route('admin.classes.show', ['class' => $class, 'tab' => 'students'])
            ->with('success', 'Đã thêm '.count($ids).' học viên vào lớp.');
    }

    public function detachStudent(CourseClass $class, Student $student)
    {
        $class->students()->detach($student->id);

        return redirect()
            ->route('admin.classes.show', ['class' => $class, 'tab' => 'students'])
            ->with('success', 'Đã gỡ học viên khỏi lớp.');
    }

    public function storeInvoice(Request $request, CourseClass $class)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'fee_type' => 'required|in:monthly,per_session,course',
            'billing_month' => 'nullable|string|max:7',
            'session_ids' => 'nullable|array',
            'session_ids.*' => 'integer|exists:class_sessions,id',
            'sessions_count' => 'nullable|integer|min:0',
            'gross_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_reason' => 'nullable|string|max:255',
            'amount' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
            'status' => 'nullable|in:unpaid,paid,cancelled',
            'note' => 'nullable|string',
            'installment_count' => 'nullable|integer|min:1|max:24',
        ]);

        if (! $class->students()->where('students.id', $data['student_id'])->exists()) {
            return back()->with('error', 'Học viên không thuộc lớp này.');
        }

        if (($data['fee_type'] ?? '') === 'per_session' && empty($data['session_ids'])) {
            return back()->with('error', 'Thu theo buổi: vui lòng chọn ít nhất một buổi trên lịch.');
        }

        $payload = $this->buildTuitionInvoicePayload($class, $data);

        app(\App\Services\Finance\InvoiceService::class)->create(array_merge($payload, [
            'student_id' => $data['student_id'],
            'class_id' => $class->id,
            'branch_id' => $class->branch_id,
            'due_date' => $data['due_date'] ?? now()->endOfMonth()->toDateString(),
            'status' => $data['status'] ?? 'unpaid',
            'received_by' => auth()->id(),
        ]), (int) ($data['installment_count'] ?? 1));

        return redirect()
            ->route('admin.classes.show', [
                'class' => $class,
                'tab' => 'tuition',
                'billing_month' => $payload['billing_month'] ?: now()->format('Y-m'),
            ])
            ->with('success', 'Đã tạo hóa đơn.');
    }

    public function generateInvoices(Request $request, CourseClass $class)
    {
        $data = $request->validate([
            'fee_type' => 'required|in:monthly,per_session,course',
            'billing_month' => 'nullable|string|max:7',
            'session_ids' => 'nullable|array',
            'session_ids.*' => 'integer|exists:class_sessions,id',
            'sessions_count' => 'nullable|integer|min:0',
            'gross_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_reason' => 'nullable|string|max:255',
            'amount' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
            'skip_existing' => 'nullable|boolean',
            'note' => 'nullable|string',
        ]);

        if (($data['fee_type'] ?? '') === 'per_session' && empty($data['session_ids'])) {
            return back()->with('error', 'Thu theo buổi: vui lòng chọn ít nhất một buổi trên lịch.');
        }

        $data['note'] = trim((string) ($data['note'] ?? '')) ?: 'Tạo hàng loạt từ lớp';
        $payload = $this->buildTuitionInvoicePayload($class, $data);
        $students = $class->students()->get();
        $created = 0;
        $skipped = 0;

        foreach ($students as $student) {
            if ($request->boolean('skip_existing', true)) {
                $existsQuery = Invoice::query()
                    ->where('class_id', $class->id)
                    ->where('student_id', $student->id)
                    ->where('status', '!=', 'cancelled');

                if (($payload['fee_type'] ?? '') === 'course') {
                    $existsQuery->where('fee_type', 'course');
                } else {
                    $existsQuery->where('billing_month', $payload['billing_month']);
                }

                if ($existsQuery->exists()) {
                    $skipped++;
                    continue;
                }
            }

            app(\App\Services\Finance\InvoiceService::class)->create(array_merge($payload, [
                'student_id' => $student->id,
                'class_id' => $class->id,
                'branch_id' => $class->branch_id,
                'status' => 'unpaid',
                'due_date' => $data['due_date'] ?? now()->endOfMonth()->toDateString(),
            ]));
            $created++;
        }

        return redirect()
            ->route('admin.classes.show', [
                'class' => $class,
                'tab' => 'tuition',
                'billing_month' => $payload['billing_month'] ?: now()->format('Y-m'),
            ])
            ->with('success', "Đã tạo {$created} hóa đơn".($skipped ? ", bỏ qua {$skipped} học viên đã có HĐ" : '').'.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function buildTuitionInvoicePayload(CourseClass $class, array $data): array
    {
        return app(\App\Services\Finance\InvoiceService::class)->buildClassBillingPayload($class, $data);
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'subject_id' => 'nullable|exists:subjects,id',
            'teacher_id' => 'nullable|exists:teachers,id',
            'teacher_hourly_rate' => 'nullable|numeric|min:0',
            'schedule_days' => 'nullable|array',
            'schedule_days.*' => 'in:T2,T3,T4,T5,T6,T7,CN',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'room' => 'nullable|string|max:100',
            'max_students' => 'nullable|integer|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'tuition_fee' => 'nullable|numeric|min:0',
            'tuition_type' => 'nullable|in:monthly,per_session',
            'status' => 'nullable|in:active,inactive,completed',
        ]);
        $data['schedule_days'] = $data['schedule_days'] ?? [];
        $data['max_students'] = $data['max_students'] ?? 0;
        $data['tuition_fee'] = $data['tuition_fee'] ?? 0;
        $data['tuition_type'] = $data['tuition_type'] ?? 'monthly';
        $data['status'] = $data['status'] ?? 'active';
        $data['teacher_hourly_rate'] = filled($data['teacher_hourly_rate'] ?? null)
            ? $data['teacher_hourly_rate']
            : null;

        return $data;
    }

    protected function authorizeTeacherClassAccess(?\App\Models\User $user, CourseClass $class): void
    {
        if (! $user?->isRestrictedTeacher()) {
            return;
        }

        $teacher = $user->linkedTeacher();
        abort_unless($teacher, 403, 'Tài khoản giáo viên chưa gắn hồ sơ Teacher (cùng email).');

        $owns = (int) $class->teacher_id === (int) $teacher->id
            || $class->sessions()->where('teacher_id', $teacher->id)->exists();

        abort_unless($owns, 403, 'Bạn chỉ được xem lớp mình phụ trách.');
    }

    protected function authorizeTeacherSessionJournal(?\App\Models\User $user, ClassSession $session): void
    {
        if (! $user?->isRestrictedTeacher()) {
            return;
        }

        $teacher = $user->linkedTeacher();
        abort_unless(
            $teacher && (int) $session->teacher_id === (int) $teacher->id,
            403,
            'Bạn chỉ được ghi nhật ký buổi mình dạy.'
        );
    }
}
