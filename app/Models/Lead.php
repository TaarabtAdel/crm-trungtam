<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    protected $fillable = [
        'name', 'phone', 'email',
        'related_name', 'related_phone', 'related_email',
        'source', 'branch_id',
        'expected_revenue', 'assigned_sales_id', 'status',
    ];

    protected function casts(): array
    {
        return ['expected_revenue' => 'decimal:0'];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function assignedSales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_sales_id');
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class);
    }

    public static function statusOptions(): array
    {
        return [
            'new' => 'Mới',
            'contacted' => 'Đã liên hệ',
            'interested' => 'Quan tâm',
            'won' => 'Đã chốt',
            'lost' => 'Thất bại',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? $this->status;
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'new' => 'lead-status-new',
            'contacted' => 'lead-status-contacted',
            'interested' => 'lead-status-interested',
            'won' => 'lead-status-won',
            'lost' => 'lead-status-lost',
            default => 'lead-status-new',
        };
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isSales()) {
            $query->where('assigned_sales_id', $user->id);
        }

        return $query;
    }
}
