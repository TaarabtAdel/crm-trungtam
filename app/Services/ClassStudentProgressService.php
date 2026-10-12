<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\CourseClass;
use App\Models\Student;
use App\Services\Finance\ClassEnrollmentBillingService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ClassStudentProgressService
{
    public function __construct(
        protected ClassEnrollmentBillingService $enrollmentBilling
    ) {}

    /**
     * @return list<string>
     */
    public function attendedStatuses(): array
    {
        return ['present', 'late'];
    }

    /**
     * @return array{
     *   month: string|null,
     *   students: array<int, array<string, mixed>>
     * }
     */
    public function buildProgressReport(CourseClass $class, ?string $month = null): array
    {
        $class->loadMissing(['students' => fn ($q) => $q->orderBy('name')]);

        $sessionsQuery = $class->sessions()
            ->where('status', '!=', 'cancelled')
            ->orderBy('session_date')
            ->orderBy('start_time');

        if ($month !== null && $month !== '' && preg_match('/^\d{4}-\d{2}$/', $month)) {
            [$year, $monthNum] = array_map('intval', explode('-', $month));
            $sessionsQuery
                ->whereYear('session_date', $year)
                ->whereMonth('session_date', $monthNum);
        }

        $sessions = $sessionsQuery->get();
        $today = now()->startOfDay();

        $makeupScheduledFor = ClassSession::query()
            ->where('class_id', $class->id)
            ->whereNotNull('makeup_of_session_id')
            ->pluck('makeup_of_session_id')
            ->map(fn ($id) => (int) $id)
            ->flip();

        $attendancesByStudent = Attendance::query()
            ->where('class_id', $class->id)
            ->get()
            ->groupBy('student_id')
            ->map(fn (Collection $rows) => $rows->keyBy(
                fn (Attendance $a) => $a->session_date?->format('Y-m-d') ?? ''
            )->filter(fn ($v, $k) => $k !== ''));

        $students = [];
        foreach ($class->students as $student) {
            $studentAtt = $attendancesByStudent->get($student->id, collect());
            $students[(int) $student->id] = $this->summarizeStudent(
                $class,
                $student,
                $sessions,
                $studentAtt,
                $today,
                $makeupScheduledFor
            );
        }

        return [
            'month' => $month ?: null,
            'students' => $students,
        ];
    }

    /**
     * @param  Collection<int, ClassSession>  $sessions
     * @param  Collection<string, Attendance>  $attendanceByDate
     * @return array<string, mixed>
     */
    protected function summarizeStudent(
        CourseClass $class,
        Student $student,
        Collection $sessions,
        Collection $attendanceByDate,
        Carbon $today,
        Collection $makeupScheduledFor
    ): array {
        $attended = 0;
        $absent = 0;
        $excused = 0;
        $notMarked = 0;
        $eligible = 0;
        $missingSessions = [];

        foreach ($sessions as $session) {
            if ($session->session_date === null) {
                continue;
            }

            $sessionDay = $session->session_date->copy()->startOfDay();
            if ($sessionDay->gt($today)) {
                continue;
            }

            if (! $this->isEnrolledOnDate($class, $student, $sessionDay)) {
                continue;
            }

            $eligible++;
            $dateKey = $session->session_date->format('Y-m-d');
            $att = $attendanceByDate->get($dateKey);
            $status = $att?->status;

            if (in_array($status, $this->attendedStatuses(), true)) {
                $attended++;

                continue;
            }

            if ($status === 'excused') {
                $excused++;

                continue;
            }

            if ($status === 'absent') {
                $absent++;
                $missingSessions[] = $this->missingSessionPayload($session, 'vắng', $makeupScheduledFor);

                continue;
            }

            $notMarked++;
            $reason = $session->status === 'completed' ? 'chưa điểm danh (buổi đã hoàn thành)' : 'chưa điểm danh';
            $missingSessions[] = $this->missingSessionPayload($session, $reason, $makeupScheduledFor);
        }

        return [
            'attended' => $attended,
            'eligible' => $eligible,
            'absent' => $absent,
            'excused' => $excused,
            'not_marked' => $notMarked,
            'needs_makeup' => max(0, $eligible - $attended - $excused),
            'missing_sessions' => $missingSessions,
        ];
    }

    protected function isEnrolledOnDate(CourseClass $class, Student $student, Carbon $sessionDay): bool
    {
        $from = $this->enrollmentBilling->enrollmentFromForProgress($student, $class);
        $to = $this->enrollmentBilling->enrollmentTo($student, $class);

        if ($from && $sessionDay->lt($from)) {
            return false;
        }
        if ($to && $sessionDay->gt($to)) {
            return false;
        }

        return true;
    }

    /**
     * @param  Collection<int, int>  $makeupScheduledFor  keys = origin session ids
     * @return array<string, mixed>
     */
    protected function missingSessionPayload(ClassSession $session, string $reason, Collection $makeupScheduledFor): array
    {
        $time = trim(
            ($session->start_time ? substr((string) $session->start_time, 0, 5) : '')
            .(($session->start_time || $session->end_time) ? '–' : '')
            .($session->end_time ? substr((string) $session->end_time, 0, 5) : ''),
            '–'
        );

        return [
            'session_id' => $session->id,
            'date' => $session->session_date->format('Y-m-d'),
            'date_label' => $session->session_date->format('d/m/Y'),
            'time' => $time !== '' ? $time : null,
            'status' => $session->status,
            'status_label' => $session->statusLabel(),
            'reason' => $reason,
            'has_makeup' => $makeupScheduledFor->has($session->id),
        ];
    }
}
