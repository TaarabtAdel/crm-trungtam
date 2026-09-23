<?php

namespace App\Services\Finance;

use App\Models\Expense;
use App\Models\User;
use App\Notifications\ExpenseProposedNotification;
use App\Notifications\ExpenseStatusNotification;
use App\Services\Tasks\AutoTaskService;
use App\Support\Notifier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ExpenseService
{
    public function create(array $data, User $user, ?UploadedFile $file = null): Expense
    {
        $path = null;
        if ($file) {
            $path = $file->store('finance/expenses', 'public');
        }

        $expense = Expense::create([
            'branch_id' => $data['branch_id'] ?? $user->branch_id,
            'teacher_id' => $data['teacher_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'billing_month' => $data['billing_month'] ?? null,
            'category' => $data['category'] ?? 'other',
            'amount' => $data['amount'],
            'expense_date' => $data['expense_date'] ?? now()->toDateString(),
            'created_by' => $user->id,
            'approved_by' => $data['approved_by'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'note' => $data['note'] ?? null,
            'attachment_path' => $path,
        ]);

        if ($expense->status === 'pending') {
            $this->notifyProposalAndCreateTasks($expense, $user);
        }

        return $expense;
    }

    /**
     * Thông báo + tạo việc đã công bố cho người có quyền duyệt đề xuất chi.
     */
    protected function notifyProposalAndCreateTasks(Expense $expense, User $proposer): void
    {
        $expense->loadMissing(['teacher', 'creator']);

        $branchId = $expense->branch_id ? (int) $expense->branch_id : null;

        $approvers = Notifier::recipientsForPermission('finance.expenses.approve', [], $branchId)
            ->filter(fn (User $u) => (int) $u->id !== (int) $proposer->id)
            ->values();

        // Nếu không còn ai khác: vẫn tạo việc cho người có quyền duyệt (kể cả người đề xuất)
        // để đề xuất không bị “mất” trên board Công việc.
        if ($approvers->isEmpty()) {
            $approvers = Notifier::recipientsForPermission('finance.expenses.approve', [], $branchId)->values();
        }

        // Bổ sung kế toán / người quản lý chi (xem được đề xuất) làm người liên quan trên việc
        $related = Notifier::recipientsForPermission('finance.expenses.manage', [], $branchId)
            ->filter(fn (User $u) => (int) $u->id !== (int) $proposer->id)
            ->merge($approvers)
            ->unique('id')
            ->values();

        $notifyTargets = $approvers->isNotEmpty()
            ? $approvers
            : $related;

        foreach ($notifyTargets as $user) {
            if ((int) $user->id === (int) $proposer->id && $approvers->contains('id', $proposer->id) && $approvers->count() === 1) {
                // Một mình tự đề xuất: vẫn nhận thông báo để theo dõi
            }
            $user->notify(new ExpenseProposedNotification($expense));
        }

        if ($related->isEmpty()) {
            \Illuminate\Support\Facades\Log::warning('Expense proposal: không có người nhận việc/thông báo', [
                'expense_id' => $expense->id,
                'proposer_id' => $proposer->id,
            ]);

            return;
        }

        try {
            $amount = number_format((float) $expense->amount, 0, ',', '.').' đ';
            $detail = $expense->categoryLabel();
            if ($expense->teacher) {
                $detail .= ' — '.$expense->teacher->name;
            }

            $assignees = $approvers->isNotEmpty() ? $approvers : $related;
            $watcherIds = $related
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->reject(fn ($id) => $assignees->contains('id', $id))
                ->values()
                ->all();

            foreach ($assignees as $assignee) {
                $watchersForThis = collect($watcherIds)
                    ->reject(fn ($id) => (int) $id === (int) $assignee->id)
                    ->values()
                    ->all();

                app(AutoTaskService::class)->ensureForUser(
                    $assignee,
                    AutoTaskService::SOURCE_EXPENSE_APPROVE,
                    (int) $expense->id,
                    [
                        'title' => 'Duyệt đề xuất chi: '.$amount,
                        'description' => $proposer->name.' đề xuất chi '.$amount.' ('.$detail.').'
                            .($expense->note ? "\nGhi chú: ".$expense->note : '')
                            ."\nMở: ".route('admin.expenses.index', ['status' => 'pending'], false),
                        'priority' => 'high',
                        'due_date' => now()->endOfDay(),
                        'branch_id' => $expense->branch_id,
                        'creator_id' => $proposer->id,
                        'status' => 'todo',
                        'watcher_ids' => $watchersForThis,
                    ]
                );
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Expense proposal auto-task failed: '.$e->getMessage(), [
                'expense_id' => $expense->id,
                'exception' => $e,
            ]);
            report($e);
        }
    }

    public function approve(Expense $expense, User $approver): Expense
    {
        if ($expense->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Khoản chi đã xử lý.']);
        }

        $expense->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
        ]);

        $expense = $expense->fresh(['creator', 'teacher', 'approver']);
        if ($expense->creator && (int) $expense->creator->id !== (int) $approver->id) {
            $expense->creator->notify(new ExpenseStatusNotification($expense, 'approved'));
        }

        $this->completeApproveTask($expense, $approver->id);
        $this->createPayTask($expense, $approver);

        return $expense;
    }

    public function reject(Expense $expense, User $approver): Expense
    {
        if ($expense->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Khoản chi đã xử lý.']);
        }

        $expense->update([
            'status' => 'rejected',
            'approved_by' => $approver->id,
        ]);

        $expense = $expense->fresh(['creator']);
        if ($expense->creator && (int) $expense->creator->id !== (int) $approver->id) {
            $expense->creator->notify(new ExpenseStatusNotification($expense, 'rejected'));
        }

        $this->completeApproveTask($expense, $approver->id);

        return $expense;
    }

    public function markPaid(Expense $expense): Expense
    {
        if (! in_array($expense->status, ['approved', 'paid'], true)) {
            throw ValidationException::withMessages(['status' => 'Chỉ chi các khoản đã duyệt.']);
        }

        $expense->update(['status' => 'paid']);
        $expense = $expense->fresh(['creator']);

        $actorId = auth()->id();
        if ($expense->creator && (int) $expense->creator->id !== (int) $actorId) {
            $expense->creator->notify(new ExpenseStatusNotification($expense, 'paid'));
        }

        $this->completePayTask($expense, $actorId);

        return $expense;
    }

    public function delete(Expense $expense): void
    {
        if ($expense->attachment_path) {
            Storage::disk('public')->delete($expense->attachment_path);
        }
        try {
            $auto = app(AutoTaskService::class);
            $auto->completeBySource(AutoTaskService::SOURCE_EXPENSE_APPROVE, (int) $expense->id);
            $auto->completeBySource(AutoTaskService::SOURCE_EXPENSE_PAY, (int) $expense->id);
        } catch (\Throwable $e) {
            report($e);
        }
        $expense->delete();
    }

    protected function completeApproveTask(Expense $expense, ?int $actorId = null): void
    {
        try {
            app(AutoTaskService::class)->completeBySource(
                AutoTaskService::SOURCE_EXPENSE_APPROVE,
                (int) $expense->id,
                $actorId
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function completePayTask(Expense $expense, ?int $actorId = null): void
    {
        try {
            app(AutoTaskService::class)->completeBySource(
                AutoTaskService::SOURCE_EXPENSE_PAY,
                (int) $expense->id,
                $actorId
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Sau khi GĐ duyệt: tạo việc nhắc kế toán thực chi (không giao cho người duyệt).
     */
    protected function createPayTask(Expense $expense, User $approver): void
    {
        try {
            $expense->loadMissing(['creator', 'teacher']);
            $amount = number_format((float) $expense->amount, 0, ',', '.').' đ';
            $detail = $expense->categoryLabel();
            if ($expense->teacher) {
                $detail .= ' — '.$expense->teacher->name;
            }

            $assignee = $this->resolvePayAssignee($expense, $approver);
            if (! $assignee) {
                \Illuminate\Support\Facades\Log::warning('Expense pay task: không tìm được kế toán để giao việc', [
                    'expense_id' => $expense->id,
                ]);

                return;
            }

            $branchId = $expense->branch_id ? (int) $expense->branch_id : null;

            // Người liên quan: kế toán khác + người duyệt (theo dõi), không giao việc chính
            $watcherIds = Notifier::recipientsForPermission('finance.expenses.manage', [], $branchId)
                ->filter(fn (User $u) => $u->hasAnyRole('accountant', 'admin', 'super_admin'))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->push((int) $approver->id)
                ->reject(fn ($id) => $id <= 0 || (int) $id === (int) $assignee->id)
                ->unique()
                ->values()
                ->all();

            app(AutoTaskService::class)->ensureForUser(
                $assignee,
                AutoTaskService::SOURCE_EXPENSE_PAY,
                (int) $expense->id,
                [
                    'title' => 'Thực chi: '.$amount,
                    'description' => 'Đề xuất chi đã được '.($approver->name).' duyệt — vui lòng tiến hành chi và đánh dấu Đã chi.'
                        ."\nKhoản: {$amount} ({$detail})"
                        .($expense->note ? "\nGhi chú: ".$expense->note : '')
                        ."\nMở: ".route('admin.expenses.index', ['status' => 'approved'], false),
                    'priority' => 'high',
                    'due_date' => now()->endOfDay(),
                    'branch_id' => $expense->branch_id,
                    'creator_id' => $approver->id,
                    'status' => 'todo',
                    'watcher_ids' => $watcherIds,
                ]
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Expense pay auto-task failed: '.$e->getMessage(), [
                'expense_id' => $expense->id,
                'exception' => $e,
            ]);
            report($e);
        }
    }

    /**
     * Người thực hiện việc "Thực chi" = kế toán (ưu tiên người đề xuất nếu là KT).
     */
    protected function resolvePayAssignee(Expense $expense, User $approver): ?User
    {
        $branchId = $expense->branch_id ? (int) $expense->branch_id : null;
        $managers = Notifier::recipientsForPermission('finance.expenses.manage', [], $branchId);
        $accountants = $managers
            ->filter(fn (User $u) => $u->is_active && $u->hasAnyRole('accountant'))
            ->values();

        // 1) Người đề xuất là kế toán → giao cho họ
        if ($expense->creator
            && $expense->creator->is_active
            && $expense->creator->hasAnyRole('accountant')) {
            return $expense->creator;
        }

        // 2) Kế toán khác (không lấy người vừa duyệt)
        $otherKt = $accountants
            ->filter(fn (User $u) => (int) $u->id !== (int) $approver->id)
            ->values();
        if ($otherKt->isNotEmpty()) {
            return $otherKt->first();
        }

        // 3) Còn kế toán (kể cả trùng người duyệt — trường hợp hiếm)
        if ($accountants->isNotEmpty()) {
            return $accountants->first();
        }

        // 4) Fallback: người manage chi, không phải người duyệt
        $fallback = $managers
            ->filter(fn (User $u) => $u->is_active && (int) $u->id !== (int) $approver->id)
            ->values();
        if ($fallback->isNotEmpty()) {
            return $fallback->first();
        }

        // 5) Người đề xuất (nếu còn)
        if ($expense->creator && $expense->creator->is_active) {
            return $expense->creator;
        }

        return null;
    }
}
