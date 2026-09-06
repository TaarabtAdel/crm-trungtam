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

    public static function typeOptions(): array
    {
        return [
            'Cuộc gọi' => 'Cuộc gọi',
            'Lịch hẹn Test' => 'Lịch hẹn Test',
            'Nhắn tin' => 'Nhắn tin',
            'Gặp trực tiếp' => 'Gặp trực tiếp',
        ];
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
            'done' => 'interaction-status-done',
            'cancelled' => 'interaction-status-cancelled',
            'no_show' => 'interaction-status-noshow',
            default => 'interaction-status-upcoming',
        };
    }

    public function typeIcon(): string
    {
        return match ($this->type) {
            'Cuộc gọi' => 'telephone',
            'Lịch hẹn Test' => 'clipboard-check',
            'Nhắn tin' => 'chat-dots',
            'Gặp trực tiếp' => 'people',
            default => 'calendar-event',
        };
    }
}
