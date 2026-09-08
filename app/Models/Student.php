<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
        'branch_id', 'name', 'dob', 'gender', 'phone', 'email',
        'parent_phone', 'parent_name', 'parent_email', 'status', 'address', 'notes',
    ];

    protected function casts(): array
    {
        return ['dob' => 'date'];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(CourseClass::class, 'class_student', 'student_id', 'class_id')->withTimestamps();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public static function statusOptions(): array
    {
        return [
            'studying' => 'Đang học',
            'paused' => 'Bảo lưu',
            'graduated' => 'Hoàn thành',
            'dropped' => 'Nghỉ',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? $this->status;
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'studying' => 'student-status-studying',
            'paused' => 'student-status-paused',
            'graduated' => 'student-status-graduated',
            'dropped' => 'student-status-dropped',
            default => 'student-status-paused',
        };
    }
}
