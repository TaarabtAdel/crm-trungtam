<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    protected $fillable = [
        'branch_id', 'name', 'email', 'phone', 'specialty',
        'qualification', 'hourly_rate', 'joined_at', 'notes', 'status',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'date',
            'hourly_rate' => 'decimal:0',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(CourseClass::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ClassSession::class);
    }

    public static function statusOptions(): array
    {
        return [
            'active' => 'Hoạt động',
            'inactive' => 'Ngưng',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? $this->status;
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'active' => 'teacher-status-active',
            'inactive' => 'teacher-status-inactive',
            default => 'teacher-status-inactive',
        };
    }
}
