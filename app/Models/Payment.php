<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $fillable = [
        'invoice_id', 'installment_id', 'amount', 'method',
        'paid_at', 'received_by', 'note', 'receipt_path',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:0',
            'paid_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(PaymentInstallment::class, 'installment_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public static function methodOptions(): array
    {
        return [
            'cash' => 'Tiền mặt',
            'bank_transfer' => 'Chuyển khoản',
            'card' => 'Thẻ',
            'e_wallet' => 'Ví điện tử',
        ];
    }

    public function methodLabel(): string
    {
        return self::methodOptions()[$this->method] ?? $this->method;
    }

    public function approvedRefundTotal(): float
    {
        return (float) $this->refunds()->where('status', 'approved')->sum('amount');
    }

    public function netAmount(): float
    {
        return max(0, (float) $this->amount - $this->approvedRefundTotal());
    }
}
