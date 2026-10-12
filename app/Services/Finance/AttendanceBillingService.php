<?php

namespace App\Services\Finance;

use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\CourseClass;
use App\Models\Invoice;
use Illuminate\Support\Collection;

class AttendanceBillingService
{
    /**
     * Trạng thái điểm danh được tính thu theo buổi.
     *
     * @return list<string>
     */
    public function billableStatuses(): array
    {
        return ['present', 'late'];
    }

    /**
     * @return list<int>
     */
    public function alreadyBilledSessionIds(int $classId, int $studentId): array
    {
        return Invoice::query()
            ->where('class_id', $classId)
            ->where('student_id', $studentId)
            ->where('status', '!=', 'cancelled')
            ->where('fee_type', 'per_session')
            ->get()
            ->flatMap(fn (Invoice $inv) => $inv->billed_session_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Buổi trong tháng có điểm danh Có mặt / Muộn, chưa nằm trong HĐ theo buổi.
     *
     * @return Collection<int, ClassSession>
     */
    public function billableSessionsForStudent(
        CourseClass $class,
        int $studentId,
        string $billingMonth,
        bool $excludeAlreadyBilled = true
    ): Collection {
        $sessions = $class->sessionsInMonth($billingMonth);
        if ($sessions->isEmpty()) {
            return collect();
        }

        $dateKeys = $sessions
            ->map(fn (ClassSession $s) => $s->session_date->format('Y-m-d'))
            ->unique()
            ->values()
            ->all();

        $attendances = Attendance::query()
            ->where('class_id', $class->id)
            ->where('student_id', $studentId)
            ->whereIn('session_date', $dateKeys)
            ->get()
            ->keyBy(fn (Attendance $a) => $a->session_date->format('Y-m-d'));

        $billed = $excludeAlreadyBilled
            ? $this->alreadyBilledSessionIds($class->id, $studentId)
            : [];
        $statuses = $this->billableStatuses();

        return $sessions->filter(function (ClassSession $session) use ($attendances, $statuses, $billed) {
            if (in_array($session->id, $billed, true)) {
                return false;
            }
            $dateKey = $session->session_date->format('Y-m-d');
            $att = $attendances->get($dateKey);

            return $att && in_array($att->status, $statuses, true);
        })->values();
    }

    /**
     * @return array<int, list<int>> student_id => session ids
     */
    public function billableSessionIdsByStudent(CourseClass $class, string $billingMonth): array
    {
        $class->loadMissing('students');
        $map = [];

        foreach ($class->students as $student) {
            $ids = $this->billableSessionsForStudent($class, (int) $student->id, $billingMonth)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
            if ($ids !== []) {
                $map[(int) $student->id] = $ids;
            }
        }

        return $map;
    }
}
