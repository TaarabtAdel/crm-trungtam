<?php

namespace App\Notifications;

use App\Models\Expense;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ExpenseProposedNotification extends Notification
{
    use Queueable;

    public function __construct(public Expense $expense) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $expense = $this->expense->loadMissing(['creator', 'teacher', 'branch']);
        $amount = number_format((float) $expense->amount, 0, ',', '.').' đ';
        $who = $expense->creator?->name ?? 'Ai đó';
        $detail = $expense->categoryLabel();
        if ($expense->teacher) {
            $detail .= ' — '.$expense->teacher->name;
        }

        return [
            'title' => 'Đề xuất chi mới',
            'body' => "{$who} đề xuất chi {$amount} ({$detail}).",
            'url' => route('admin.expenses.index', ['status' => 'pending']),
            'icon' => 'bi-wallet2',
            'expense_id' => $expense->id,
            'type' => 'expense_proposed',
        ];
    }
}
