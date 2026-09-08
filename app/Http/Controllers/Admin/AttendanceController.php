<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\CourseClass;
use App\Services\ClassSessionJournalService;
use App\Support\CurrentBranch;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $classes = CurrentBranch::apply(CourseClass::query())->where('status', 'active')->orderBy('name')->get();
        $classId = $request->get('class_id', $classes->first()?->id);
        $courseClass = $classId ? CourseClass::with('students')->find($classId) : null;

        $sessionOptions = collect();
        $date = $request->get('session_date');

        if ($courseClass) {
            $sessions = ClassSession::query()
                ->where('class_id', $courseClass->id)
                ->where('status', '!=', 'cancelled')
                ->orderBy('session_date')
                ->orderBy('start_time')
                ->get();

            $sessionOptions = $sessions
                ->groupBy(fn (ClassSession $s) => optional($s->session_date)->format('Y-m-d'))
                ->filter(fn ($group, $key) => filled($key))
                ->map(function ($group, $ymd) {
                    $times = $group->map(function (ClassSession $s) {
                        $start = $s->start_time ? substr((string) $s->start_time, 0, 5) : '';
                        $end = $s->end_time ? substr((string) $s->end_time, 0, 5) : '';
                        $range = trim($start.($start || $end ? '–' : '').$end, '–');

                        return $range !== '' ? $range : null;
                    })->filter()->unique()->values();

                    $statuses = $group->pluck('status')->unique()->values();
                    $statusLabel = $statuses->count() === 1
                        ? $group->first()->statusLabel()
                        : $statuses->map(fn ($st) => match ($st) {
                            'scheduled' => 'Đã lên lịch',
                            'completed' => 'Hoàn thành',
                            default => $st,
                        })->implode('/');

                    $dateLabel = optional($group->first()->session_date)->format('d/m/Y') ?: $ymd;
                    $timePart = $times->isEmpty() ? '' : ' · '.$times->implode(', ');

                    return [
                        'date' => $ymd,
                        'label' => $dateLabel.$timePart.' ('.$statusLabel.')',
                        'sort' => $ymd,
                    ];
                })
                ->sortBy('sort')
                ->values();

            $allowed = $sessionOptions->pluck('date');
            if (! $date || ! $allowed->contains($date)) {
                $date = $this->defaultSessionDate($allowed);
            }
        } else {
            $date = null;
        }

        $attendances = collect();
        if ($courseClass && $date) {
            $attendances = Attendance::where('class_id', $courseClass->id)
                ->whereDate('session_date', $date)
                ->get()
                ->keyBy('student_id');
        }

        return view('admin.students.attendances', compact(
            'classes',
            'courseClass',
            'classId',
            'date',
            'attendances',
            'sessionOptions'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'session_date' => 'required|date',
            'statuses' => 'nullable|array',
            'statuses.*' => 'in:present,absent,late,excused',
            'notes' => 'nullable|array',
            'notes.*' => 'nullable|string|max:500',
        ]);

        $hasSession = ClassSession::query()
            ->where('class_id', $data['class_id'])
            ->whereDate('session_date', $data['session_date'])
            ->where('status', '!=', 'cancelled')
            ->exists();

        if (! $hasSession) {
            throw ValidationException::withMessages([
                'session_date' => 'Ngày điểm danh phải trùng một buổi trong thời khóa biểu của lớp (không tính buổi Hủy).',
            ]);
        }

        $class = CourseClass::with('students')->findOrFail($data['class_id']);
        $statuses = $data['statuses'] ?? [];
        $notes = $data['notes'] ?? [];

        foreach ($class->students as $student) {
            $status = $statuses[$student->id] ?? 'absent';
            $note = trim((string) ($notes[$student->id] ?? ''));

            Attendance::updateOrCreate(
                [
                    'class_id' => $class->id,
                    'student_id' => $student->id,
                    'session_date' => $data['session_date'],
                ],
                [
                    'status' => $status,
                    'note' => $note !== '' ? $note : null,
                ]
            );
        }

        app(ClassSessionJournalService::class)->refreshStatsForClassDate(
            (int) $class->id,
            $data['session_date']
        );

        $redirectTo = (string) $request->input('redirect_to', '');
        if ($redirectTo !== '' && str_starts_with($redirectTo, '/admin/')) {
            return redirect($redirectTo)->with('success', 'Đã lưu điểm danh.');
        }

        return redirect()
            ->route('admin.attendances.index', ['class_id' => $class->id, 'session_date' => $data['session_date']])
            ->with('success', 'Đã lưu điểm danh.');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, string>  $allowedDates  Y-m-d
     */
    protected function defaultSessionDate($allowedDates): ?string
    {
        if ($allowedDates->isEmpty()) {
            return null;
        }

        $today = now()->toDateString();
        if ($allowedDates->contains($today)) {
            return $today;
        }

        $upcoming = $allowedDates->first(fn ($d) => $d >= $today);
        if ($upcoming) {
            return $upcoming;
        }

        return $allowedDates->last();
    }
}
