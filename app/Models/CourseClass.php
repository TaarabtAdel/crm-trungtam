<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseClass extends Model
{
    protected $table = 'classes';

    protected $fillable = [
        'branch_id', 'name', 'code', 'subject_id', 'teacher_id',
        'schedule_days', 'start_time', 'end_time', 'room',
        'max_students', 'start_date', 'end_date', 'tuition_fee', 'tuition_type', 'status',
    ];

    protected function casts(): array
    {
        return [
            'schedule_days' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
            'tuition_fee' => 'decimal:0',
        ];
    }

    public function isMonthlyFee(): bool
    {
        return ($this->tuition_type ?? 'monthly') === 'monthly';
    }

    public function isPerSessionFee(): bool
    {
        return ($this->tuition_type ?? 'monthly') === 'per_session';
    }

    public function tuitionTypeLabel(): string
    {
        return $this->isPerSessionFee() ? 'Theo buổi' : 'Theo tháng';
    }

    public function tuitionDisplay(): string
    {
        $amount = number_format((float) $this->tuition_fee, 0, ',', '.').' đ';

        return $this->isPerSessionFee() ? "{$amount}/buổi" : "{$amount}/tháng";
    }

    public static function statusOptions(): array
    {
        return [
            'active' => 'Đang học',
            'inactive' => 'Ngưng',
            'completed' => 'Kết thúc',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? $this->status;
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'active' => 'class-status-active',
            'inactive' => 'class-status-inactive',
            'completed' => 'class-status-completed',
            default => 'class-status-inactive',
        };
    }

    /**
     * Gợi ý số tiền hóa đơn theo loại học phí + tháng.
     * - monthly: = tuition_fee
     * - per_session: tuition_fee × số buổi completed trong tháng
     */
    public function suggestInvoiceAmount(?string $billingMonth = null): array
    {
        $billingMonth = $billingMonth ?: now()->format('Y-m');
        $unit = (float) $this->tuition_fee;

        if ($this->isMonthlyFee()) {
            return [
                'fee_type' => 'monthly',
                'sessions_count' => null,
                'unit_fee' => $unit,
                'amount' => $unit,
            ];
        }

        [$year, $month] = array_map('intval', explode('-', $billingMonth));
        $sessions = $this->sessions()
            ->where('status', 'completed')
            ->whereYear('session_date', $year)
            ->whereMonth('session_date', $month)
            ->count();

        return [
            'fee_type' => 'per_session',
            'sessions_count' => $sessions,
            'unit_fee' => $unit,
            'amount' => $unit * $sessions,
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'class_student', 'class_id', 'student_id')->withTimestamps();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'class_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ClassSession::class, 'class_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'class_id');
    }
}
