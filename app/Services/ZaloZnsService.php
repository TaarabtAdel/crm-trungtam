<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\AppSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ZaloZnsService
{
    protected string $sendUrl = 'https://business.openapi.zalo.me/message/template';

    protected string $tokenUrl = 'https://oauth.zaloapp.com/v4/oa/access_token';

    /**
     * @param  array<string, string>  $templateData
     * @return array{ok: bool, status: int, body: array|string, error?: string}
     */
    public function sendTemplate(string $phone, string $templateId, array $templateData, ?string $trackingId = null): array
    {
        $phone = $this->normalizePhone($phone);
        if ($phone === '') {
            return ['ok' => false, 'status' => 0, 'body' => '', 'error' => 'Số điện thoại không hợp lệ'];
        }

        try {
            $token = $this->getValidAccessToken();
        } catch (\Throwable $e) {
            Log::error('Zalo ZNS token error', ['message' => $e->getMessage()]);

            return ['ok' => false, 'status' => 0, 'body' => '', 'error' => $e->getMessage()];
        }

        $payload = [
            'phone' => $phone,
            'template_id' => $templateId,
            'template_data' => $templateData,
            'tracking_id' => $trackingId ?: uniqid('pay_', true),
        ];

        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'access_token' => $token,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->sendUrl, $payload);

            $body = $response->json() ?? $response->body();
            $errorCode = is_array($body) ? ($body['error'] ?? $body['error_code'] ?? null) : null;
            $ok = $response->successful() && (int) $errorCode === 0;

            // Một số response Zalo dùng error=0 là thành công
            if ($response->successful() && is_array($body) && ! array_key_exists('error', $body) && ! array_key_exists('error_code', $body)) {
                $ok = true;
            }

            Log::info('Zalo ZNS send', [
                'phone' => $phone,
                'template_id' => $templateId,
                'http' => $response->status(),
                'body' => $body,
            ]);

            return [
                'ok' => $ok,
                'status' => $response->status(),
                'body' => $body,
                'error' => $ok ? null : (is_array($body) ? (string) ($body['message'] ?? json_encode($body)) : (string) $body),
                'request' => $payload,
            ];
        } catch (\Throwable $e) {
            Log::error('Zalo ZNS send exception', [
                'phone' => $phone,
                'message' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'status' => 0,
                'body' => '',
                'error' => $e->getMessage(),
                'request' => $payload,
            ];
        }
    }

    public function getValidAccessToken(): string
    {
        $token = AppSettings::secret('zalo_zns_access_token') ?: AppSettings::secret('zalo_oa_access_token');
        $expiresAt = Setting::get('zalo_zns_token_expires_at', '');

        if ($token !== '' && $expiresAt !== '' && now()->lt(\Carbon\Carbon::parse($expiresAt)->subMinutes(2))) {
            return $token;
        }

        if ($token !== '' && $expiresAt === '') {
            return $token;
        }

        $refreshed = $this->refreshAccessToken();
        if ($refreshed === '') {
            if ($token !== '') {
                return $token;
            }
            throw new RuntimeException('Chưa có Access Token Zalo ZNS. Cấu hình tại Cài đặt.');
        }

        return $refreshed;
    }

    public function refreshAccessToken(): string
    {
        $appId = (string) Setting::get('zalo_zns_app_id', Setting::get('zalo_oa_id', ''));
        $secret = AppSettings::secret('zalo_zns_secret_key');
        $refresh = AppSettings::secret('zalo_zns_refresh_token');

        if ($appId === '' || $secret === '' || $refresh === '') {
            Log::warning('Zalo ZNS refresh skipped: thiếu app_id / secret / refresh_token');

            return '';
        }

        try {
            $response = Http::asForm()
                ->timeout(20)
                ->withHeaders([
                    'secret_key' => $secret,
                ])
                ->post($this->tokenUrl, [
                    'refresh_token' => $refresh,
                    'app_id' => $appId,
                    'grant_type' => 'refresh_token',
                ]);

            $body = $response->json() ?? [];
            Log::info('Zalo ZNS refresh token', ['http' => $response->status(), 'body' => $body]);

            $access = (string) ($body['access_token'] ?? '');
            if ($access === '') {
                throw new RuntimeException('Zalo không trả access_token: '.json_encode($body));
            }

            AppSettings::setSecret('zalo_zns_access_token', $access);
            // Đồng bộ token OA cũ nếu đang dùng chung
            AppSettings::setSecret('zalo_oa_access_token', $access);

            if (! empty($body['refresh_token'])) {
                AppSettings::setSecret('zalo_zns_refresh_token', (string) $body['refresh_token']);
            }

            $expiresIn = (int) ($body['expires_in'] ?? 90000);
            Setting::set('zalo_zns_token_expires_at', now()->addSeconds(max(60, $expiresIn))->toDateTimeString());

            return $access;
        } catch (\Throwable $e) {
            Log::error('Zalo ZNS refresh failed', ['message' => $e->getMessage()]);
            throw new RuntimeException('Làm mới Access Token Zalo thất bại: '.$e->getMessage(), 0, $e);
        }
    }

    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';
        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '84') && strlen($digits) >= 11) {
            return $digits;
        }

        if (str_starts_with($digits, '0') && strlen($digits) >= 10) {
            return '84'.substr($digits, 1);
        }

        if (strlen($digits) === 9) {
            return '84'.$digits;
        }

        return $digits;
    }

    public function isConfigured(): bool
    {
        $token = AppSettings::secret('zalo_zns_access_token') ?: AppSettings::secret('zalo_oa_access_token');
        $appId = Setting::get('zalo_zns_app_id', Setting::get('zalo_oa_id', ''));

        return $token !== '' && filled($appId);
    }
}
