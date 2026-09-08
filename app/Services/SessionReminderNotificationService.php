<?php

namespace App\Services;

use App\Models\ClassSession;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\Setting;
use App\Models\Student;
use App\Support\AppSettings;
use App\Support\NotificationTemplateParser;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SessionReminderNotificationService
{
    public const TEMPLATE_CODE = 'session_reminder';

    public function __construct(protected ZaloZnsService $zalo) {}

    public function sendForSession(ClassSession $session): int
    {
        $session->loadMissing([
            'courseClass.students',
            'courseClass.branch',
            'teacher',
        ]);

        $template = NotificationTemplate::findByCode(self::TEMPLATE_CODE);
        if (! $template) {
            Log::info('Session reminder skipped: no template', ['code' => self::TEMPLATE_CODE]);

            return 0;
        }

        if (! $template->is_active_email && ! $template->is_active_zalo) {
            Log::info('Session reminder skipped: template channels off', ['session_id' => $session->id]);

            return 0;
        }

        $class = $session->courseClass;
        if (! $class) {
            return 0;
        }

        $students = $class->students
            ->filter(fn (Student $s) => ($s->status ?? 'studying') === 'studying');

        $sent = 0;
        foreach ($students as $student) {
            $sent += $this->sendForStudent($template, $session, $student);
        }

        return $sent;
    }

    protected function sendForStudent(
        NotificationTemplate $template,
        ClassSession $session,
        Student $student
    ): int {
        $baseVars = $this->buildVariables($session, $student);
        $sent = 0;

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
                if ($this->sendEmail($template, $session, $student, $recipient, $vars)) {
                    $sent++;
                }
            }

            if ($template->is_active_zalo) {
                if ($this->sendZalo($template, $session, $student, $recipient, $vars)) {
                    $sent++;
                }
            }
        }

        return $sent;
    }

    /**
     * @return array<string, string>
     */
    public function buildVariables(ClassSession $session, Student $student): array
    {
        $class = $session->courseClass;
        $startsAt = $session->startsAt();
        $endTime = $session->end_time ?: $class?->end_time;
        $startDisplay = $startsAt
            ? $startsAt->format('H:i')
            : $this->formatTime($session->start_time ?: $class?->start_time);
        $endDisplay = $this->formatTime($endTime);

        return [
            'student_name' => (string) $student->name,
            'parent_name' => (string) ($student->parent_name ?: ''),
            'class_name' => (string) ($class?->name ?: '—'),
            'course_name' => (string) ($class?->name ?: '—'),
            'session_date' => $session->session_date?->format('d/m/Y') ?: '',
            'start_time' => $startDisplay,
            'end_time' => $endDisplay,
            'room' => (string) ($class?->room ?: '—'),
            'teacher_name' => (string) ($session->teacher?->name ?: $class?->teacher?->name ?: '—'),
            'branch_name' => (string) ($class?->branch?->name ?: ''),
            'center_name' => (string) Setting::get('center_name', Setting::get('logo_text', 'CRM Trung tâm')),
        ];
    }

    protected function formatTime(?string $time): string
    {
        if (! $time) {
            return '—';
        }

        try {
            return \Carbon\Carbon::parse($time)->format('H:i');
        } catch (\Throwable) {
            return substr((string) $time, 0, 5);
        }
    }

    /**
     * @param  array{type: string, name: string, email: ?string, phone: ?string}  $recipient
     * @param  array<string, string>  $vars
     */
    protected function sendEmail(
        NotificationTemplate $template,
        ClassSession $session,
        Student $student,
        array $recipient,
        array $vars
    ): bool {
        if ($this->alreadySent($template->code, 'email', $session->id, $student->id, $recipient['type'])) {
            return false;
        }

        $email = trim((string) ($recipient['email'] ?? ''));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->log($template, 'email', $recipient, $session, $student, 'skipped', null, null, 'Không có email hợp lệ');

            return false;
        }

        AppSettings::applyMailConfig();

        $subject = NotificationTemplateParser::render($template->email_subject ?: $template->title, $vars);
        $body = NotificationTemplateParser::render($template->content_email, $vars);

        try {
            Mail::raw($body, function ($message) use ($email, $subject, $recipient) {
                $message->to($email, $recipient['name'] ?? null)->subject($subject);
            });

            $this->log($template, 'email', $recipient, $session, $student, 'sent', [
                'session_id' => $session->id,
                'subject' => $subject,
                'to' => $email,
            ], 'OK');

            return true;
        } catch (\Throwable $e) {
            Log::error('Session reminder email failed', ['email' => $email, 'error' => $e->getMessage()]);
            $this->log($template, 'email', $recipient, $session, $student, 'failed', [
                'session_id' => $session->id,
                'subject' => $subject,
                'to' => $email,
            ], null, $e->getMessage());

            return false;
        }
    }

    /**
     * @param  array{type: string, name: string, email: ?string, phone: ?string}  $recipient
     * @param  array<string, string>  $vars
     */
    protected function sendZalo(
        NotificationTemplate $template,
        ClassSession $session,
        Student $student,
        array $recipient,
        array $vars
    ): bool {
        if ($this->alreadySent($template->code, 'zalo', $session->id, $student->id, $recipient['type'])) {
            return false;
        }

        $phone = trim((string) ($recipient['phone'] ?? ''));
        if ($phone === '') {
            $this->log($template, 'zalo', $recipient, $session, $student, 'skipped', null, null, 'Không có SĐT');

            return false;
        }

        if (! $template->zalo_template_id) {
            $this->log($template, 'zalo', $recipient, $session, $student, 'skipped', null, null, 'Chưa cấu hình zalo_template_id');

            return false;
        }

        if (! AppSettings::bool('zalo_notify_enabled')) {
            $this->log($template, 'zalo', $recipient, $session, $student, 'skipped', null, null, 'Kênh Zalo đang tắt (Cài đặt)');

            return false;
        }

        if (! AppSettings::bool('zalo_notify_schedule')) {
            $this->log($template, 'zalo', $recipient, $session, $student, 'skipped', null, null, 'Chưa bật “Nhắc lịch học” (Cài đặt)');

            return false;
        }

        if (! $this->zalo->isConfigured()) {
            $this->log($template, 'zalo', $recipient, $session, $student, 'skipped', null, null, 'Chưa cấu hình Zalo ZNS token/app');

            return false;
        }

        $templateData = NotificationTemplateParser::mapZaloParams($template->params_mapping, $vars);
        $result = $this->zalo->sendTemplate(
            $phone,
            (string) $template->zalo_template_id,
            $templateData,
            'session_'.$session->id.'_'.$student->id.'_'.$recipient['type']
        );

        $this->log(
            $template,
            'zalo',
            $recipient,
            $session,
            $student,
            $result['ok'] ? 'sent' : 'failed',
            $result['request'] ?? [
                'session_id' => $session->id,
                'phone' => $phone,
                'template_data' => $templateData,
            ],
            is_array($result['body'] ?? null) ? json_encode($result['body'], JSON_UNESCAPED_UNICODE) : (string) ($result['body'] ?? ''),
            $result['error'] ?? null
        );

        return (bool) ($result['ok'] ?? false);
    }

    protected function alreadySent(
        string $templateCode,
        string $channel,
        int $sessionId,
        int $studentId,
        string $recipientType
    ): bool {
        return NotificationLog::query()
            ->where('template_code', $templateCode)
            ->where('channel', $channel)
            ->where('class_session_id', $sessionId)
            ->where('student_id', $studentId)
            ->where('recipient_type', $recipientType)
            ->whereIn('status', ['sent', 'pending'])
            ->exists();
    }

    /**
     * @param  array{type: string, name: string, email: ?string, phone: ?string}  $recipient
     */
    protected function log(
        NotificationTemplate $template,
        string $channel,
        array $recipient,
        ClassSession $session,
        Student $student,
        string $status,
        mixed $request = null,
        ?string $response = null,
        ?string $error = null
    ): void {
        $payload = $request;
        if (is_array($payload) && ! isset($payload['session_id'])) {
            $payload['session_id'] = $session->id;
        }

        NotificationLog::create([
            'template_code' => $template->code,
            'channel' => $channel,
            'recipient_type' => $recipient['type'],
            'recipient_name' => $recipient['name'],
            'recipient_contact' => $channel === 'email' ? ($recipient['email'] ?? null) : ($recipient['phone'] ?? null),
            'class_session_id' => $session->id,
            'student_id' => $student->id,
            'status' => $status,
            'request_payload' => $payload ? (is_string($payload) ? $payload : json_encode($payload, JSON_UNESCAPED_UNICODE)) : null,
            'response_body' => $response,
            'error_message' => $error,
        ]);
    }
}
