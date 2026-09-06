<?php

namespace App\Services\Finance;

use App\Models\Expense;
use App\Models\User;
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

        return Expense::create([
            'branch_id' => $data['branch_id'] ?? $user->branch_id,
            'category' => $data['category'] ?? 'other',
            'amount' => $data['amount'],
            'expense_date' => $data['expense_date'] ?? now()->toDateString(),
            'created_by' => $user->id,
            'status' => 'pending',
            'note' => $data['note'] ?? null,
            'attachment_path' => $path,
        ]);
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

        return $expense->fresh();
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

        return $expense->fresh();
    }

    public function markPaid(Expense $expense): Expense
    {
        if (! in_array($expense->status, ['approved', 'paid'], true)) {
            throw ValidationException::withMessages(['status' => 'Chỉ chi các khoản đã duyệt.']);
        }

        $expense->update(['status' => 'paid']);

        return $expense->fresh();
    }

    public function delete(Expense $expense): void
    {
        if ($expense->attachment_path) {
            Storage::disk('public')->delete($expense->attachment_path);
        }
        $expense->delete();
    }
}
