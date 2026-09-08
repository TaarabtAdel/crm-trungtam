<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\AppSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SettingController extends Controller
{
    /** @return array<string, string> */
    protected function defaults(): array
    {
        return [
            'center_name' => 'CRM Trung Tâm',
            'logo_text' => 'TPT2',
            'center_email' => '',
            'center_phone' => '',
            'center_hotline' => '',
            'center_address' => '',
            'center_website' => '',
            'center_fanpage' => '',
            'contact_note' => '',
            'zalo_oa_name' => '',
            'zalo_oa_id' => '',
            'zalo_oa_link' => '',
            'zalo_oa_note' => '',
            'zalo_notify_enabled' => '0',
            'zalo_notify_debt' => '0',
            'zalo_notify_schedule' => '0',
            'zalo_notify_attendance' => '0',
            'zalo_notify_payment' => '0',
            'zalo_zns_app_id' => '',
            'smtp_enabled' => '0',
            'smtp_host' => '',
            'smtp_port' => '587',
            'smtp_encryption' => 'tls',
            'smtp_username' => '',
            'smtp_from_address' => '',
            'smtp_from_name' => '',
        ];
    }

    public function edit()
    {
        $settings = [];
        foreach ($this->defaults() as $key => $default) {
            $settings[$key] = Setting::get($key, $default);
        }

        $settings['smtp_password_set'] = AppSettings::secret('smtp_password') !== '';
        $settings['zalo_oa_access_token_set'] = AppSettings::secret('zalo_oa_access_token') !== '';
        $settings['zalo_zns_access_token_set'] = AppSettings::secret('zalo_zns_access_token') !== '';
        $settings['zalo_zns_refresh_token_set'] = AppSettings::secret('zalo_zns_refresh_token') !== '';
        $settings['zalo_zns_secret_set'] = AppSettings::secret('zalo_zns_secret_key') !== '';
        $settings['zalo_notify_ready'] = AppSettings::zaloNotifyReady();
        $settings['zalo_zns_token_expires_at'] = Setting::get('zalo_zns_token_expires_at', '');

        return view('admin.system.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'center_name' => 'required|string|max:255',
            'logo_text' => 'required|string|max:50',
            'center_email' => 'nullable|email|max:255',
            'center_phone' => 'nullable|string|max:30',
            'center_hotline' => 'nullable|string|max:30',
            'center_address' => 'nullable|string|max:500',
            'center_website' => 'nullable|string|max:255',
            'center_fanpage' => 'nullable|string|max:255',
            'contact_note' => 'nullable|string|max:1000',
            'zalo_oa_name' => 'nullable|string|max:120',
            'zalo_oa_id' => 'nullable|string|max:64',
            'zalo_oa_link' => 'nullable|string|max:255',
            'zalo_oa_note' => 'nullable|string|max:500',
            'zalo_oa_access_token' => 'nullable|string|max:2000',
            'zalo_zns_app_id' => 'nullable|string|max:64',
            'zalo_zns_secret_key' => 'nullable|string|max:255',
            'zalo_zns_access_token' => 'nullable|string|max:2000',
            'zalo_zns_refresh_token' => 'nullable|string|max:2000',
            'zalo_notify_enabled' => 'nullable|boolean',
            'zalo_notify_debt' => 'nullable|boolean',
            'zalo_notify_schedule' => 'nullable|boolean',
            'zalo_notify_attendance' => 'nullable|boolean',
            'zalo_notify_payment' => 'nullable|boolean',
            'smtp_enabled' => 'nullable|boolean',
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|integer|min:1|max:65535',
            'smtp_encryption' => 'nullable|in:none,tls,ssl',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'smtp_from_address' => 'nullable|email|max:255',
            'smtp_from_name' => 'nullable|string|max:120',
        ]);

        $booleans = [
            'zalo_notify_enabled',
            'zalo_notify_debt',
            'zalo_notify_schedule',
            'zalo_notify_attendance',
            'zalo_notify_payment',
            'smtp_enabled',
        ];

        foreach ($booleans as $key) {
            Setting::set($key, $request->boolean($key) ? '1' : '0');
            unset($data[$key]);
        }

        $smtpPassword = $data['smtp_password'] ?? null;
        $zaloToken = $data['zalo_oa_access_token'] ?? null;
        $znsSecret = $data['zalo_zns_secret_key'] ?? null;
        $znsAccess = $data['zalo_zns_access_token'] ?? null;
        $znsRefresh = $data['zalo_zns_refresh_token'] ?? null;
        unset(
            $data['smtp_password'],
            $data['zalo_oa_access_token'],
            $data['zalo_zns_secret_key'],
            $data['zalo_zns_access_token'],
            $data['zalo_zns_refresh_token']
        );

        foreach ($data as $key => $value) {
            $value = is_string($value) ? trim($value) : $value;
            if ($key === 'smtp_port') {
                Setting::set($key, (string) ($value ?: '587'));

                continue;
            }
            Setting::set($key, $value === '' || $value === null ? '' : (string) $value);
        }

        if (is_string($smtpPassword) && trim($smtpPassword) !== '') {
            AppSettings::setSecret('smtp_password', trim($smtpPassword));
        }

        if (is_string($zaloToken) && trim($zaloToken) !== '') {
            AppSettings::setSecret('zalo_oa_access_token', trim($zaloToken));
        }

        if (is_string($znsSecret) && trim($znsSecret) !== '') {
            AppSettings::setSecret('zalo_zns_secret_key', trim($znsSecret));
        }

        if (is_string($znsAccess) && trim($znsAccess) !== '') {
            AppSettings::setSecret('zalo_zns_access_token', trim($znsAccess));
        }

        if (is_string($znsRefresh) && trim($znsRefresh) !== '') {
            AppSettings::setSecret('zalo_zns_refresh_token', trim($znsRefresh));
        }

        AppSettings::applyMailConfig();

        return back()->with('success', 'Đã lưu cài đặt hệ thống.');
    }

    public function testMail(Request $request)
    {
        $data = $request->validate([
            'test_email' => 'required|email|max:255',
        ]);

        AppSettings::applyMailConfig();

        if (! AppSettings::bool('smtp_enabled')) {
            return back()->withErrors(['test_email' => 'Hãy bật “Sử dụng SMTP” và lưu cấu hình trước khi gửi thử.']);
        }

        if (! Setting::get('smtp_host')) {
            return back()->withErrors(['test_email' => 'Chưa có SMTP host.']);
        }

        try {
            $center = Setting::get('center_name', config('app.name'));
            Mail::raw(
                "Đây là email thử từ CRM {$center}.\nNếu bạn nhận được thư này, cấu hình SMTP đã hoạt động.",
                function ($message) use ($data, $center) {
                    $message->to($data['test_email'])
                        ->subject('[CRM] Kiểm tra gửi email SMTP — '.$center);
                }
            );
        } catch (\Throwable $e) {
            return back()->withErrors([
                'test_email' => 'Gửi thất bại: '.$e->getMessage(),
            ])->withInput();
        }

        return back()->with('success', 'Đã gửi email thử tới '.$data['test_email'].'. Hãy kiểm tra hộp thư (và Spam).');
    }
}
