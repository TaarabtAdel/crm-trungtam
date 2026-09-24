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
        'source', 'interest_subject_id', 'interest_level', 'interest_note', 'branch_id',
        'expected_revenue', 'assigned_sales_id', 'student_id', 'status', 'follow_up_at',
    ];

    protected function casts(): array
    {
        return [
            'expected_revenue' => 'decimal:0',
            'follow_up_at' => 'date',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function interestSubject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'interest_subject_id');
    }

    public function placementTests(): HasMany
    {
        return $this->hasMany(PlacementTest::class);
    }

    public function assignedSales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_sales_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
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

    /**
     * Sales: thấy pool chưa phân bổ + lead được gán cho mình.
     * Admin / role khác: không giới hạn theo phân bổ (vẫn lọc chi nhánh ở controller).
     */
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isSales()) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('assigned_sales_id')
                    ->orWhere('assigned_sales_id', $user->id);
            });
        }

        return $query;
    }

    /**
     * Sales được xem/thao tác lead chưa gán hoặc lead của chính mình.
     */
    public function isAccessibleBySales(User $user): bool
    {
        if (! $user->isSales()) {
            return true;
        }

        if ($this->assigned_sales_id === null) {
            return true;
        }

        return (int) $this->assigned_sales_id === (int) $user->id;
    }
}
