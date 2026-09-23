<?php

namespace App\Services\Finance;

use App\Models\DebtReminder;
use App\Models\Invoice;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Notifications\DebtReminderNotification;
use App\Services\Tasks\AutoTaskService;
use App\Services\ZaloZnsService;
use App\Support\AppSettings;
use App\Support\NotificationTemplateParser;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class DebtReminderService
{
    public const TEMPLATE_CODE = 'debt_reminder';

    public function __construct(protected ZaloZnsService $zalo) {}

    public function run(int $daysBefore = 3): array
    {
        $stats = ['email' => 0, 'notification' => 0, 'zalo' => 0];

        $invoices = Invoice::with(['student', 'sales', 'courseClass'])
            ->whereIn('status', ['unpaid', 'partial'])
            ->whereNotNull('due_date')
            ->where('remaining_amount', '>', 0)
            ->where(function ($q) use ($daysBefore) {
                $q->whereDate('due_date', '<=', now()->toDateString())
                    ->orWhereDate('due_date', '<=', now()->addDays($daysBefore)->toDateString());
            })
            ->get();

        $template = NotificationTemplate::findByCode(self::TEMPLATE_CODE);

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
                    $this->createDebtTask($invoice);
                } catch (\Throwable $e) {
                    Log::warning('Debt notification failed: '.$e->getMessage());
                }
            } else {
                // Không có sales gắn HĐ: vẫn tạo việc cho kế toán / sales có quyền thu
                try {
                    $this->createDebtTask($invoice);
                } catch (\Throwable $e) {
                    Log::warning('Debt auto-task failed: '.$e->getMessage());
                }
            }

            if ($template && $this->sendZaloToParent($invoice, $template, $payload)) {
                $stats['zalo']++;
            }
        }

        return $stats;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function sendZaloToParent(Invoice $invoice, NotificationTemplate $template, array $payload): bool
    {
        if (! $template->is_active_zalo) {
            return false;
        }

        if (! AppSettings::bool('zalo_notify_enabled') || ! AppSettings::bool('zalo_notify_debt')) {
            return false;
        }

        $student = $invoice->student;
        $phone = trim((string) ($student?->parent_phone ?: ''));
        if ($phone === '' || ! $template->zalo_template_id || ! $this->zalo->isConfigured()) {
            return false;
        }

        $already = DebtReminder::query()
            ->where('invoice_id', $invoice->id)
            ->where('channel', 'zalo')
            ->whereDate('sent_at', now()->toDateString())
            ->exists();
        if ($already) {
            return false;
        }

        $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ';
        $vars = [
            'recipient_name' => (string) ($student->parent_name ?: ('PH '.$student->name)),
            'student_name' => (string) $student->name,
            'class_name' => (string) ($invoice->courseClass?->name ?: '—'),
            'course_name' => (string) ($invoice->courseClass?->name ?: '—'),
            'invoice_code' => (string) $invoice->code,
            'remaining_amount' => $fmt($invoice->remaining_amount),
            'center_name' => (string) (AppSettings::get('center_name') ?: config('app.name')),
            'branch_name' => (string) ($invoice->branch?->name ?: $invoice->courseClass?->branch?->name ?: ''),
            'due_date' => optional($invoice->due_date)->format('d/m/Y') ?: '',
        ];

        $templateData = NotificationTemplateParser::mapZaloParams($template->params_mapping, $vars);
        $result = $this->zalo->sendTemplate(
            $phone,
            (string) $template->zalo_template_id,
            $templateData,
            'debt_'.$invoice->id.'_'.now()->format('Ymd')
        );

        $this->log($invoice, 'zalo', array_merge($payload, [
            'phone' => $phone,
            'ok' => (bool) ($result['ok'] ?? false),
            'error' => $result['error'] ?? null,
        ]));

        NotificationLog::create([
            'template_code' => $template->code,
            'channel' => 'zalo',
            'recipient_type' => 'parent',
            'recipient_name' => $vars['recipient_name'],
            'recipient_contact' => $phone,
            'student_id' => $student->id,
            'status' => ($result['ok'] ?? false) ? 'sent' : 'failed',
            'request_payload' => json_encode([
                'invoice_id' => $invoice->id,
                'template_data' => $templateData,
            ], JSON_UNESCAPED_UNICODE),
            'response_body' => is_array($result['body'] ?? null)
                ? json_encode($result['body'], JSON_UNESCAPED_UNICODE)
                : (string) ($result['body'] ?? ''),
            'error_message' => $result['error'] ?? null,
        ]);

        return (bool) ($result['ok'] ?? false);
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

    protected function createDebtTask(Invoice $invoice): void
    {
        $invoice->loadMissing(['student', 'sales', 'courseClass']);
        $overdue = $invoice->isOverdue();
        $remaining = number_format((float) $invoice->remaining_amount, 0, ',', '.').' đ';
        $student = $invoice->student?->name ?? 'Học viên';
        $title = ($overdue ? 'Thu nợ quá hạn: ' : 'Thu học phí: ').$student.' · HĐ '.$invoice->code;
        $desc = "Còn nợ: {$remaining}\nHạn: ".(optional($invoice->due_date)->format('d/m/Y') ?: '—');
        if ($invoice->courseClass?->name) {
            $desc .= "\nLớp: ".$invoice->courseClass->name;
        }
        $desc .= "\nMở: ".route('admin.invoices.show', $invoice, absolute: false);

        $assignees = collect();
        if ($invoice->sales && $invoice->sales->is_active) {
            $assignees->push($invoice->sales);
        } else {
            $branchId = $invoice->branch_id
                ? (int) $invoice->branch_id
                : ($invoice->courseClass?->branch_id ? (int) $invoice->courseClass->branch_id : null);
            $assignees = $assignees->merge(
                \App\Support\Notifier::recipientsForPermission('finance.invoices.manage', [], $branchId)
                    ->filter(fn ($u) => $u->hasAnyRole('sales', 'accountant', 'admin', 'super_admin'))
                    ->take(5)
            );
        }

        if ($assignees->isEmpty()) {
            return;
        }

        app(AutoTaskService::class)->ensureForUsers(
            $assignees->unique('id'),
            AutoTaskService::SOURCE_INVOICE_DEBT,
            (int) $invoice->id,
            [
                'title' => $title,
                'description' => $desc,
                'priority' => $overdue ? 'urgent' : 'high',
                'due_date' => $invoice->due_date ?: now()->endOfDay(),
                'branch_id' => $invoice->branch_id ?? $invoice->courseClass?->branch_id,
                'status' => 'todo',
            ]
        );
    }
}
