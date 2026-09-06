<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentInstallment extends Model
{
    protected $fillable = [
        'invoice_id', 'sequence', 'amount', 'paid_amount', 'due_date', 'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:0',
            'paid_amount' => 'decimal:0',
            'due_date' => 'date',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'installment_id');
    }

    public static function statusOptions(): array
    {
        return [
            'unpaid' => 'Chưa thu',
            'partial' => 'Thu một phần',
            'paid' => 'Đã thu',
            'overdue' => 'Quá hạn',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? $this->status;
    }

    public function remainingAmount(): float
    {
        return max(0, (float) $this->amount - (float) $this->paid_amount);
    }
}
