<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Student;
use App\Support\AppSettings;
use App\Support\NotificationTemplateParser;
use App\Support\VietnameseCurrency;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaymentNotificationService
{
    public const TEMPLATE_CODE = 'payment_success';

    public function __construct(protected ZaloZnsService $zalo) {}

    public function sendForPayment(Payment $payment): void
    {
        $payment->loadMissing(['invoice.student', 'invoice.courseClass', 'invoice.branch']);

        $template = NotificationTemplate::findByCode(self::TEMPLATE_CODE);
        if (! $template) {
            Log::info('Payment notification skipped: no template', ['code' => self::TEMPLATE_CODE]);

            return;
        }

        $invoice = $payment->invoice;
        $student = $invoice?->student;
        if (! $student) {
            Log::warning('Payment notification skipped: no student', ['payment_id' => $payment->id]);

            return;
        }

        $baseVars = $this->buildVariables($payment, $student);

        $recipients = [
            [
                'type' => 'student',
                'name' => $student->name,
                'email' => $student->email,
                'phone' => $student->phone,
            ],
            [
                'type' => 'parent',
                'name' => $student->parent_name ?: ('PH '.$student->name),
                'email' => $student->parent_email,
                'phone' => $student->parent_phone,
            ],
        ];

        foreach ($recipients as $recipient) {
            $vars = array_merge($baseVars, [
                'recipient_name' => $recipient['name'],
            ]);

            if ($template->is_active_email) {
                $this->sendEmail($template, $payment, $student, $recipient, $vars);
            }

            if ($template->is_active_zalo) {
                $this->sendZalo($template, $payment, $student, $recipient, $vars);
            }
        }
    }

    /**
     * @return array<string, string>
     */
    public function buildVariables(Payment $payment, Student $student): array
    {
        $invoice = $payment->invoice;
        $className = $invoice?->courseClass?->name ?: '—';
        $amount = (float) $payment->amount;
        $paidAt = $payment->paid_at ?? now();

        return [
            'student_name' => (string) $student->name,
            'parent_name' => (string) ($student->parent_name ?: ''),
            'class_name' => $className,
            'course_name' => $className,
            'invoice_code' => (string) ($invoice?->code ?: ''),
            'payment_code' => (string) ($invoice?->code ?: ''),
            'amount' => (string) (int) $amount,
            'amount_formatted' => VietnameseCurrency::format($amount),
            'paid_at' => $paidAt->format('d/m/Y H:i'),
            'date' => $paidAt->format('d/m/Y'),
            'payment_method' => $payment->methodLabel(),
            'remaining_amount' => VietnameseCurrency::format($invoice?->remaining_amount ?? 0),
            'branch_name' => (string) ($invoice?->branch?->name ?: ''),
            'center_name' => (string) Setting::get('center_name', Setting::get('logo_text', 'CRM Trung tâm')),
        ];
    }

    /**
     * @param  array{type: string, name: string, email: ?string, phone: ?string}  $recipient
     * @param  array<string, string>  $vars
     */
    protected function sendEmail(
        NotificationTemplate $template,
        Payment $payment,
        Student $student,
        array $recipient,
        array $vars
    ): void {
        $email = trim((string) ($recipient['email'] ?? ''));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->log($template, 'email', $recipient, $payment, $student, 'skipped', null, null, 'Không có email hợp lệ');

            return;
        }

        if (! AppSettings::bool('smtp_enabled') && config('mail.default') === 'log') {
            // vẫn gửi qua mailer hiện tại (log/smtp) — không chặn hoàn toàn
        }

        AppSettings::applyMailConfig();

        $subject = NotificationTemplateParser::render($template->email_subject ?: $template->title, $vars);
        $body = NotificationTemplateParser::render($template->content_email, $vars);

        try {
            Mail::raw($body, function ($message) use ($email, $subject, $recipient) {
                $message->to($email, $recipient['name'] ?? null)->subject($subject);
            });

            $this->log($template, 'email', $recipient, $payment, $student, 'sent', [
                'subject' => $subject,
                'to' => $email,
            ], 'OK');
        } catch (\Throwable $e) {
            Log::error('Payment email failed', ['email' => $email, 'error' => $e->getMessage()]);
            $this->log($template, 'email', $recipient, $payment, $student, 'failed', [
                'subject' => $subject,
                'to' => $email,
            ], null, $e->getMessage());
        }
    }

    /**
     * @param  array{type: string, name: string, email: ?string, phone: ?string}  $recipient
     * @param  array<string, string>  $vars
     */
    protected function sendZalo(
        NotificationTemplate $template,
        Payment $payment,
        Student $student,
        array $recipient,
        array $vars
    ): void {
        $phone = trim((string) ($recipient['phone'] ?? ''));
        if ($phone === '') {
            $this->log($template, 'zalo', $recipient, $payment, $student, 'skipped', null, null, 'Không có SĐT');

            return;
        }

        if (! $template->zalo_template_id) {
            $this->log($template, 'zalo', $recipient, $payment, $student, 'skipped', null, null, 'Chưa cấu hình zalo_template_id');

            return;
        }

        if (! AppSettings::bool('zalo_notify_enabled')) {
            $this->log($template, 'zalo', $recipient, $payment, $student, 'skipped', null, null, 'Kênh Zalo đang tắt (Cài đặt)');

            return;
        }

        if (! AppSettings::bool('zalo_notify_payment')) {
            $this->log($template, 'zalo', $recipient, $payment, $student, 'skipped', null, null, 'Chưa bật “Thanh toán học phí thành công” (Cài đặt)');

            return;
        }

        if (! $this->zalo->isConfigured()) {
            $this->log($template, 'zalo', $recipient, $payment, $student, 'skipped', null, null, 'Chưa cấu hình Zalo ZNS token/app');

            return;
        }

        $templateData = NotificationTemplateParser::mapZaloParams($template->params_mapping, $vars);
        $result = $this->zalo->sendTemplate(
            $phone,
            (string) $template->zalo_template_id,
            $templateData,
            'payment_'.$payment->id.'_'.$recipient['type']
        );

        $this->log(
            $template,
            'zalo',
            $recipient,
            $payment,
            $student,
            $result['ok'] ? 'sent' : 'failed',
            $result['request'] ?? ['phone' => $phone, 'template_data' => $templateData],
            is_array($result['body'] ?? null) ? json_encode($result['body'], JSON_UNESCAPED_UNICODE) : (string) ($result['body'] ?? ''),
            $result['error'] ?? null
        );
    }

    /**
     * @param  array{type: string, name: string, email: ?string, phone: ?string}  $recipient
     */
    protected function log(
        NotificationTemplate $template,
        string $channel,
        array $recipient,
        Payment $payment,
        Student $student,
        string $status,
        mixed $request = null,
        ?string $response = null,
        ?string $error = null
    ): void {
        NotificationLog::create([
            'template_code' => $template->code,
            'channel' => $channel,
            'recipient_type' => $recipient['type'],
            'recipient_name' => $recipient['name'],
            'recipient_contact' => $channel === 'email' ? ($recipient['email'] ?? null) : ($recipient['phone'] ?? null),
            'payment_id' => $payment->id,
            'student_id' => $student->id,
            'status' => $status,
            'request_payload' => $request ? (is_string($request) ? $request : json_encode($request, JSON_UNESCAPED_UNICODE)) : null,
            'response_body' => $response,
            'error_message' => $error,
        ]);
    }
}
