<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\ClassSessionJournal;
use App\Models\CourseClass;

class ClassSessionJournalService
{
    /**
     * Tạo / đồng bộ nhật ký từ buổi học + điểm danh (sĩ số, vắng, muộn...).
     */
    public function ensureForSession(ClassSession $session, bool $refreshStats = true): ?ClassSessionJournal
    {
        if ($session->status !== 'completed') {
            return ClassSessionJournal::query()
                ->where('class_session_id', $session->id)
                ->first();
        }

        $session->loadMissing('courseClass');
        $class = $session->courseClass;
        if (! $class) {
            return null;
        }

        $stats = $this->attendanceStats($class, $session->session_date->format('Y-m-d'));

        $journal = ClassSessionJournal::query()->firstOrNew([
            'class_session_id' => $session->id,
        ]);

        $journal->class_id = $class->id;
        $journal->session_date = $session->session_date;
        $journal->class_name = $class->name;

        if (! $journal->exists || $refreshStats) {
            $journal->enrollment_count = $stats['enrollment_count'];
            $journal->present_count = $stats['present_count'];
            $journal->absent_count = $stats['absent_count'];
            $journal->excused_count = $stats['excused_count'];
            $journal->late_count = $stats['late_count'];
        }

        $journal->save();

        return $journal;
    }

    /**
     * Làm mới sĩ số / điểm danh trên nhật ký nếu đã có (sau khi lưu điểm danh).
     */
    public function refreshStatsForClassDate(int $classId, string $ymd): void
    {
        $session = ClassSession::query()
            ->where('class_id', $classId)
            ->whereDate('session_date', $ymd)
            ->where('status', 'completed')
            ->first();

        if (! $session) {
            return;
        }

        $this->ensureForSession($session, true);
    }

    /**
     * @return array{
     *     enrollment_count: int,
     *     present_count: int,
     *     absent_count: int,
     *     excused_count: int,
     *     late_count: int
     * }
     */
    public function attendanceStats(CourseClass $class, string $ymd): array
    {
        $enrollment = (int) $class->students()->count();

        $rows = Attendance::query()
            ->where('class_id', $class->id)
            ->whereDate('session_date', $ymd)
            ->get();

        return [
            'enrollment_count' => $enrollment,
            'present_count' => $rows->where('status', 'present')->count(),
            'absent_count' => $rows->where('status', 'absent')->count(),
            'excused_count' => $rows->where('status', 'excused')->count(),
            'late_count' => $rows->where('status', 'late')->count(),
        ];
    }
}
