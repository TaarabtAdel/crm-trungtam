<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ClassSession extends Model
{
    protected $fillable = [
        'class_id', 'teacher_id', 'session_date', 'start_time', 'end_time', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return ['session_date' => 'date'];
    }

    public function courseClass(): BelongsTo
    {
        return $this->belongsTo(CourseClass::class, 'class_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function journal(): HasOne
    {
        return $this->hasOne(ClassSessionJournal::class, 'class_session_id');
    }

    public function hours(): float
    {
        if (! $this->start_time || ! $this->end_time) {
            return 0;
        }

        $start = strtotime($this->start_time);
        $end = strtotime($this->end_time);

        return max(0, ($end - $start) / 3600);
    }

    /**
     * Đơn giá giờ dạy của buổi: ưu tiên mức lớp, không có thì mức GV của buổi.
     */
    public function teacherPayRate(): float
    {
        $this->loadMissing(['courseClass', 'teacher']);

        $class = $this->courseClass;
        if ($class && $class->hasCustomTeacherRate()) {
            return (float) $class->teacher_hourly_rate;
        }

        return (float) ($this->teacher?->hourly_rate ?? 0);
    }

    public function teacherPayAmount(): float
    {
        return round($this->hours() * $this->teacherPayRate());
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'scheduled' => 'Đã lên lịch',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Hủy',
            default => $this->status,
        };
    }

    /**
     * Thời điểm bắt đầu buổi (session_date + start_time, fallback giờ lớp).
     */
    public function startsAt(): ?\Carbon\Carbon
    {
        if (! $this->session_date) {
            return null;
        }

        $time = $this->start_time ?: $this->courseClass?->start_time;
        if (! $time) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($this->session_date->format('Y-m-d').' '.$time);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Buổi đã qua giờ kết thúc (theo ngày + end_time, fallback start_time / cuối ngày).
     */
    public function hasEnded(): bool
    {
        if (! $this->session_date) {
            return false;
        }

        $date = $this->session_date->format('Y-m-d');
        $end = $this->end_time ?: ($this->start_time ?: '23:59:59');

        return now()->greaterThan(\Carbon\Carbon::parse($date.' '.$end));
    }
}
