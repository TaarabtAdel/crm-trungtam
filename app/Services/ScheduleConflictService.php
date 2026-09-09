<?php

namespace App\Services;

use App\Models\ClassSession;
use App\Models\CourseClass;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ScheduleConflictService
{
    /**
     * @return array<int, array{type:string,message:string,session_id:int}>
     */
    public function conflicts(
        CourseClass $class,
        string $sessionDate,
        ?string $startTime,
        ?string $endTime,
        ?int $teacherId,
        ?int $excludeSessionId = null,
        bool $checkStudents = true,
    ): array {
        $start = $this->normalizeTime($startTime ?: $class->start_time);
        $end = $this->normalizeTime($endTime ?: $class->end_time);
        if (! $start || ! $end) {
            return [];
        }

        $found = [];

        if ($teacherId) {
            $teacherHits = $this->overlappingSessions(
                date: $sessionDate,
                start: $start,
                end: $end,
                excludeSessionId: $excludeSessionId,
                teacherId: $teacherId,
            );
            foreach ($teacherHits as $hit) {
                $found[] = [
                    'type' => 'teacher',
                    'session_id' => $hit->id,
                    'message' => 'GV trùng lịch với lớp '.$hit->courseClass?->name
                        .' ('.$this->formatRange($hit).')',
                ];
            }
        }

        $room = trim((string) ($class->room ?? ''));
        if ($room !== '') {
            $roomHits = $this->overlappingSessions(
                date: $sessionDate,
                start: $start,
                end: $end,
                excludeSessionId: $excludeSessionId,
                room: $room,
                branchId: $class->branch_id,
            )->filter(fn (ClassSession $s) => (int) $s->class_id !== (int) $class->id);
            foreach ($roomHits as $hit) {
                $found[] = [
                    'type' => 'room',
                    'session_id' => $hit->id,
                    'message' => 'Phòng "'.$room.'" trùng với lớp '.$hit->courseClass?->name
                        .' ('.$this->formatRange($hit).')',
                ];
            }
        }

        if ($checkStudents) {
            $studentIds = $class->students()->pluck('students.id');
            if ($studentIds->isNotEmpty()) {
                $studentHits = $this->overlappingSessions(
                    date: $sessionDate,
                    start: $start,
                    end: $end,
                    excludeSessionId: $excludeSessionId,
                )->filter(fn (ClassSession $s) => (int) $s->class_id !== (int) $class->id);

                foreach ($studentHits as $hit) {
                    $overlapIds = $hit->courseClass?->students()
                        ->whereIn('students.id', $studentIds)
                        ->pluck('students.name') ?? collect();
                    if ($overlapIds->isEmpty()) {
                        continue;
                    }
                    $found[] = [
                        'type' => 'student',
                        'session_id' => $hit->id,
                        'message' => 'HV trùng lịch ('. $overlapIds->take(3)->implode(', ')
                            .($overlapIds->count() > 3 ? '…' : '').') với lớp '
                            .$hit->courseClass?->name.' ('.$this->formatRange($hit).')',
                    ];
                }
            }
        }

        return $found;
    }

    /**
     * @return Collection<int, ClassSession>
     */
    protected function overlappingSessions(
        string $date,
        string $start,
        string $end,
        ?int $excludeSessionId = null,
        ?int $teacherId = null,
        ?string $room = null,
        ?int $branchId = null,
    ): Collection {
        $query = ClassSession::query()
            ->with(['courseClass.students'])
            ->whereDate('session_date', $date)
            ->where('status', '!=', 'cancelled')
            ->when($excludeSessionId, fn ($q) => $q->where('id', '!=', $excludeSessionId))
            ->when($teacherId, fn ($q) => $q->where('teacher_id', $teacherId))
            ->when($room !== null, function ($q) use ($room, $branchId) {
                $q->whereHas('courseClass', function ($c) use ($room, $branchId) {
                    $c->where('room', $room);
                    if ($branchId) {
                        $c->where('branch_id', $branchId);
                    }
                });
            });

        return $query->get()->filter(function (ClassSession $s) use ($start, $end, $date) {
            $otherStart = $this->normalizeTime($s->start_time ?: $s->courseClass?->start_time);
            $otherEnd = $this->normalizeTime($s->end_time ?: $s->courseClass?->end_time);
            if (! $otherStart || ! $otherEnd) {
                return false;
            }

            return $this->timesOverlap($date, $start, $end, $otherStart, $otherEnd);
        })->values();
    }

    protected function timesOverlap(string $date, string $aStart, string $aEnd, string $bStart, string $bEnd): bool
    {
        $a0 = Carbon::parse($date.' '.$aStart);
        $a1 = Carbon::parse($date.' '.$aEnd);
        $b0 = Carbon::parse($date.' '.$bStart);
        $b1 = Carbon::parse($date.' '.$bEnd);

        return $a0->lt($b1) && $b0->lt($a1);
    }

    protected function normalizeTime(?string $time): ?string
    {
        if (! $time) {
            return null;
        }
        $time = trim($time);
        if (preg_match('/^\d{2}:\d{2}/', $time, $m)) {
            return substr($m[0], 0, 5).':00';
        }

        return null;
    }

    protected function formatRange(ClassSession $session): string
    {
        $start = $session->start_time ? substr((string) $session->start_time, 0, 5) : '—';
        $end = $session->end_time ? substr((string) $session->end_time, 0, 5) : '—';

        return $start.'–'.$end;
    }
}
