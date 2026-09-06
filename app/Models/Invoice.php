<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'student_id', 'class_id', 'amount', 'billing_month',
        'sessions_count', 'fee_type',
        'status', 'due_date', 'paid_at', 'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:0',
            'due_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    public function feeTypeLabel(): string
    {
        return match ($this->fee_type) {
            'per_session' => 'Theo buổi',
            'monthly' => 'Theo tháng',
            default => '—',
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
}
