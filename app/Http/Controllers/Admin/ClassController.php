<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\ClassSession;
use App\Models\CourseClass;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Services\ClassTimetableGenerator;
use App\Support\CurrentBranch;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ClassController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $classes = CourseClass::with(['branch', 'subject', 'teacher', 'students'])
            ->withCount('sessions')
            ->tap(fn ($query) => CurrentBranch::apply($query))
            ->when($q, fn ($query) => $query->where('name', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $subjects = CurrentBranch::apply(Subject::query())->where('status', 'active')->orderBy('name')->get();
        $teachers = CurrentBranch::apply(Teacher::query())->where('status', 'active')->orderBy('name')->get();

        return view('admin.training.classes', compact('classes', 'branches', 'subjects', 'teachers', 'q'));
    }

    public function show(Request $request, CourseClass $class)
    {
        $tab = $request->get('tab', 'info');
        if (! in_array($tab, ['info', 'students', 'timetable', 'tuition'], true)) {
            $tab = 'info';
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
        $month = now()->format('Y-m');
        $defaultFrom = now()->startOfMonth()->toDateString();
        $defaultTo = now()->addMonths(2)->endOfMonth()->toDateString();
        $invoices = collect();
        $billingMonth = now()->format('Y-m');
        $suggestion = null;

        if ($tab === 'students') {
            $classStudents = $class->students()->orderBy('name')->get();
            $enrolledIds = $classStudents->pluck('id')->all();
            $branchId = CurrentBranch::id() ?: $class->branch_id;
            $availableStudents = Student::query()
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->where('status', 'studying')
                ->when($enrolledIds, fn ($q) => $q->whereNotIn('id', $enrolledIds))
                ->orderBy('name')
                ->get();
        }

        if ($tab === 'timetable') {
            $month = $request->get('month', now()->format('Y-m'));
            if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
                $month = now()->format('Y-m');
            }
            [$year, $monthNum] = array_map('intval', explode('-', $month));
            $sessions = $class->sessions()
                ->with('teacher')
                ->whereYear('session_date', $year)
                ->whereMonth('session_date', $monthNum)
                ->orderBy('session_date')
                ->orderBy('start_time')
                ->get();
            $defaultFrom = optional($class->start_date)->format('Y-m-d') ?: now()->startOfMonth()->toDateString();
            $defaultTo = optional($class->end_date)->format('Y-m-d') ?: now()->addMonths(2)->endOfMonth()->toDateString();
        }

        if ($tab === 'tuition') {
            $billingMonth = $request->get('billing_month', now()->format('Y-m'));
            $classStudents = $class->students()->orderBy('name')->get();
            $invoices = Invoice::with('student')
                ->where('class_id', $class->id)
                ->latest()
                ->paginate(20)
                ->withQueryString();
            $suggestion = $class->suggestInvoiceAmount($billingMonth);
        }

        if ($tab === 'info') {
            $class->loadCount(['invoices']);
        }

        return view('admin.training.class_show', compact(
            'class', 'tab', 'branches', 'subjects', 'teachers', 'sessionTeachers', 'days',
            'availableStudents', 'classStudents',
            'sessions', 'month', 'defaultFrom', 'defaultTo',
            'invoices', 'billingMonth', 'suggestion'
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
                'month' => substr($data['from'], 0, 7),
            ])
            ->with('success', $msg);
    }

    public function storeSession(Request $request, CourseClass $class)
    {
        $data = $request->validate([
            'session_date' => 'required|date',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'teacher_id' => 'required|exists:teachers,id',
            'status' => 'nullable|in:scheduled,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        $exists = ClassSession::query()
            ->where('class_id', $class->id)
            ->whereDate('session_date', $data['session_date'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'Đã có buổi học vào ngày này.');
        }

        ClassSession::query()->create([
            'class_id' => $class->id,
            'teacher_id' => $data['teacher_id'],
            'session_date' => $data['session_date'],
            'start_time' => $data['start_time'] ?? $class->start_time,
            'end_time' => $data['end_time'] ?? $class->end_time,
            'status' => $data['status'] ?? 'scheduled',
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Đã thêm buổi học.');
    }

    public function updateSession(Request $request, CourseClass $class, ClassSession $session)
    {
        abort_unless($session->class_id === $class->id, 404);

        $data = $request->validate([
            'session_date' => 'required|date',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'teacher_id' => 'required|exists:teachers,id',
            'status' => 'required|in:scheduled,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        $dup = ClassSession::query()
            ->where('class_id', $class->id)
            ->whereDate('session_date', $data['session_date'])
            ->where('id', '!=', $session->id)
            ->exists();

        if ($dup) {
            return back()->with('error', 'Đã có buổi học khác vào ngày này.');
        }

        $session->update($data);

        return back()->with('success', 'Đã cập nhật buổi học.');
    }

    public function destroySession(CourseClass $class, ClassSession $session)
    {
        abort_unless($session->class_id === $class->id, 404);
        $session->delete();

        return back()->with('success', 'Đã xóa buổi học.');
    }

    public function attachStudent(Request $request, CourseClass $class)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $class->students()->syncWithoutDetaching([$data['student_id']]);

        return redirect()
            ->route('admin.classes.show', ['class' => $class, 'tab' => 'students'])
            ->with('success', 'Đã thêm học viên vào lớp.');
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
            'billing_month' => 'nullable|string|max:7',
            'amount' => 'nullable|numeric|min:0',
            'sessions_count' => 'nullable|integer|min:0',
            'due_date' => 'nullable|date',
            'status' => 'nullable|in:unpaid,paid,cancelled',
            'note' => 'nullable|string',
        ]);

        if (! $class->students()->where('students.id', $data['student_id'])->exists()) {
            return back()->with('error', 'Học viên không thuộc lớp này.');
        }

        $billingMonth = $data['billing_month'] ?? now()->format('Y-m');
        $suggestion = $class->suggestInvoiceAmount($billingMonth);

        $status = $data['status'] ?? 'unpaid';
        Invoice::create([
            'student_id' => $data['student_id'],
            'class_id' => $class->id,
            'billing_month' => $billingMonth,
            'fee_type' => $suggestion['fee_type'],
            'sessions_count' => $data['sessions_count'] ?? $suggestion['sessions_count'],
            'amount' => $data['amount'] ?? $suggestion['amount'],
            'due_date' => $data['due_date'] ?? null,
            'status' => $status,
            'paid_at' => $status === 'paid' ? now() : null,
            'note' => $data['note'] ?? null,
        ]);

        return redirect()
            ->route('admin.classes.show', ['class' => $class, 'tab' => 'tuition', 'billing_month' => $billingMonth])
            ->with('success', 'Đã tạo hóa đơn.');
    }

    public function generateInvoices(Request $request, CourseClass $class)
    {
        $data = $request->validate([
            'billing_month' => 'required|string|max:7',
            'skip_existing' => 'nullable|boolean',
        ]);

        $billingMonth = $data['billing_month'];
        $suggestion = $class->suggestInvoiceAmount($billingMonth);
        $students = $class->students()->get();
        $created = 0;
        $skipped = 0;

        foreach ($students as $student) {
            if ($request->boolean('skip_existing', true)) {
                $exists = Invoice::query()
                    ->where('class_id', $class->id)
                    ->where('student_id', $student->id)
                    ->where('billing_month', $billingMonth)
                    ->where('status', '!=', 'cancelled')
                    ->exists();
                if ($exists) {
                    $skipped++;
                    continue;
                }
            }

            Invoice::create([
                'student_id' => $student->id,
                'class_id' => $class->id,
                'billing_month' => $billingMonth,
                'fee_type' => $suggestion['fee_type'],
                'sessions_count' => $suggestion['sessions_count'],
                'amount' => $suggestion['amount'],
                'status' => 'unpaid',
                'due_date' => now()->endOfMonth()->toDateString(),
                'note' => 'Tạo hàng loạt từ lớp',
            ]);
            $created++;
        }

        return redirect()
            ->route('admin.classes.show', ['class' => $class, 'tab' => 'tuition', 'billing_month' => $billingMonth])
            ->with('success', "Đã tạo {$created} hóa đơn".($skipped ? ", bỏ qua {$skipped} học viên đã có HĐ" : '').'.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'subject_id' => 'nullable|exists:subjects,id',
            'teacher_id' => 'nullable|exists:teachers,id',
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

        return $data;
    }
}
