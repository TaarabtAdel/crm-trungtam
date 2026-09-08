<?php

namespace App\Services\Finance;

use App\Models\Expense;
use App\Models\User;
use App\Notifications\ExpenseProposedNotification;
use App\Notifications\ExpenseStatusNotification;
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
            Notifier::toPermission(
                'finance.expenses.approve',
                new ExpenseProposedNotification($expense),
                $user->id
            );
        }

        return $expense;
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

        $expense = $expense->fresh(['creator']);
        if ($expense->creator && (int) $expense->creator->id !== (int) $approver->id) {
            $expense->creator->notify(new ExpenseStatusNotification($expense, 'approved'));
        }

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

        return $expense;
    }

    public function delete(Expense $expense): void
    {
        if ($expense->attachment_path) {
            Storage::disk('public')->delete($expense->attachment_path);
        }
        $expense->delete();
    }
}
