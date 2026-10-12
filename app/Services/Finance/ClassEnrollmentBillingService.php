<?php

namespace App\Services\Finance;

use App\Models\ClassSession;
use App\Models\CourseClass;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ClassEnrollmentBillingService
{
    /**
     * @return Collection<int, ClassSession>
     */
    public function sessionsInPeriod(CourseClass $class, string $periodFrom, string $periodTo): Collection
    {
        return $class->sessions()
            ->where('status', '!=', 'cancelled')
            ->whereDate('session_date', '>=', $periodFrom)
            ->whereDate('session_date', '<=', $periodTo)
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * @param  bool  $fallbackCreatedAt  Khi chưa có ngày vào học: dùng ngày gắn lớp (phù hợp học phí). Tắt cho báo cáo điểm danh khi chưa khai báo ngày vào học.
     */
    public function enrollmentFrom(Student $student, CourseClass $class, bool $fallbackCreatedAt = true): ?Carbon
    {
        $pivot = $student->pivot;
        if ($pivot && $this->hasEnrollmentColumns()) {
            if (! empty($pivot->enrolled_from)) {
                return Carbon::parse($pivot->enrolled_from)->startOfDay();
            }
        }
        if ($fallbackCreatedAt && $pivot?->created_at) {
            return Carbon::parse($pivot->created_at)->startOfDay();
        }

        return null;
    }

    /**
     * Ngày vào học cho báo cái điểm danh: chỉ dùng ngày admin khai báo rõ, không dùng ngày gắn lớp mặc định.
     */
    public function enrollmentFromForProgress(Student $student, CourseClass $class): ?Carbon
    {
        if (! $this->hasEnrollmentColumns()) {
            return null;
        }

        $pivot = $student->pivot;
        if (! $pivot || empty($pivot->enrolled_from)) {
            return null;
        }

        $from = Carbon::parse($pivot->enrolled_from)->startOfDay();
        if ($pivot->created_at) {
            $attachedDay = Carbon::parse($pivot->created_at)->startOfDay();
            if ($from->equalTo($attachedDay)) {
                return null;
            }
        }

        return $from;
    }

    public function enrollmentTo(Student $student, CourseClass $class): ?Carbon
    {
        if (! $this->hasEnrollmentColumns()) {
            return null;
        }
        $pivot = $student->pivot;
        if ($pivot && ! empty($pivot->enrolled_to)) {
            return Carbon::parse($pivot->enrolled_to)->endOfDay();
        }

        return null;
    }

    /**
     * Buổi trong kỳ thu mà học viên được tính (theo ngày vào / ngày nghỉ lớp).
     *
     * @return Collection<int, ClassSession>
     */
    public function billableSessionsForStudent(
        CourseClass $class,
        Student $student,
        string $periodFrom,
        string $periodTo
    ): Collection {
        $from = Carbon::parse($periodFrom)->startOfDay();
        $to = Carbon::parse($periodTo)->endOfDay();

        $enrolledFrom = $this->enrollmentFrom($student, $class);
        $enrolledTo = $this->enrollmentTo($student, $class);

        if ($enrolledFrom && $enrolledFrom->gt($to)) {
            return collect();
        }
        if ($enrolledTo && $enrolledTo->lt($from)) {
            return collect();
        }

        $effectiveFrom = $from;
        if ($enrolledFrom && $enrolledFrom->gt($effectiveFrom)) {
            $effectiveFrom = $enrolledFrom;
        }

        $effectiveTo = $to;
        if ($enrolledTo && $enrolledTo->lt($effectiveTo)) {
            $effectiveTo = $enrolledTo;
        }

        if ($effectiveFrom->gt($effectiveTo)) {
            return collect();
        }

        return $this->sessionsInPeriod(
            $class,
            $effectiveFrom->toDateString(),
            $effectiveTo->toDateString()
        );
    }

    public function sessionCountForStudent(
        CourseClass $class,
        Student $student,
        string $periodFrom,
        string $periodTo
    ): int {
        return $this->billableSessionsForStudent($class, $student, $periodFrom, $periodTo)->count();
    }

    /**
     * @return array<int, int> student_id => session count
     */
    public function sessionCountsByStudent(
        CourseClass $class,
        string $periodFrom,
        string $periodTo
    ): array {
        $class->loadMissing(['students' => fn ($q) => $q->orderBy('name')]);
        $map = [];
        foreach ($class->students as $student) {
            $map[(int) $student->id] = $this->sessionCountForStudent($class, $student, $periodFrom, $periodTo);
        }

        return $map;
    }

    public function periodLabel(string $periodFrom, string $periodTo): string
    {
        $f = Carbon::parse($periodFrom)->format('d/m/Y');
        $t = Carbon::parse($periodTo)->format('d/m/Y');

        return $f === $t ? $f : "{$f} – {$t}";
    }

    protected function hasEnrollmentColumns(): bool
    {
        return Schema::hasColumn('class_student', 'enrolled_from');
    }
}
