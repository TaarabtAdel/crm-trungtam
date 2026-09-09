<?php

namespace App\Services;

use App\Models\ClassSession;
use App\Models\CourseClass;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\Student;
use App\Support\AppSettings;
use App\Support\NotificationTemplateParser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AttendanceNotificationService
{
    public const TEMPLATE_CODE = 'attendance_alert';

    public function __construct(protected ZaloZnsService $zalo) {}

    /**
     * @param  array<int, string>  $statuses  student_id => status
     */
    public function notifyForClassDate(CourseClass $class, string $sessionDate, array $statuses): int
    {
        $template = NotificationTemplate::findByCode(self::TEMPLATE_CODE);
        if (! $template || (! $template->is_active_email && ! $template->is_active_zalo)) {
            return 0;
        }

        $class->loadMissing(['branch', 'students', 'teacher']);
        $session = ClassSession::query()
            ->where('class_id', $class->id)
            ->whereDate('session_date', $sessionDate)
            ->where('status', '!=', 'cancelled')
            ->first();

        $sent = 0;
        foreach ($statuses as $studentId => $status) {
            if (! in_array($status, ['absent', 'late'], true)) {
                continue;
            }

            $student = $class->students->firstWhere('id', (int) $studentId) ?: Student::find($studentId);
            if (! $student) {
                continue;
            }

            $sent += $this->sendForStudent($template, $class, $sessionDate, $session, $student, $status);
        }

        return $sent;
    }

    protected function sendForStudent(
        NotificationTemplate $template,
        CourseClass $class,
        string $sessionDate,
        ?ClassSession $session,
        Student $student,
        string $status
    ): int {
        $statusLabel = match ($status) {
            'late' => 'Muộn',
            'absent' => 'Vắng',
            default => $status,
        };

        $vars = [
            'student_name' => (string) $student->name,
            'parent_name' => (string) ($student->parent_name ?: ''),
            'class_name' => (string) ($class->name ?: '—'),
            'course_name' => (string) ($class->name ?: '—'),
            'session_date' => Carbon::parse($sessionDate)->format('d/m/Y'),
            'attendance_status' => $statusLabel,
            'branch_name' => (string) ($class->branch?->name ?: ''),
            'center_name' => (string) (AppSettings::get('center_name') ?: config('app.name')),
            'teacher_name' => (string) ($class->teacher?->name ?: ''),
            'room' => (string) ($class->room ?: ''),
            'recipient_name' => (string) ($student->parent_name ?: ('PH '.$student->name)),
        ];

        $recipient = [
            'type' => 'parent',
            'name' => $vars['recipient_name'],
            'email' => $student->parent_email,
            'phone' => $student->parent_phone,
        ];

        $sent = 0;
        if ($template->is_active_email && $this->sendEmail($template, $class, $sessionDate, $session, $student, $recipient, $vars, $status)) {
            $sent++;
        }
        if ($template->is_active_zalo && $this->sendZalo($template, $class, $sessionDate, $session, $student, $recipient, $vars, $status)) {
            $sent++;
        }

        return $sent;
    }

    /**
     * @param  array{type:string,name:string,email:?string,phone:?string}  $recipient
     * @param  array<string, string>  $vars
     */
    protected function sendEmail(
        NotificationTemplate $template,
        CourseClass $class,
        string $sessionDate,
        ?ClassSession $session,
        Student $student,
        array $recipient,
        array $vars,
        string $status
    ): bool {
        if ($this->alreadySent($template->code, 'email', $student->id, $session?->id, $sessionDate, $status)) {
            return false;
        }

        $email = trim((string) ($recipient['email'] ?? ''));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
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
                'class_id' => $class->id,
                'session_date' => $sessionDate,
                'attendance_status' => $status,
                'to' => $email,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Attendance email failed: '.$e->getMessage());
            $this->log($template, 'email', $recipient, $session, $student, 'failed', [
                'class_id' => $class->id,
                'session_date' => $sessionDate,
                'attendance_status' => $status,
            ], null, $e->getMessage());

            return false;
        }
    }

    /**
     * @param  array{type:string,name:string,email:?string,phone:?string}  $recipient
     * @param  array<string, string>  $vars
     */
    protected function sendZalo(
        NotificationTemplate $template,
        CourseClass $class,
        string $sessionDate,
        ?ClassSession $session,
        Student $student,
        array $recipient,
        array $vars,
        string $status
    ): bool {
        if ($this->alreadySent($template->code, 'zalo', $student->id, $session?->id, $sessionDate, $status)) {
            return false;
        }

        $phone = trim((string) ($recipient['phone'] ?? ''));
        if ($phone === '') {
            return false;
        }

        if (! AppSettings::bool('zalo_notify_enabled') || ! AppSettings::bool('zalo_notify_attendance')) {
            $this->log($template, 'zalo', $recipient, $session, $student, 'skipped', [
                'class_id' => $class->id,
                'session_date' => $sessionDate,
                'attendance_status' => $status,
            ], null, 'Chưa bật Zalo điểm danh');

            return false;
        }

        if (! $template->zalo_template_id || ! $this->zalo->isConfigured()) {
            $this->log($template, 'zalo', $recipient, $session, $student, 'skipped', [
                'class_id' => $class->id,
                'session_date' => $sessionDate,
                'attendance_status' => $status,
            ], null, 'Chưa cấu hình Zalo template/token');

            return false;
        }

        $templateData = NotificationTemplateParser::mapZaloParams($template->params_mapping, $vars);
        $result = $this->zalo->sendTemplate(
            $phone,
            (string) $template->zalo_template_id,
            $templateData,
            'att_'.$class->id.'_'.$sessionDate.'_'.$student->id.'_'.$status
        );

        $this->log(
            $template,
            'zalo',
            $recipient,
            $session,
            $student,
            $result['ok'] ? 'sent' : 'failed',
            [
                'class_id' => $class->id,
                'session_date' => $sessionDate,
                'attendance_status' => $status,
                'phone' => $phone,
                'template_data' => $templateData,
            ],
            is_array($result['body'] ?? null) ? json_encode($result['body'], JSON_UNESCAPED_UNICODE) : (string) ($result['body'] ?? ''),
            $result['error'] ?? ($result['ok'] ? null : ($result['message'] ?? 'Zalo failed'))
        );

        return (bool) ($result['ok'] ?? false);
    }

    protected function alreadySent(
        string $code,
        string $channel,
        int $studentId,
        ?int $sessionId,
        string $sessionDate,
        string $status
    ): bool {
        $q = NotificationLog::query()
            ->where('template_code', $code)
            ->where('channel', $channel)
            ->where('student_id', $studentId)
            ->where('recipient_type', 'parent')
            ->whereIn('status', ['sent', 'pending']);

        if ($sessionId) {
            $q->where('class_session_id', $sessionId);
        } else {
            $q->where('request_payload', 'like', '%"session_date":"'.$sessionDate.'"%')
                ->where('request_payload', 'like', '%"attendance_status":"'.$status.'"%');
        }

        return $q->exists();
    }

    /**
     * @param  array{type:string,name:string,email:?string,phone:?string}  $recipient
     * @param  array<string, mixed>|null  $request
     */
    protected function log(
        NotificationTemplate $template,
        string $channel,
        array $recipient,
        ?ClassSession $session,
        Student $student,
        string $status,
        ?array $request = null,
        ?string $response = null,
        ?string $error = null
    ): void {
        NotificationLog::create([
            'template_code' => $template->code,
            'channel' => $channel,
            'recipient_type' => $recipient['type'],
            'recipient_name' => $recipient['name'],
            'recipient_contact' => $channel === 'email' ? ($recipient['email'] ?? null) : ($recipient['phone'] ?? null),
            'class_session_id' => $session?->id,
            'student_id' => $student->id,
            'status' => $status,
            'request_payload' => $request ? json_encode($request, JSON_UNESCAPED_UNICODE) : null,
            'response_body' => $response,
            'error_message' => $error,
        ]);
    }
}
