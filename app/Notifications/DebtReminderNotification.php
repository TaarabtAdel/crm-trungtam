<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DebtReminderNotification extends Notification
{
    use Queueable;

    public function __construct(public Invoice $invoice) {}

    public function via(object $notifiable): array
    {
        if ($notifiable instanceof \Illuminate\Notifications\AnonymousNotifiable) {
            return ['mail'];
        }

        $channels = ['database'];
        if (! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $inv = $this->invoice;
        $msg = $inv->isOverdue()
            ? 'Hóa đơn học phí đã quá hạn thanh toán.'
            : 'Hóa đơn học phí sắp đến hạn thanh toán.';

        return (new MailMessage)
            ->subject('Nhắc nợ học phí '.$inv->code)
            ->line($msg)
            ->line('Mã HĐ: '.$inv->code)
            ->line('Học viên: '.($inv->student?->name ?? '—'))
            ->line('Còn nợ: '.number_format((float) $inv->remaining_amount, 0, ',', '.').' đ')
            ->line('Hạn thanh toán: '.optional($inv->due_date)->format('d/m/Y'));
    }

    public function toArray(object $notifiable): array
    {
        $inv = $this->invoice;
        $overdue = $inv->isOverdue();

        return [
            'title' => $overdue ? 'Hóa đơn quá hạn' : 'Nhắc nợ học phí',
            'body' => ($inv->student?->name ?? 'Học viên').' — HĐ '.$inv->code.' còn '
                .number_format((float) $inv->remaining_amount, 0, ',', '.').' đ',
            'url' => route('admin.invoices.show', $inv),
            'icon' => $overdue ? 'bi-exclamation-triangle' : 'bi-alarm',
            'invoice_id' => $inv->id,
            'code' => $inv->code,
            'remaining' => (float) $inv->remaining_amount,
            'due_date' => optional($inv->due_date)->toDateString(),
            'type' => 'debt_reminder',
        ];
    }
}
