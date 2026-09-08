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
        'branch_id', 'name', 'code', 'subject_id', 'teacher_id', 'teacher_hourly_rate',
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
            'teacher_hourly_rate' => 'decimal:0',
        ];
    }

    /**
     * Đơn giá giờ dạy áp dụng cho lớp.
     * Có ghi đè lớp → dùng; không → mức giáo viên (truyền vào hoặc teacher của lớp).
     */
    public function effectiveTeacherHourlyRate(?Teacher $teacher = null): float
    {
        if ($this->teacher_hourly_rate !== null && $this->teacher_hourly_rate !== '') {
            return (float) $this->teacher_hourly_rate;
        }

        $teacher ??= $this->teacher;

        return (float) ($teacher?->hourly_rate ?? 0);
    }

    public function hasCustomTeacherRate(): bool
    {
        return $this->teacher_hourly_rate !== null && $this->teacher_hourly_rate !== '';
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

    /**
     * Số buổi theo lịch trong tháng (bỏ buổi Hủy).
     */
    public function sessionsCountInMonth(string $billingMonth): int
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $billingMonth)) {
            return 0;
        }

        [$year, $month] = array_map('intval', explode('-', $billingMonth));

        return (int) $this->sessions()
            ->where('status', '!=', 'cancelled')
            ->whereYear('session_date', $year)
            ->whereMonth('session_date', $month)
            ->count();
    }

    /**
     * Toàn bộ buổi theo lịch lớp (bỏ buổi Hủy).
     */
    public function sessionsCountAll(): int
    {
        return (int) $this->sessions()
            ->where('status', '!=', 'cancelled')
            ->count();
    }

    /**
     * Số tháng có buổi trên lịch (dùng gợi ý thu theo khóa khi lớp tính theo tháng).
     */
    public function billingMonthsCount(): int
    {
        $count = $this->sessions()
            ->where('status', '!=', 'cancelled')
            ->selectRaw("COUNT(DISTINCT DATE_FORMAT(session_date, '%Y-%m')) as c")
            ->value('c');

        return max(1, (int) $count);
    }

    /**
     * Buổi theo lịch trong tháng (bỏ Hủy).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ClassSession>
     */
    public function sessionsInMonth(string $billingMonth)
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $billingMonth)) {
            return $this->sessions()->whereRaw('1=0')->get();
        }

        [$year, $month] = array_map('intval', explode('-', $billingMonth));

        return $this->sessions()
            ->where('status', '!=', 'cancelled')
            ->whereYear('session_date', $year)
            ->whereMonth('session_date', $month)
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Gợi ý số tiền hóa đơn theo loại thu + tháng.
     *
     * - Theo tháng: đơn giá × số buổi lịch trong tháng
     * - Theo buổi: chọn từng buổi (mặc định 0 đến khi chọn)
     * - Theo khóa: đơn giá × toàn bộ buổi lịch
     *
     * @return array{
     *   fee_type: string,
     *   sessions_count: int|null,
     *   billing_months_count: int|null,
     *   unit_fee: float,
     *   gross_amount: float,
     *   amount: float,
     *   label: string,
     *   detail: string
     * }
     */
    public function suggestInvoiceAmount(?string $billingMonth = null, ?string $feeType = null): array
    {
        $billingMonth = $billingMonth ?: now()->format('Y-m');
        $feeType = $feeType ?: ($this->isPerSessionFee() ? 'per_session' : 'monthly');
        if (! in_array($feeType, ['monthly', 'per_session', 'course'], true)) {
            $feeType = 'monthly';
        }

        $unit = (float) $this->tuition_fee;
        $monthSessions = $this->sessionsCountInMonth($billingMonth);
        $allSessions = $this->sessionsCountAll();

        return match ($feeType) {
            'per_session' => [
                'fee_type' => 'per_session',
                'sessions_count' => 0,
                'billing_months_count' => null,
                'unit_fee' => $unit,
                'gross_amount' => 0,
                'amount' => 0,
                'label' => 'Theo buổi',
                'detail' => 'Chọn buổi cần thu · '.number_format($unit, 0, ',', '.').' đ/buổi',
            ],
            'course' => [
                'fee_type' => 'course',
                'sessions_count' => $allSessions,
                'billing_months_count' => null,
                'unit_fee' => $unit,
                'gross_amount' => $unit * $allSessions,
                'amount' => $unit * $allSessions,
                'label' => 'Theo khóa',
                'detail' => number_format($unit, 0, ',', '.').' đ × '.$allSessions.' buổi cả khóa',
            ],
            default => [
                'fee_type' => 'monthly',
                'sessions_count' => $monthSessions,
                'billing_months_count' => 1,
                'unit_fee' => $unit,
                'gross_amount' => $unit * $monthSessions,
                'amount' => $unit * $monthSessions,
                'label' => 'Theo tháng',
                'detail' => number_format($unit, 0, ',', '.').' đ × '.$monthSessions.' buổi (tháng '.$billingMonth.')',
            ],
        };
    }

    public static function feeTypeOptions(): array
    {
        return [
            'monthly' => 'Theo tháng',
            'per_session' => 'Theo buổi',
            'course' => 'Theo khóa',
        ];
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
