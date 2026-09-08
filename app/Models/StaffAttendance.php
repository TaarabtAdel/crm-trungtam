<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAttendance extends Model
{
    protected $fillable = [
        'user_id', 'work_date', 'status', 'note', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function statusOptions(): array
    {
        return [
            'present' => 'Có mặt',
            'half' => 'Nửa ngày',
            'leave' => 'Nghỉ phép',
            'absent' => 'Vắng',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? $this->status;
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'present' => 'badge-success',
            'half' => 'badge-info',
            'leave' => 'badge-warning',
            'absent' => 'badge-secondary',
            default => 'badge-light',
        };
    }

    /** Hệ số ngày công để tính lương (1 / 0.5 / 0). */
    public function dayUnits(): float
    {
        return match ($this->status) {
            'present' => 1.0,
            'half' => 0.5,
            default => 0.0,
        };
    }

    public function payAmount(?float $dailyRate = null): float
    {
        $rate = $dailyRate ?? (float) ($this->user?->daily_rate ?? 0);

        return round($this->dayUnits() * $rate);
    }
}
