<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NotificationTemplateController extends Controller
{
    public function index()
    {
        $templates = NotificationTemplate::query()->orderBy('code')->get();
        $logs = NotificationLog::query()->latest()->limit(40)->get();
        $placeholders = (new NotificationTemplate)->availablePlaceholders();

        return view('admin.system.notification_templates', compact('templates', 'logs', 'placeholders'));
    }

    public function store(Request $request)
    {
        NotificationTemplate::create($this->validated($request));

        return back()->with('success', 'Đã thêm mẫu thông báo.');
    }

    public function update(Request $request, NotificationTemplate $notificationTemplate)
    {
        $notificationTemplate->update($this->validated($request, $notificationTemplate));

        return back()->with('success', 'Đã cập nhật mẫu thông báo.');
    }

    public function destroy(NotificationTemplate $notificationTemplate)
    {
        if ($notificationTemplate->code === 'payment_success') {
            return back()->with('error', 'Không xóa mẫu hệ thống payment_success — hãy tắt Email/Zalo nếu không dùng.');
        }

        $notificationTemplate->delete();

        return back()->with('success', 'Đã xóa mẫu thông báo.');
    }

    protected function validated(Request $request, ?NotificationTemplate $existing = null): array
    {
        $data = $request->validate([
            'code' => 'required|string|max:64|regex:/^[a-z0-9_]+$/|unique:notification_templates,code,'.($existing?->id ?? 'NULL'),
            'title' => 'required|string|max:255',
            'email_subject' => 'nullable|string|max:255',
            'content_email' => 'nullable|string',
            'zalo_template_id' => 'nullable|string|max:64',
            'params_mapping_json' => 'nullable|string',
            'notes' => 'nullable|string|max:1000',
            'is_active_email' => 'nullable|boolean',
            'is_active_zalo' => 'nullable|boolean',
        ], [
            'code.regex' => 'Mã mẫu chỉ gồm a-z, 0-9 và gạch dưới.',
        ]);

        $mapping = [];
        $raw = trim((string) ($data['params_mapping_json'] ?? ''));
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                throw ValidationException::withMessages([
            'params_mapping_json' => 'Biến Zalo (JSON) không hợp lệ — cần object, ví dụ {"customer_name":"{{recipient_name}}"}',
                ]);
            }
            $mapping = $decoded;
        }
        unset($data['params_mapping_json']);

        $data['params_mapping'] = $mapping;
        $data['is_active_email'] = $request->boolean('is_active_email');
        $data['is_active_zalo'] = $request->boolean('is_active_zalo');

        return $data;
    }
}
