<?php

namespace App\Services\Finance;

use App\Models\PayrollAdjustment;
use App\Models\Teacher;
use App\Models\User;
use App\Support\CurrentBranch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PayrollAdjustmentService
{
    public function __construct(
        protected ExpenseService $expenses,
    ) {}

    /**
     * @return array{bonus:float, penalty:float, advance:float}
     */
    public function sumsForTeachers(string $billingMonth): array
    {
        $rows = PayrollAdjustment::query()
            ->tap(fn ($q) => CurrentBranch::apply($q))
            ->where('scope', 'teacher')
            ->where('billing_month', $billingMonth)
            ->whereNotNull('teacher_id')
            ->selectRaw('teacher_id, type, sum(amount) as total')
            ->groupBy('teacher_id', 'type')
            ->get();

        return $this->pivotSums($rows, 'teacher_id');
    }

    /**
     * @return array{bonus:float, penalty:float, advance:float}
     */
    public function sumsForUsers(string $billingMonth): array
    {
        $rows = PayrollAdjustment::query()
            ->tap(fn ($q) => CurrentBranch::apply($q))
            ->where('scope', 'staff')
            ->where('billing_month', $billingMonth)
            ->whereNotNull('user_id')
            ->selectRaw('user_id, type, sum(amount) as total')
            ->groupBy('user_id', 'type')
            ->get();

        return $this->pivotSums($rows, 'user_id');
    }

    /**
     * @return array{bonus:float, penalty:float, advance:float}
     */
    public function sumsForTeacher(int $teacherId, string $billingMonth): array
    {
        return $this->sumsForSubject('teacher', 'teacher_id', $teacherId, $billingMonth);
    }

    /**
     * @return array{bonus:float, penalty:float, advance:float}
     */
    public function sumsForUser(int $userId, string $billingMonth): array
    {
        return $this->sumsForSubject('staff', 'user_id', $userId, $billingMonth);
    }

    public function listForMonth(string $scope, string $billingMonth): Collection
    {
        return PayrollAdjustment::query()
            ->tap(fn ($q) => CurrentBranch::apply($q))
            ->with(['teacher', 'user', 'creator', 'expense'])
            ->where('scope', $scope)
            ->where('billing_month', $billingMonth)
            ->orderByDesc('id')
            ->get();
    }

    public function listForTeacher(int $teacherId, string $billingMonth): Collection
    {
        return PayrollAdjustment::query()
            ->where('scope', 'teacher')
            ->where('teacher_id', $teacherId)
            ->where('billing_month', $billingMonth)
            ->orderBy('id')
            ->get();
    }

    public function listForUser(int $userId, string $billingMonth): Collection
    {
        return PayrollAdjustment::query()
            ->where('scope', 'staff')
            ->where('user_id', $userId)
            ->where('billing_month', $billingMonth)
            ->orderBy('id')
            ->get();
    }

    public function createTeacherAdjustment(
        Teacher $teacher,
        string $billingMonth,
        string $type,
        float $amount,
        string $note,
        User $actor,
        bool $markAdvancePaid = true,
        ?string $batchId = null,
    ): PayrollAdjustment {
        return $this->createAdjustment(
            scope: 'teacher',
            billingMonth: $billingMonth,
            type: $type,
            amount: $amount,
            note: $note,
            actor: $actor,
            teacher: $teacher,
            user: null,
            markAdvancePaid: $markAdvancePaid,
            batchId: $batchId,
        );
    }

    public function createStaffAdjustment(
        User $staff,
        string $billingMonth,
        string $type,
        float $amount,
        string $note,
        User $actor,
        bool $markAdvancePaid = true,
        ?string $batchId = null,
    ): PayrollAdjustment {
        return $this->createAdjustment(
            scope: 'staff',
            billingMonth: $billingMonth,
            type: $type,
            amount: $amount,
            note: $note,
            actor: $actor,
            teacher: null,
            user: $staff,
            markAdvancePaid: $markAdvancePaid,
            batchId: $batchId,
        );
    }

    /**
     * @return array{count:int, batch_id:string}
     */
    public function bulkTeachers(string $billingMonth, string $type, float $amount, string $note, User $actor): array
    {
        if (! in_array($type, ['bonus', 'penalty'], true)) {
            throw ValidationException::withMessages(['type' => 'Chỉ hỗ trợ thưởng/phạt hàng loạt.']);
        }

        $teachers = CurrentBranch::apply(Teacher::query()->where('status', 'active'))
            ->orderBy('name')
            ->get();

        if ($teachers->isEmpty()) {
            throw ValidationException::withMessages(['type' => 'Không có giáo viên active trong chi nhánh.']);
        }

        $batchId = (string) Str::uuid();
        $count = 0;
        DB::transaction(function () use ($teachers, $billingMonth, $type, $amount, $note, $actor, $batchId, &$count) {
            foreach ($teachers as $teacher) {
                $this->createTeacherAdjustment($teacher, $billingMonth, $type, $amount, $note, $actor, true, $batchId);
                $count++;
            }
        });

        return ['count' => $count, 'batch_id' => $batchId];
    }

    /**
     * @return array{count:int, batch_id:string}
     */
    public function bulkStaff(string $billingMonth, string $type, float $amount, string $note, User $actor): array
    {
        if (! in_array($type, ['bonus', 'penalty'], true)) {
            throw ValidationException::withMessages(['type' => 'Chỉ hỗ trợ thưởng/phạt hàng loạt.']);
        }

        $users = CurrentBranch::apply(User::query()->where('is_active', true))
            ->orderBy('name')
            ->get();

        if ($users->isEmpty()) {
            throw ValidationException::withMessages(['type' => 'Không có nhân viên active trong chi nhánh.']);
        }

        $batchId = (string) Str::uuid();
        $count = 0;
        DB::transaction(function () use ($users, $billingMonth, $type, $amount, $note, $actor, $batchId, &$count) {
            foreach ($users as $user) {
                $this->createStaffAdjustment($user, $billingMonth, $type, $amount, $note, $actor, true, $batchId);
                $count++;
            }
        });

        return ['count' => $count, 'batch_id' => $batchId];
    }

    public function delete(PayrollAdjustment $adjustment): void
    {
        DB::transaction(function () use ($adjustment) {
            if ($adjustment->type === 'advance' && $adjustment->expense_id) {
                $expense = $adjustment->expense;
                if ($expense && $expense->status === 'paid') {
                    throw ValidationException::withMessages([
                        'adjustment' => 'Không xóa được: phiếu ứng đã chi. Hủy/điều chỉnh phiếu chi trước.',
                    ]);
                }
                $adjustment->update(['expense_id' => null]);
                if ($expense && in_array($expense->status, ['pending', 'approved', 'rejected'], true)) {
                    $expense->delete();
                }
            }

            $adjustment->delete();
        });
    }

    protected function createAdjustment(
        string $scope,
        string $billingMonth,
        string $type,
        float $amount,
        string $note,
        User $actor,
        ?Teacher $teacher,
        ?User $user,
        bool $markAdvancePaid,
        ?string $batchId,
    ): PayrollAdjustment {
        if (! in_array($type, ['bonus', 'penalty', 'advance'], true)) {
            throw ValidationException::withMessages(['type' => 'Loại điều chỉnh không hợp lệ.']);
        }
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Số tiền phải lớn hơn 0.']);
        }
        $note = trim($note);
        if ($note === '') {
            throw ValidationException::withMessages(['note' => 'Ghi chú là bắt buộc.']);
        }

        return DB::transaction(function () use (
            $scope, $billingMonth, $type, $amount, $note, $actor, $teacher, $user, $markAdvancePaid, $batchId
        ) {
            $branchId = $teacher?->branch_id ?? $user?->branch_id ?? CurrentBranch::id() ?? $actor->branch_id;
            $expenseId = null;

            if ($type === 'advance') {
                $canPayNow = $actor->hasPermission('finance.expenses.pay_immediate') && $markAdvancePaid;
                $expense = $this->expenses->create([
                    'branch_id' => $branchId,
                    'teacher_id' => $teacher?->id,
                    'user_id' => $scope === 'staff' ? $user?->id : null,
                    'billing_month' => $billingMonth,
                    'category' => $scope === 'teacher' ? 'salary_advance' : 'staff_salary_advance',
                    'amount' => $amount,
                    'expense_date' => now()->toDateString(),
                    'note' => $note,
                    'status' => $canPayNow ? 'paid' : 'pending',
                    'approved_by' => $canPayNow ? $actor->id : null,
                ], $actor);
                $expenseId = $expense->id;
            }

            return PayrollAdjustment::create([
                'branch_id' => $branchId,
                'scope' => $scope,
                'teacher_id' => $teacher?->id,
                'user_id' => $scope === 'staff' ? $user?->id : null,
                'billing_month' => $billingMonth,
                'type' => $type,
                'amount' => $amount,
                'note' => $note,
                'expense_id' => $expenseId,
                'batch_id' => $batchId,
                'created_by' => $actor->id,
            ]);
        });
    }

    /**
     * @return array<int, array{bonus:float, penalty:float, advance:float}>
     */
    protected function pivotSums(Collection $rows, string $idKey): array
    {
        $out = [];
        foreach ($rows as $row) {
            $id = (int) $row->{$idKey};
            if (! isset($out[$id])) {
                $out[$id] = ['bonus' => 0.0, 'penalty' => 0.0, 'advance' => 0.0];
            }
            if (isset($out[$id][$row->type])) {
                $out[$id][$row->type] = (float) $row->total;
            }
        }

        return $out;
    }

    /**
     * @return array{bonus:float, penalty:float, advance:float}
     */
    protected function sumsForSubject(string $scope, string $idColumn, int $id, string $billingMonth): array
    {
        $rows = PayrollAdjustment::query()
            ->where('scope', $scope)
            ->where($idColumn, $id)
            ->where('billing_month', $billingMonth)
            ->selectRaw('type, sum(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return [
            'bonus' => (float) ($rows['bonus'] ?? 0),
            'penalty' => (float) ($rows['penalty'] ?? 0),
            'advance' => (float) ($rows['advance'] ?? 0),
        ];
    }

    /**
     * @param  array{bonus?:float, penalty?:float, advance?:float}  $adj
     * @return array{bonus:float, penalty:float, advance:float, net:float, remaining:float}
     */
    public static function applyToAccrued(float $accrued, float $paid, array $adj): array
    {
        $bonus = (float) ($adj['bonus'] ?? 0);
        $penalty = (float) ($adj['penalty'] ?? 0);
        $advance = (float) ($adj['advance'] ?? 0);
        $net = $accrued + $bonus - $penalty - $advance;

        return [
            'bonus' => $bonus,
            'penalty' => $penalty,
            'advance' => $advance,
            'net' => $net,
            'remaining' => max(0, $net - $paid),
        ];
    }
}
