<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;

class AppSettings
{
    public static function get(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = Setting::get($key, $default ? '1' : '0');

        return in_array((string) $value, ['1', 'true', 'yes', 'on'], true);
    }

    public static function secret(string $key): string
    {
        $raw = (string) Setting::get($key, '');
        if ($raw === '') {
            return '';
        }

        try {
            return Crypt::decryptString($raw);
        } catch (\Throwable) {
            return $raw;
        }
    }

    public static function setSecret(string $key, ?string $plain): void
    {
        if ($plain === null || $plain === '') {
            Setting::set($key, '');

            return;
        }

        Setting::set($key, Crypt::encryptString($plain));
    }

    public static function applyMailConfig(): void
    {
        if (! self::bool('smtp_enabled')) {
            return;
        }

        $host = trim((string) Setting::get('smtp_host', ''));
        if ($host === '') {
            return;
        }

        $encryption = Setting::get('smtp_encryption', 'tls'); // none|tls|ssl
        $port = (int) Setting::get('smtp_port', $encryption === 'ssl' ? 465 : 587);
        $scheme = match ($encryption) {
            'ssl' => 'smtps',
            default => 'smtp',
        };

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.transport', 'smtp');
        Config::set('mail.mailers.smtp.host', $host);
        Config::set('mail.mailers.smtp.port', $port);
        Config::set('mail.mailers.smtp.scheme', $scheme);
        Config::set('mail.mailers.smtp.username', Setting::get('smtp_username') ?: null);
        Config::set('mail.mailers.smtp.password', self::secret('smtp_password') ?: null);

        $fromAddress = Setting::get('smtp_from_address') ?: Setting::get('center_email');
        $fromName = Setting::get('smtp_from_name') ?: Setting::get('center_name', config('app.name'));

        if ($fromAddress) {
            Config::set('mail.from.address', $fromAddress);
            Config::set('mail.from.name', $fromName ?: config('app.name'));
        }
    }

    /** Zalo OA đã đủ info cơ bản chưa (chưa đồng nghĩa đã gửi được API). */
    public static function zaloConfigured(): bool
    {
        return filled(Setting::get('zalo_oa_id')) || filled(Setting::get('zalo_oa_link'));
    }

    /** Master switch + có token OA (dự phòng). */
    public static function zaloNotifyReady(): bool
    {
        return self::zaloZnsReady()
            || (
                self::bool('zalo_notify_enabled')
                && filled(Setting::get('zalo_oa_id'))
                && self::secret('zalo_oa_access_token') !== ''
            );
    }

    /** Đã có App ID + Access Token ZNS để gọi API template. */
    public static function zaloZnsReady(): bool
    {
        $appId = Setting::get('zalo_zns_app_id', Setting::get('zalo_oa_id', ''));
        $token = self::secret('zalo_zns_access_token') ?: self::secret('zalo_oa_access_token');

        return filled($appId) && $token !== '';
    }
}
