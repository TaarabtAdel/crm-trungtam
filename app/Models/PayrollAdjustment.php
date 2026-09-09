<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollAdjustment extends Model
{
    protected $fillable = [
        'branch_id', 'scope', 'teacher_id', 'user_id', 'billing_month',
        'type', 'amount', 'note', 'expense_id', 'batch_id', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:0',
        ];
    }

    public static function typeOptions(): array
    {
        return [
            'bonus' => 'Thưởng',
            'penalty' => 'Phạt',
            'advance' => 'Ứng trước',
        ];
    }

    public function typeLabel(): string
    {
        return self::typeOptions()[$this->type] ?? $this->type;
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
