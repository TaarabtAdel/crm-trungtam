<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CourseClass;
use App\Models\NotificationTemplate;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\ClassTimetableGenerator;
use App\Support\AppSettings;
use App\Support\GuideSetupChecklist;
use App\Support\VietQr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QuickSetupController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.quick-setup.show', [
            'step' => GuideSetupChecklist::firstIncompleteStep(),
        ]);
    }

    public function show(Request $request, string $step)
    {
        $steps = GuideSetupChecklist::wizardSteps();
        $keys = collect($steps)->pluck('key')->all();
        if (! in_array($step, $keys, true)) {
            return redirect()->route('admin.quick-setup.index');
        }

        $checklist = GuideSetupChecklist::summary();
        $stepMeta = collect($steps)->firstWhere('key', $step);
        $stepIndex = array_search($step, $keys, true);
        $prevKey = $stepIndex > 0 ? $keys[$stepIndex - 1] : null;
        $nextKey = $stepIndex < count($keys) - 1 ? $keys[$stepIndex + 1] : null;

        $branches = Branch::query()->where('is_active', true)->orderBy('name')->get();
        $banks = VietQr::banks();
        $subjects = Subject::query()->where('status', 'active')->orderBy('name')->get();
        $teachers = Teacher::query()->where('status', 'active')->orderBy('name')->get();
        $classes = CourseClass::query()->where('status', 'active')->orderBy('name')->get();
        $roleOptions = config('permissions.roles', []);
        $days = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
        $paymentTemplate = NotificationTemplate::findByCode('payment_success');

        $settings = [
            'center_name' => Setting::get('center_name', ''),
            'logo_text' => Setting::get('logo_text', ''),
            'smtp_enabled' => Setting::get('smtp_enabled', '0'),
            'smtp_host' => Setting::get('smtp_host', ''),
            'smtp_port' => Setting::get('smtp_port', '587'),
            'smtp_encryption' => Setting::get('smtp_encryption', 'tls'),
            'smtp_username' => Setting::get('smtp_username', ''),
            'smtp_from_address' => Setting::get('smtp_from_address', ''),
            'smtp_from_name' => Setting::get('smtp_from_name', ''),
            'smtp_password_set' => AppSettings::secret('smtp_password') !== '',
            'zalo_notify_enabled' => Setting::get('zalo_notify_enabled', '0'),
            'zalo_notify_payment' => Setting::get('zalo_notify_payment', '0'),
            'zalo_zns_app_id' => Setting::get('zalo_zns_app_id', ''),
            'zalo_zns_access_token_set' => AppSettings::secret('zalo_zns_access_token') !== '',
            'zalo_zns_refresh_token_set' => AppSettings::secret('zalo_zns_refresh_token') !== '',
            'zalo_zns_secret_set' => AppSettings::secret('zalo_zns_secret_key') !== '',
        ];

        return view('admin.quick_setup.show', compact(
            'step', 'steps', 'stepMeta', 'prevKey', 'nextKey', 'checklist',
            'branches', 'banks', 'subjects', 'teachers', 'classes', 'roleOptions', 'days',
            'paymentTemplate', 'settings'
        ));
    }

    public function store(Request $request, string $step, ClassTimetableGenerator $generator)
    {
        $keys = collect(GuideSetupChecklist::wizardSteps())->pluck('key')->all();
        if (! in_array($step, $keys, true) || $step === 'done') {
            return redirect()->route('admin.quick-setup.index');
        }

        if ($request->boolean('skip')) {
            return $this->goNext($step);
        }

        return match ($step) {
            'brand' => $this->saveBrand($request),
            'branch' => $this->saveBranch($request),
            'user' => $this->saveUser($request),
            'permissions' => $this->savePermissions($request),
            'subject' => $this->saveSubject($request),
            'teacher' => $this->saveTeacher($request),
            'class' => $this->saveClass($request, $generator),
            'student' => $this->saveStudent($request),
            'smtp' => $this->saveSmtp($request),
            'zns' => $this->saveZns($request),
            'templates' => $this->saveTemplates($request),
            default => redirect()->route('admin.quick-setup.index'),
        };
    }

    protected function goNext(string $current, ?string $message = null)
    {
        $keys = collect(GuideSetupChecklist::wizardSteps())->pluck('key')->all();
        $idx = array_search($current, $keys, true);
        $next = ($idx !== false && isset($keys[$idx + 1])) ? $keys[$idx + 1] : 'done';

        return redirect()
            ->route('admin.quick-setup.show', ['step' => $next])
            ->with('success', $message ?? 'Đã lưu. Tiếp tục bước tiếp theo.');
    }

    protected function saveBrand(Request $request)
    {
        $data = $request->validate([
            'center_name' => 'required|string|max:120',
            'logo_text' => 'required|string|max:50',
        ]);
        Setting::set('center_name', trim($data['center_name']));
        Setting::set('logo_text', trim($data['logo_text']));

        return $this->goNext('brand', 'Đã lưu thương hiệu trung tâm.');
    }

    protected function saveBranch(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'bank_bin' => 'nullable|string|max:20',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_name' => 'nullable|string|max:120',
        ]);

        $bin = $data['bank_bin'] ?? null;
        $account = ! empty($data['bank_account_number'])
            ? preg_replace('/\s+/', '', $data['bank_account_number'])
            : null;
        $holder = ! empty($data['bank_account_name'])
            ? mb_strtoupper(trim($data['bank_account_name']))
            : null;

        Branch::create([
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'bank_bin' => $bin ?: null,
            'bank_account_number' => $account,
            'bank_account_name' => $holder,
            'bank_name' => ($bin && $account) ? VietQr::bankName($bin) : null,
            'is_active' => true,
        ]);

        return $this->goNext('branch', 'Đã thêm chi nhánh.');
    }

    protected function saveUser(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'roles' => 'required|array|min:1',
            'roles.*' => ['string', Rule::in(array_keys(config('permissions.roles', [])))],
            'phone' => 'nullable|string|max:30',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $roles = array_values(array_unique($data['roles']));
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'role' => in_array('super_admin', $roles, true) ? 'super_admin' : $roles[0],
            'is_active' => true,
        ]);
        $user->syncRoles($roles);

        return $this->goNext('user', 'Đã tạo người dùng '.$user->name.'.');
    }

    protected function savePermissions(Request $request)
    {
        Setting::set('setup_permissions_reviewed', '1');

        return $this->goNext('permissions', 'Đã ghi nhận kiểm tra phân quyền.');
    }

    protected function saveSubject(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'branch_id' => 'required|exists:branches,id',
            'description' => 'nullable|string',
        ]);
        Subject::create([
            'name' => $data['name'],
            'branch_id' => $data['branch_id'],
            'description' => $data['description'] ?? null,
            'status' => 'active',
        ]);

        return $this->goNext('subject', 'Đã thêm môn học.');
    }

    protected function saveTeacher(Request $request)
    {
        $data = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:teachers,email',
            'phone' => 'nullable|string|max:30',
            'hourly_rate' => 'nullable|numeric|min:0',
            'create_login' => 'nullable|boolean',
            'password' => 'nullable|string|min:6',
        ]);

        $teacher = Teacher::create([
            'branch_id' => $data['branch_id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'hourly_rate' => $data['hourly_rate'] ?? 0,
            'status' => 'active',
        ]);

        if ($request->boolean('create_login')) {
            $password = $data['password'] ?? null;
            if (! $password) {
                throw ValidationException::withMessages([
                    'password' => 'Nhập mật khẩu khi tạo tài khoản đăng nhập cho GV.',
                ]);
            }
            if (! User::query()->where('email', $teacher->email)->exists()) {
                $user = User::create([
                    'name' => $teacher->name,
                    'email' => $teacher->email,
                    'password' => Hash::make($password),
                    'branch_id' => $teacher->branch_id,
                    'role' => 'teacher',
                    'is_active' => true,
                ]);
                $user->syncRoles(['teacher']);
            }
        }

        return $this->goNext('teacher', 'Đã thêm giáo viên.');
    }

    protected function saveClass(Request $request, ClassTimetableGenerator $generator)
    {
        $data = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'subject_id' => 'nullable|exists:subjects,id',
            'teacher_id' => 'nullable|exists:teachers,id',
            'schedule_days' => 'nullable|array',
            'schedule_days.*' => 'in:T2,T3,T4,T5,T6,T7,CN',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'tuition_fee' => 'nullable|numeric|min:0',
            'tuition_type' => 'nullable|in:monthly,per_session',
            'tt_from' => 'nullable|date',
            'tt_to' => 'nullable|date|after_or_equal:tt_from',
        ]);

        $class = CourseClass::create([
            'branch_id' => $data['branch_id'],
            'name' => $data['name'],
            'subject_id' => $data['subject_id'] ?? null,
            'teacher_id' => $data['teacher_id'] ?? null,
            'schedule_days' => $data['schedule_days'] ?? [],
            'start_time' => $data['start_time'] ?? '08:00',
            'end_time' => $data['end_time'] ?? '10:00',
            'tuition_fee' => $data['tuition_fee'] ?? 0,
            'tuition_type' => $data['tuition_type'] ?? 'monthly',
            'status' => 'active',
            'max_students' => 0,
        ]);

        $msg = 'Đã tạo lớp '.$class->name.'.';
        if (! empty($data['tt_from']) && ! empty($data['tt_to']) && ! empty($data['schedule_days'])) {
            try {
                $result = $generator->generate($class, $data['tt_from'], $data['tt_to'], false);
                $msg .= " Đã sinh {$result['created']} buổi TKB.";
            } catch (\InvalidArgumentException $e) {
                return redirect()
                    ->route('admin.quick-setup.show', ['step' => 'class'])
                    ->withInput()
                    ->with('error', 'Lớp đã tạo nhưng TKB lỗi: '.$e->getMessage());
            }
        }

        return $this->goNext('class', $msg);
    }

    protected function saveStudent(Request $request)
    {
        $data = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'parent_name' => 'nullable|string|max:255',
            'parent_phone' => 'nullable|string|max:30',
            'class_id' => 'nullable|exists:classes,id',
        ]);

        $student = Student::create([
            'branch_id' => $data['branch_id'],
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'parent_name' => $data['parent_name'] ?? null,
            'parent_phone' => $data['parent_phone'] ?? null,
            'status' => 'studying',
        ]);

        if (! empty($data['class_id'])) {
            $student->classes()->syncWithoutDetaching([(int) $data['class_id']]);
        }

        return $this->goNext('student', 'Đã thêm học viên.');
    }

    protected function saveSmtp(Request $request)
    {
        $data = $request->validate([
            'smtp_enabled' => 'nullable|boolean',
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|integer|min:1|max:65535',
            'smtp_encryption' => 'nullable|in:none,tls,ssl',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'smtp_from_address' => 'nullable|email|max:255',
            'smtp_from_name' => 'nullable|string|max:120',
        ]);

        Setting::set('smtp_enabled', $request->boolean('smtp_enabled') ? '1' : '0');
        Setting::set('smtp_host', trim((string) ($data['smtp_host'] ?? '')));
        Setting::set('smtp_port', (string) ($data['smtp_port'] ?? 587));
        Setting::set('smtp_encryption', $data['smtp_encryption'] ?? 'tls');
        Setting::set('smtp_username', trim((string) ($data['smtp_username'] ?? '')));
        Setting::set('smtp_from_address', trim((string) ($data['smtp_from_address'] ?? '')));
        Setting::set('smtp_from_name', trim((string) ($data['smtp_from_name'] ?? '')));

        if (! empty($data['smtp_password'])) {
            AppSettings::setSecret('smtp_password', trim($data['smtp_password']));
        }
        AppSettings::applyMailConfig();

        return $this->goNext('smtp', 'Đã lưu cấu hình SMTP.');
    }

    protected function saveZns(Request $request)
    {
        $data = $request->validate([
            'zalo_notify_enabled' => 'nullable|boolean',
            'zalo_notify_payment' => 'nullable|boolean',
            'zalo_zns_app_id' => 'nullable|string|max:64',
            'zalo_zns_secret_key' => 'nullable|string|max:255',
            'zalo_zns_access_token' => 'nullable|string|max:2000',
            'zalo_zns_refresh_token' => 'nullable|string|max:2000',
        ]);

        Setting::set('zalo_notify_enabled', $request->boolean('zalo_notify_enabled') ? '1' : '0');
        Setting::set('zalo_notify_payment', $request->boolean('zalo_notify_payment') ? '1' : '0');
        Setting::set('zalo_zns_app_id', trim((string) ($data['zalo_zns_app_id'] ?? '')));

        foreach (['zalo_zns_secret_key', 'zalo_zns_access_token', 'zalo_zns_refresh_token'] as $secret) {
            if (! empty($data[$secret])) {
                AppSettings::setSecret($secret, trim($data[$secret]));
            }
        }

        return $this->goNext('zns', 'Đã lưu cấu hình Zalo ZNS.');
    }

    protected function saveTemplates(Request $request)
    {
        $data = $request->validate([
            'is_active_email' => 'nullable|boolean',
            'is_active_zalo' => 'nullable|boolean',
            'zalo_template_id' => 'nullable|string|max:64',
            'email_subject' => 'nullable|string|max:255',
            'content_email' => 'nullable|string',
        ]);

        $tpl = NotificationTemplate::findByCode('payment_success');
        if (! $tpl) {
            return redirect()
                ->route('admin.quick-setup.show', ['step' => 'templates'])
                ->with('error', 'Chưa có mẫu payment_success. Chạy seeder hoặc tạo tại Mẫu thông báo.');
        }

        $tpl->update([
            'is_active_email' => $request->boolean('is_active_email'),
            'is_active_zalo' => $request->boolean('is_active_zalo'),
            'zalo_template_id' => $data['zalo_template_id'] ?? null,
            'email_subject' => $data['email_subject'] ?? $tpl->email_subject,
            'content_email' => $data['content_email'] ?? $tpl->content_email,
        ]);

        return $this->goNext('templates', 'Đã cập nhật mẫu payment_success.');
    }
}
