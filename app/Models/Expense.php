<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = [
        'branch_id', 'teacher_id', 'user_id', 'billing_month', 'category', 'amount', 'expense_date',
        'created_by', 'approved_by', 'status', 'note', 'attachment_path',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:0',
            'expense_date' => 'date',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function staffUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public static function categoryOptions(): array
    {
        return [
            'operations' => 'Vận hành',
            'salary' => 'Lương GV',
            'salary_advance' => 'Ứng lương GV',
            'staff_salary' => 'Lương nhân viên',
            'staff_salary_advance' => 'Ứng lương NV',
            'marketing' => 'Marketing',
            'other' => 'Khác',
        ];
    }

    public function categoryLabel(): string
    {
        return self::categoryOptions()[$this->category] ?? $this->category;
    }

    public static function statusOptions(): array
    {
        return [
            'pending' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'rejected' => 'Từ chối',
            'paid' => 'Đã chi',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? $this->status;
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'approved' => 'lead-status-won',
            'paid' => 'lead-status-contacted',
            'rejected' => 'lead-status-lost',
            default => 'lead-status-interested',
        };
    }
}
