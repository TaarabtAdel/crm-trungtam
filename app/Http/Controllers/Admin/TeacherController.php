<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\ClassSession;
use App\Models\CourseClass;
use App\Models\Teacher;
use App\Support\CurrentBranch;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $status = $request->get('status');
        $teachers = Teacher::with('branch')
            ->withCount('classes')
            ->tap(fn ($query) => CurrentBranch::apply($query))
            ->when($q, function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('specialty', 'like', "%{$q}%");
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        $month = (int) $request->get('payroll_month', now()->month);
        $year = (int) $request->get('payroll_year', now()->year);
        $payroll = $this->buildPayroll($month, $year);

        return view('admin.training.teachers', compact('teachers', 'branches', 'q', 'status', 'payroll', 'month', 'year'));
    }

    public function show(Request $request, Teacher $teacher)
    {
        $tab = $request->get('tab', 'info');
        if (! in_array($tab, ['info', 'classes', 'payroll', 'schedule'], true)) {
            $tab = 'info';
        }

        $teacher->load('branch');
        $teacher->loadCount(['classes', 'sessions']);

        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $teachingClasses = collect();
        $sessions = collect();
        $availableMonths = [];
        $scheduleMonth = '';
        $month = (int) $request->get('payroll_month', now()->month);
        $year = (int) $request->get('payroll_year', now()->year);
        $payrollSummary = [
            'rate' => (float) $teacher->hourly_rate,
            'sessions' => 0,
            'hours' => 0.0,
            'total' => 0.0,
        ];
        $payrollSessions = collect();

        if ($tab === 'classes') {
            $primary = $teacher->classes()
                ->with(['subject', 'branch'])
                ->withCount(['students', 'sessions'])
                ->orderBy('name')
                ->get()
                ->map(function (CourseClass $class) {
                    $class->setAttribute('is_substitute_only', false);

                    return $class;
                });

            $primaryIds = $primary->pluck('id')->all();
            $sessionClassIds = ClassSession::query()
                ->where('teacher_id', $teacher->id)
                ->when($primaryIds, fn ($q) => $q->whereNotIn('class_id', $primaryIds))
                ->distinct()
                ->pluck('class_id')
                ->all();

            $substitutes = CourseClass::query()
                ->with(['subject', 'branch'])
                ->withCount(['students', 'sessions'])
                ->whereIn('id', $sessionClassIds)
                ->orderBy('name')
                ->get()
                ->map(function (CourseClass $class) {
                    $class->setAttribute('is_substitute_only', true);

                    return $class;
                });

            $teachingClasses = $primary->concat($substitutes)->values();
        }

        if ($tab === 'payroll') {
            $payrollSessions = ClassSession::query()
                ->with('courseClass')
                ->where('teacher_id', $teacher->id)
                ->where('status', 'completed')
                ->whereMonth('session_date', $month)
                ->whereYear('session_date', $year)
                ->orderBy('session_date')
                ->orderBy('start_time')
                ->get();

            $hours = $payrollSessions->sum(fn (ClassSession $s) => $s->hours());
            $payrollSummary = [
                'rate' => (float) $teacher->hourly_rate,
                'rate_mixed' => $payrollSessions->contains(fn (ClassSession $s) => $s->courseClass?->hasCustomTeacherRate()),
                'sessions' => $payrollSessions->count(),
                'hours' => round($hours, 2),
                'total' => (float) $payrollSessions->sum(fn (ClassSession $s) => $s->teacherPayAmount()),
            ];
        }

        if ($tab === 'schedule') {
            $scheduleMonth = (string) $request->get('month', '');
            if ($scheduleMonth !== '' && ! preg_match('/^\d{4}-\d{2}$/', $scheduleMonth)) {
                $scheduleMonth = '';
            }

            $availableMonths = ClassSession::query()
                ->where('teacher_id', $teacher->id)
                ->selectRaw("DATE_FORMAT(session_date, '%Y-%m') as ym")
                ->groupBy('ym')
                ->orderByDesc('ym')
                ->pluck('ym')
                ->filter()
                ->values()
                ->all();

            $sessionsQuery = ClassSession::query()
                ->with('courseClass')
                ->where('teacher_id', $teacher->id);

            if ($scheduleMonth !== '') {
                [$y, $m] = array_map('intval', explode('-', $scheduleMonth));
                $sessionsQuery->whereYear('session_date', $y)->whereMonth('session_date', $m);
            }

            $sessions = $sessionsQuery
                ->orderBy('session_date')
                ->orderBy('start_time')
                ->get();
        }

        return view('admin.training.teacher_show', compact(
            'teacher', 'tab', 'branches',
            'teachingClasses',
            'month', 'year', 'payrollSummary', 'payrollSessions',
            'sessions', 'scheduleMonth', 'availableMonths'
        ));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $teacher = Teacher::create($data);

        return redirect()
            ->route('admin.teachers.show', $teacher)
            ->with('success', 'Đã thêm giáo viên.');
    }

    public function update(Request $request, Teacher $teacher)
    {
        $data = $this->validated($request, $teacher->id);
        $teacher->update($data);

        if ($request->boolean('from_detail')) {
            return redirect()
                ->route('admin.teachers.show', ['teacher' => $teacher, 'tab' => 'info'])
                ->with('success', 'Đã cập nhật giáo viên.');
        }

        return back()->with('success', 'Đã cập nhật giáo viên.');
    }

    public function destroy(Teacher $teacher)
    {
        $teacher->delete();

        return redirect()->route('admin.teachers.index')->with('success', 'Đã xóa giáo viên.');
    }

    public function exportPayroll(Request $request): StreamedResponse
    {
        $month = (int) $request->get('payroll_month', now()->month);
        $year = (int) $request->get('payroll_year', now()->year);
        $payroll = $this->buildPayroll($month, $year);

        return response()->streamDownload(function () use ($payroll, $month, $year) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['Giáo viên', 'Chi nhánh', 'Đơn giá', 'Số buổi hoàn thành', 'Tổng giờ', 'Thực nhận', "Tháng {$month}/{$year}"]);
            foreach ($payroll as $row) {
                fputcsv($out, [
                    $row['name'], $row['branch'], $row['rate'], $row['sessions'], $row['hours'], $row['total'],
                ]);
            }
            fclose($out);
        }, "bang-luong-gv-{$month}-{$year}.csv");
    }

    protected function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:teachers,email'.($ignoreId ? ",{$ignoreId}" : ''),
            'phone' => 'nullable|string|max:30',
            'specialty' => 'nullable|string|max:255',
            'qualification' => 'nullable|string|max:255',
            'hourly_rate' => 'nullable|numeric|min:0',
            'joined_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);
        $data['hourly_rate'] = $data['hourly_rate'] ?? 0;
        $data['status'] = $data['status'] ?? 'active';

        return $data;
    }

    protected function buildPayroll(int $month, int $year): array
    {
        $teachers = CurrentBranch::apply(Teacher::with('branch'))->orderBy('name')->get();
        $rows = [];

        foreach ($teachers as $teacher) {
            $sessions = ClassSession::with('courseClass')
                ->where('teacher_id', $teacher->id)
                ->where('status', 'completed')
                ->whereMonth('session_date', $month)
                ->whereYear('session_date', $year)
                ->get();

            $hours = $sessions->sum(fn ($s) => $s->hours());
            $rows[] = [
                'name' => $teacher->name,
                'branch' => $teacher->branch?->name,
                'rate' => (float) $teacher->hourly_rate,
                'rate_mixed' => $sessions->contains(fn ($s) => $s->courseClass?->hasCustomTeacherRate()),
                'sessions' => $sessions->count(),
                'hours' => round($hours, 2),
                'total' => (float) $sessions->sum(fn ($s) => $s->teacherPayAmount()),
            ];
        }

        return $rows;
    }
}
