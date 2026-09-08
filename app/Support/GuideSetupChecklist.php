<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\CourseClass;
use App\Models\ClassSession;
use App\Models\NotificationTemplate;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class GuideSetupChecklist
{
    /**
     * @return list<array{id: string, label: string, done: bool, optional: bool, url: ?string, hint: ?string, step: ?string}>
     */
    public static function items(): array
    {
        $centerName = trim((string) Setting::get('center_name', ''));
        $logoText = trim((string) Setting::get('logo_text', ''));
        // Đủ khi đã có cả 2 field (kể cả giá trị seed — user có thể giữ nguyên tên).
        $brandDone = $centerName !== '' && $logoText !== '';

        $branchCount = Branch::query()->where('is_active', true)->count();
        $branchWithBank = Branch::query()
            ->where('is_active', true)
            ->get()
            ->contains(fn (Branch $b) => $b->hasPaymentAccount());

        $userCount = User::query()->where('is_active', true)->count();
        $hasSales = self::hasUserRole('sales');
        $hasTraining = self::hasUserRole('training');
        $hasAccountant = self::hasUserRole('accountant');
        $staffDone = $userCount >= 2 && ($hasSales || $hasTraining || $hasAccountant);

        $permissionsReviewed = AppSettings::bool('setup_permissions_reviewed') || $staffDone;

        $subjects = Subject::query()->count();
        $teachers = Teacher::query()->count();
        $classes = CourseClass::query()->count();
        $sessions = ClassSession::query()->count();
        $students = Student::query()->count();

        $smtpDone = AppSettings::bool('smtp_enabled')
            && filled(Setting::get('smtp_host'))
            && AppSettings::secret('smtp_password') !== '';

        $znsDone = AppSettings::zaloZnsReady()
            && AppSettings::bool('zalo_notify_enabled');

        $templateDone = false;
        if (Schema::hasTable('notification_templates')) {
            $templateDone = (bool) NotificationTemplate::findByCode('payment_success');
        }

        $wizard = function (string $step, string $fallbackRoute, array $fallbackParams = []) {
            if (\Illuminate\Support\Facades\Route::has('admin.quick-setup.show')) {
                return route('admin.quick-setup.show', ['step' => $step]);
            }

            return route($fallbackRoute, $fallbackParams);
        };

        return [
            [
                'id' => 'brand',
                'step' => 'brand',
                'label' => 'Đặt tên trung tâm & logo text',
                'done' => $brandDone,
                'optional' => false,
                'url' => $wizard('brand', 'admin.settings.edit'),
                'hint' => $brandDone ? $centerName : 'Vào Cài đặt → Thương hiệu',
            ],
            [
                'id' => 'branch',
                'step' => 'branch',
                'label' => 'Có ≥ 1 chi nhánh đang hoạt động',
                'done' => $branchCount > 0,
                'optional' => false,
                'url' => $wizard('branch', 'admin.branches.index'),
                'hint' => $branchCount > 0 ? "{$branchCount} chi nhánh Active" : null,
            ],
            [
                'id' => 'bank',
                'step' => 'branch',
                'label' => 'Chi nhánh có STK VietQR (thu chuyển khoản)',
                'done' => $branchWithBank,
                'optional' => true,
                'url' => $wizard('branch', 'admin.branches.index'),
                'hint' => $branchWithBank ? 'Đã cấu hình ngân hàng' : 'Tuỳ chọn — PDF mới có QR',
            ],
            [
                'id' => 'users',
                'step' => 'user',
                'label' => 'Tạo tài khoản nhân sự (Sale / Đào tạo / Kế toán…)',
                'done' => $staffDone,
                'optional' => false,
                'url' => $wizard('user', 'admin.users.index'),
                'hint' => $staffDone ? 'Có thể gắn nhiều role / 1 user' : 'Thêm user và tick vai trò',
            ],
            [
                'id' => 'permissions',
                'step' => 'permissions',
                'label' => 'Kiểm tra ma trận phân quyền',
                'done' => $permissionsReviewed,
                'optional' => false,
                'url' => $wizard('permissions', 'admin.permissions.edit'),
                'hint' => $permissionsReviewed ? 'Đã xác nhận' : 'Xem ma trận quyền theo role',
            ],
            [
                'id' => 'subjects',
                'step' => 'subject',
                'label' => 'Có môn học',
                'done' => $subjects > 0,
                'optional' => false,
                'url' => $wizard('subject', 'admin.subjects.index'),
                'hint' => $subjects > 0 ? "{$subjects} môn" : null,
            ],
            [
                'id' => 'teachers',
                'step' => 'teacher',
                'label' => 'Có giáo viên (email trùng user để nhận thông báo nhật ký)',
                'done' => $teachers > 0,
                'optional' => false,
                'url' => $wizard('teacher', 'admin.teachers.index'),
                'hint' => $teachers > 0 ? "{$teachers} GV" : null,
            ],
            [
                'id' => 'classes',
                'step' => 'class',
                'label' => 'Có lớp học + thời khóa biểu',
                'done' => $classes > 0 && $sessions > 0,
                'optional' => false,
                'url' => $wizard('class', 'admin.classes.index'),
                'hint' => $classes > 0
                    ? "{$classes} lớp · {$sessions} buổi"
                    : 'Tạo lớp rồi sinh TKB',
            ],
            [
                'id' => 'students',
                'step' => 'student',
                'label' => 'Có học viên',
                'done' => $students > 0,
                'optional' => false,
                'url' => $wizard('student', 'admin.students.index'),
                'hint' => $students > 0 ? "{$students} HV" : null,
            ],
            [
                'id' => 'smtp',
                'step' => 'smtp',
                'label' => 'Cấu hình SMTP gửi email (Gmail…)',
                'done' => $smtpDone,
                'optional' => true,
                'url' => $wizard('smtp', 'admin.settings.edit'),
                'hint' => $smtpDone ? 'SMTP đang bật' : 'Tuỳ chọn — nhắc nợ / TT học phí qua mail',
            ],
            [
                'id' => 'zns',
                'step' => 'zns',
                'label' => 'Cấu hình Zalo ZNS + bật thông báo thanh toán',
                'done' => $znsDone,
                'optional' => true,
                'url' => $wizard('zns', 'admin.settings.edit'),
                'hint' => $znsDone ? 'ZNS sẵn sàng' : 'Tuỳ chọn — App ID / Token tại Cài đặt',
            ],
            [
                'id' => 'templates',
                'step' => 'templates',
                'label' => 'Mẫu thông báo payment_success (Email / Zalo)',
                'done' => $templateDone,
                'optional' => true,
                'url' => $wizard('templates', 'admin.notification-templates.index'),
                'hint' => $templateDone ? 'Đã có mẫu hệ thống' : 'Seed / tạo tại Mẫu thông báo',
            ],
        ];
    }

    /**
     * @return list<array{key: string, title: string, optional: bool, checklist_ids: list<string>}>
     */
    public static function wizardSteps(): array
    {
        return [
            ['key' => 'brand', 'title' => 'Thương hiệu', 'optional' => false, 'checklist_ids' => ['brand']],
            ['key' => 'branch', 'title' => 'Chi nhánh & STK', 'optional' => false, 'checklist_ids' => ['branch', 'bank']],
            ['key' => 'user', 'title' => 'Người dùng', 'optional' => false, 'checklist_ids' => ['users']],
            ['key' => 'permissions', 'title' => 'Phân quyền', 'optional' => false, 'checklist_ids' => ['permissions']],
            ['key' => 'subject', 'title' => 'Môn học', 'optional' => false, 'checklist_ids' => ['subjects']],
            ['key' => 'teacher', 'title' => 'Giáo viên', 'optional' => false, 'checklist_ids' => ['teachers']],
            ['key' => 'class', 'title' => 'Lớp & TKB', 'optional' => false, 'checklist_ids' => ['classes']],
            ['key' => 'student', 'title' => 'Học viên', 'optional' => false, 'checklist_ids' => ['students']],
            ['key' => 'smtp', 'title' => 'SMTP Email', 'optional' => true, 'checklist_ids' => ['smtp']],
            ['key' => 'zns', 'title' => 'Zalo ZNS', 'optional' => true, 'checklist_ids' => ['zns']],
            ['key' => 'templates', 'title' => 'Mẫu thông báo', 'optional' => true, 'checklist_ids' => ['templates']],
            ['key' => 'done', 'title' => 'Hoàn tất', 'optional' => false, 'checklist_ids' => []],
        ];
    }

    public static function firstIncompleteStep(): string
    {
        $byId = collect(self::items())->keyBy('id');
        foreach (self::wizardSteps() as $step) {
            if ($step['key'] === 'done') {
                continue;
            }
            foreach ($step['checklist_ids'] as $id) {
                $item = $byId->get($id);
                if ($item && ! $item['optional'] && ! $item['done']) {
                    return $step['key'];
                }
            }
        }

        return 'done';
    }

    /**
     * @return array{total: int, done: int, required_total: int, required_done: int, percent: int, items: list<array>}
     */
    public static function summary(): array
    {
        $items = self::items();
        $total = count($items);
        $done = collect($items)->where('done', true)->count();
        $required = collect($items)->where('optional', false);
        $requiredDone = $required->where('done', true)->count();
        $requiredTotal = $required->count();
        $percent = $requiredTotal > 0
            ? (int) round(($requiredDone / $requiredTotal) * 100)
            : 100;

        return [
            'total' => $total,
            'done' => $done,
            'required_total' => $requiredTotal,
            'required_done' => $requiredDone,
            'percent' => $percent,
            'items' => $items,
        ];
    }

    protected static function hasUserRole(string $role): bool
    {
        return User::query()
            ->where('is_active', true)
            ->where(function ($q) use ($role) {
                $q->where('role', $role)
                    ->orWhereHas('roleAssignments', fn ($r) => $r->where('role', $role));
            })
            ->exists();
    }
}
