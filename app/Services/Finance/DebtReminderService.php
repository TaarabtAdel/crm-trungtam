<?php

namespace App\Services\Finance;

use App\Models\DebtReminder;
use App\Models\Invoice;
use App\Notifications\DebtReminderNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class DebtReminderService
{
    public function run(int $daysBefore = 3): array
    {
        $stats = ['email' => 0, 'notification' => 0, 'sms' => 0];

        $invoices = Invoice::with(['student', 'sales'])
            ->whereIn('status', ['unpaid', 'partial'])
            ->whereNotNull('due_date')
            ->where('remaining_amount', '>', 0)
            ->where(function ($q) use ($daysBefore) {
                $q->whereDate('due_date', '<=', now()->toDateString())
                    ->orWhereDate('due_date', '<=', now()->addDays($daysBefore)->toDateString());
            })
            ->get();

        foreach ($invoices as $invoice) {
            $payload = [
                'invoice_code' => $invoice->code,
                'student' => $invoice->student?->name,
                'remaining' => (float) $invoice->remaining_amount,
                'due_date' => optional($invoice->due_date)->toDateString(),
                'overdue_days' => $invoice->daysOverdue(),
            ];

            if ($invoice->student?->parent_email) {
                try {
                    Notification::route('mail', $invoice->student->parent_email)
                        ->notify(new DebtReminderNotification($invoice));
                    $this->log($invoice, 'email', $payload);
                    $stats['email']++;
                } catch (\Throwable $e) {
                    Log::warning('Debt email failed: '.$e->getMessage());
                }
            }

            if ($invoice->sales) {
                try {
                    $invoice->sales->notify(new DebtReminderNotification($invoice));
                    $this->log($invoice, 'notification', $payload);
                    $stats['notification']++;
                } catch (\Throwable $e) {
                    Log::warning('Debt notification failed: '.$e->getMessage());
                }
            }

            // SMS stub
            $this->log($invoice, 'sms', array_merge($payload, [
                'phone' => $invoice->student?->parent_phone,
                'stub' => true,
                'message' => 'Nhắc nợ học phí '.$invoice->code,
            ]));
            $stats['sms']++;
        }

        return $stats;
    }

    protected function log(Invoice $invoice, string $channel, array $payload): void
    {
        DebtReminder::create([
            'invoice_id' => $invoice->id,
            'channel' => $channel,
            'sent_at' => now(),
            'payload' => $payload,
        ]);
    }
}
