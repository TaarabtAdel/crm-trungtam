<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassSessionJournal extends Model
{
    protected $fillable = [
        'class_session_id',
        'class_id',
        'session_date',
        'class_name',
        'enrollment_count',
        'present_count',
        'absent_count',
        'excused_count',
        'late_count',
        'lesson_title',
        'content',
        'remarks',
        'filled_by',
        'filled_at',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'filled_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class, 'class_session_id');
    }

    public function courseClass(): BelongsTo
    {
        return $this->belongsTo(CourseClass::class, 'class_id');
    }

    public function filledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'filled_by');
    }

    public function isFilled(): bool
    {
        return filled($this->lesson_title) || filled($this->content) || filled($this->remarks);
    }
}
