<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Invoice extends Model
{
    protected $fillable = [
        'code', 'student_id', 'class_id', 'branch_id', 'sales_id',
        'amount', 'gross_amount', 'discount_amount', 'discount_reason',
        'paid_amount', 'remaining_amount',
        'billing_month', 'sessions_count', 'billed_session_ids', 'fee_type', 'installment_count',
        'status', 'due_date', 'paid_at', 'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:0',
            'gross_amount' => 'decimal:0',
            'discount_amount' => 'decimal:0',
            'paid_amount' => 'decimal:0',
            'remaining_amount' => 'decimal:0',
            'billed_session_ids' => 'array',
            'due_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    public function feeTypeLabel(): string
    {
        return match ($this->fee_type) {
            'per_session' => 'Theo buổi',
            'monthly' => 'Theo tháng',
            'course' => 'Theo khóa',
            default => '—',
        };
    }

    public static function statusOptions(): array
    {
        return [
            'unpaid' => 'Chưa thu',
            'partial' => 'Thu một phần',
            'paid' => 'Đã thu',
            'cancelled' => 'Hủy',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? $this->status;
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'paid' => 'lead-status-won',
            'partial' => 'lead-status-interested',
            'cancelled' => 'lead-status-lost',
            default => 'lead-status-new',
        };
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function courseClass(): BelongsTo
    {
        return $this->belongsTo(CourseClass::class, 'class_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(PaymentInstallment::class)->orderBy('sequence');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('paid_at');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(DebtReminder::class);
    }

    public function refunds(): HasManyThrough
    {
        return $this->hasManyThrough(Refund::class, Payment::class);
    }

    public function isOverdue(): bool
    {
        return in_array($this->status, ['unpaid', 'partial'], true)
            && $this->due_date
            && $this->due_date->isPast();
    }

    /**
     * Không cho xóa khi đã có thu (đã thu / thu một phần).
     */
    public function canBeDeleted(): bool
    {
        if (in_array($this->status, ['paid', 'partial'], true)) {
            return false;
        }

        return (float) $this->paid_amount <= 0;
    }

    public function daysOverdue(): int
    {
        if (! $this->isOverdue()) {
            return 0;
        }

        return (int) $this->due_date->diffInDays(now());
    }
}
