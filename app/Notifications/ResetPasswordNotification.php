<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    public function __construct(public string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $minutes = (int) config('auth.passwords.users.expire', 60);
        $center = \App\Models\Setting::get('center_name', config('app.name'));

        return (new MailMessage)
            ->subject('Đặt lại mật khẩu — '.$center)
            ->greeting('Xin chào '.$notifiable->name.',')
            ->line('Bạn nhận được email này vì hệ thống nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.')
            ->action('Đặt lại mật khẩu', $url)
            ->line('Link có hiệu lực trong '.$minutes.' phút.')
            ->line('Nếu bạn không yêu cầu đặt lại mật khẩu, hãy bỏ qua email này.')
            ->salutation('Trân trọng, '.$center);
    }
}
