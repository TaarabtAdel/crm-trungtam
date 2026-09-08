<?php

namespace App\Notifications;

use App\Models\Expense;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ExpenseStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Expense $expense,
        public string $action, // approved|rejected|paid
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $expense = $this->expense->loadMissing(['approver', 'teacher']);
        $amount = number_format((float) $expense->amount, 0, ',', '.').' đ';

        [$title, $body, $icon] = match ($this->action) {
            'approved' => [
                'Đề xuất chi đã được duyệt',
                'Khoản '.$amount.' ('.$expense->categoryLabel().') đã được duyệt.',
                'bi-check-circle',
            ],
            'rejected' => [
                'Đề xuất chi bị từ chối',
                'Khoản '.$amount.' ('.$expense->categoryLabel().') đã bị từ chối.',
                'bi-x-circle',
            ],
            'paid' => [
                'Khoản chi đã thanh toán',
                'Khoản '.$amount.' ('.$expense->categoryLabel().') đã được đánh dấu đã chi.',
                'bi-cash-coin',
            ],
            default => [
                'Cập nhật khoản chi',
                'Khoản '.$amount.' có cập nhật trạng thái.',
                'bi-bell',
            ],
        };

        return [
            'title' => $title,
            'body' => $body,
            'url' => route('admin.expenses.index'),
            'icon' => $icon,
            'expense_id' => $expense->id,
            'type' => 'expense_'.$this->action,
        ];
    }
}
