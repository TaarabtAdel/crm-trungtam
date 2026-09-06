<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function hours(): float
    {
        if (! $this->start_time || ! $this->end_time) {
            return 0;
        }

        $start = strtotime($this->start_time);
        $end = strtotime($this->end_time);

        return max(0, ($end - $start) / 3600);
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
}
