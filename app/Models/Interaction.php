<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interaction extends Model
{
    protected $fillable = [
        'lead_id', 'sales_id', 'branch_id', 'type', 'scheduled_at', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime'];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public static function statusOptions(): array
    {
        return [
            'upcoming' => 'Sắp diễn ra',
            'done' => 'Hoàn thành',
            'cancelled' => 'Hủy',
            'no_show' => 'Không đến',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? $this->status;
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'done' => 'success',
            'cancelled' => 'secondary',
            'no_show' => 'warning',
            default => 'info',
        };
    }
}
