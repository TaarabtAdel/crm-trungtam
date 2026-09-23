<?php

namespace App\Support;

use App\Models\User;

class WorkbenchApps
{
    /**
     * @return list<array{id:string,label:string,color:string}>
     */
    public static function categories(): array
    {
        return [
            ['id' => 'crm', 'label' => 'Tuyển sinh', 'color' => '#0284c7'],
            ['id' => 'training', 'label' => 'Đào tạo', 'color' => '#059669'],
            ['id' => 'finance', 'label' => 'Tài chính', 'color' => '#d97706'],
            ['id' => 'system', 'label' => 'Hệ thống', 'color' => '#475569'],
            ['id' => 'personal', 'label' => 'Cá nhân', 'color' => '#0d9488'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function categoryColors(): array
    {
        $map = [];
        foreach (static::categories() as $cat) {
            $map[$cat['id']] = $cat['color'];
        }

        return $map;
    }

    /**
     * @return list<array{
     *   id:string,
     *   name:string,
     *   route:string,
     *   icon:string,
     *   color:string,
     *   category:string,
     *   permissions?:list<string>,
     *   any?:bool,
     *   always?:bool,
     *   hide_if_restricted_teacher?:bool
     * }>
     */
    public static function catalog(): array
    {
        return [
            [
                'id' => 'dashboard',
                'name' => 'Bảng điều khiển',
                'route' => 'admin.dashboard',
                'icon' => 'bi-speedometer2',
                'color' => '#0d9488',
                'category' => 'personal',
                'permissions' => ['dashboard.view'],
            ],
            [
                'id' => 'crm-sales',
                'name' => 'Dashboard Sales',
                'route' => 'admin.crm.sales',
                'icon' => 'bi-graph-up-arrow',
                'color' => '#0284c7',
                'category' => 'crm',
                'permissions' => ['crm.sales.view'],
            ],
            [
                'id' => 'leads',
                'name' => 'Leads',
                'route' => 'admin.leads.index',
                'icon' => 'bi-people',
                'color' => '#2563eb',
                'category' => 'crm',
                'permissions' => ['crm.leads.view'],
            ],
            [
                'id' => 'interactions',
                'name' => 'Lịch hẹn',
                'route' => 'admin.interactions.index',
                'icon' => 'bi-calendar-check',
                'color' => '#0891b2',
                'category' => 'crm',
                'permissions' => ['crm.interactions.view'],
            ],
            [
                'id' => 'classes',
                'name' => 'Lớp học',
                'route' => 'admin.classes.index',
                'icon' => 'bi-journal-bookmark',
                'color' => '#059669',
                'category' => 'training',
                'permissions' => ['training.classes.view'],
            ],
            [
                'id' => 'subjects',
                'name' => 'Môn học',
                'route' => 'admin.subjects.index',
                'icon' => 'bi-book',
                'color' => '#65a30d',
                'category' => 'training',
                'permissions' => ['training.subjects.view'],
            ],
            [
                'id' => 'teachers',
                'name' => 'Giáo viên',
                'route' => 'admin.teachers.index',
                'icon' => 'bi-person-badge',
                'color' => '#ca8a04',
                'category' => 'training',
                'permissions' => ['training.teachers.view'],
                'hide_if_restricted_teacher' => true,
            ],
            [
                'id' => 'students',
                'name' => 'Học sinh',
                'route' => 'admin.students.index',
                'icon' => 'bi-mortarboard',
                'color' => '#d97706',
                'category' => 'training',
                'permissions' => ['students.view'],
            ],
            [
                'id' => 'attendances',
                'name' => 'Điểm danh',
                'route' => 'admin.attendances.index',
                'icon' => 'bi-clipboard-check',
                'color' => '#ea580c',
                'category' => 'training',
                'permissions' => ['attendances.view'],
            ],
            [
                'id' => 'finance',
                'name' => 'Tài chính',
                'route' => 'admin.finance.dashboard',
                'icon' => 'bi-pie-chart',
                'color' => '#b45309',
                'category' => 'finance',
                'permissions' => ['finance.dashboard.view'],
            ],
            [
                'id' => 'invoices',
                'name' => 'Hóa đơn',
                'route' => 'admin.invoices.index',
                'icon' => 'bi-receipt',
                'color' => '#c2410c',
                'category' => 'finance',
                'permissions' => ['finance.invoices.view'],
            ],
            [
                'id' => 'debts',
                'name' => 'Công nợ',
                'route' => 'admin.debts.index',
                'icon' => 'bi-exclamation-triangle',
                'color' => '#dc2626',
                'category' => 'finance',
                'permissions' => ['finance.debts.view'],
            ],
            [
                'id' => 'expenses',
                'name' => 'Chi phí',
                'route' => 'admin.expenses.index',
                'icon' => 'bi-wallet2',
                'color' => '#e11d48',
                'category' => 'finance',
                'permissions' => ['finance.expenses.view'],
            ],
            [
                'id' => 'teacher-payroll',
                'name' => 'Lương GV',
                'route' => 'admin.finance.teacher-payroll',
                'icon' => 'bi-person-vcard',
                'color' => '#be123c',
                'category' => 'finance',
                'permissions' => ['finance.reports.view'],
            ],
            [
                'id' => 'staff-payroll',
                'name' => 'Lương NV',
                'route' => 'admin.finance.staff-payroll',
                'icon' => 'bi-cash-stack',
                'color' => '#9f1239',
                'category' => 'finance',
                'permissions' => ['finance.staff_payroll.view'],
            ],
            [
                'id' => 'commissions',
                'name' => 'Hoa hồng',
                'route' => 'admin.commissions.index',
                'icon' => 'bi-percent',
                'color' => '#a16207',
                'category' => 'finance',
                'permissions' => ['finance.commissions.view'],
            ],
            [
                'id' => 'finance-reports',
                'name' => 'Báo cáo TC',
                'route' => 'admin.finance.reports',
                'icon' => 'bi-bar-chart-line',
                'color' => '#854d0e',
                'category' => 'finance',
                'permissions' => ['finance.reports.view'],
            ],
            [
                'id' => 'branches',
                'name' => 'Chi nhánh',
                'route' => 'admin.branches.index',
                'icon' => 'bi-building',
                'color' => '#475569',
                'category' => 'system',
                'permissions' => ['system.branches.view'],
            ],
            [
                'id' => 'users',
                'name' => 'Người dùng',
                'route' => 'admin.users.index',
                'icon' => 'bi-person-gear',
                'color' => '#334155',
                'category' => 'system',
                'permissions' => ['system.users.view'],
            ],
            [
                'id' => 'staff-attendances',
                'name' => 'Chấm công',
                'route' => 'admin.staff-attendances.index',
                'icon' => 'bi-calendar2-check',
                'color' => '#1e293b',
                'category' => 'system',
                'permissions' => ['system.staff_attendances.view'],
            ],
            [
                'id' => 'permissions',
                'name' => 'Phân quyền',
                'route' => 'admin.permissions.edit',
                'icon' => 'bi-shield-lock',
                'color' => '#0f172a',
                'category' => 'system',
                'permissions' => ['system.permissions.manage'],
            ],
            [
                'id' => 'reports',
                'name' => 'Báo cáo',
                'route' => 'admin.reports.index',
                'icon' => 'bi-bar-chart',
                'color' => '#64748b',
                'category' => 'system',
                'permissions' => ['system.reports.view'],
            ],
            [
                'id' => 'settings',
                'name' => 'Cài đặt',
                'route' => 'admin.settings.edit',
                'icon' => 'bi-gear',
                'color' => '#57534e',
                'category' => 'system',
                'permissions' => ['system.settings.manage'],
            ],
            [
                'id' => 'quick-setup',
                'name' => 'Cài đặt nhanh',
                'route' => 'admin.quick-setup.index',
                'icon' => 'bi-magic',
                'color' => '#0e7490',
                'category' => 'system',
                'permissions' => ['system.settings.manage', 'system.branches.manage', 'training.classes.manage'],
                'any' => true,
            ],
            [
                'id' => 'guide',
                'name' => 'Hướng dẫn',
                'route' => 'admin.guide',
                'icon' => 'bi-question-circle',
                'color' => '#0369a1',
                'category' => 'personal',
                'always' => true,
            ],
            [
                'id' => 'my-payroll',
                'name' => 'Lương của tôi',
                'route' => 'admin.my-payroll',
                'icon' => 'bi-wallet',
                'color' => '#0f766e',
                'category' => 'personal',
                'always' => true,
            ],
            [
                'id' => 'notifications',
                'name' => 'Thông báo',
                'route' => 'admin.notifications.index',
                'icon' => 'bi-bell',
                'color' => '#b45309',
                'category' => 'personal',
                'always' => true,
            ],
        ];
    }

    /**
     * @return list<array{
     *   id:string,
     *   name:string,
     *   url:string,
     *   icon:string,
     *   color:string,
     *   category:string
     * }>
     */
    public static function forUser(User $user): array
    {
        $apps = [];
        $colors = static::categoryColors();

        foreach (static::catalog() as $app) {
            if (! empty($app['hide_if_restricted_teacher']) && $user->isRestrictedTeacher()) {
                continue;
            }

            if (empty($app['always'])) {
                $perms = $app['permissions'] ?? [];
                if ($perms === []) {
                    continue;
                }
                $ok = ! empty($app['any'])
                    ? $user->hasAnyPermission(...$perms)
                    : $user->hasPermission($perms[0]);
                if (! $ok) {
                    continue;
                }
            }

            if (! \Illuminate\Support\Facades\Route::has($app['route'])) {
                continue;
            }

            $category = $app['category'];

            $apps[] = [
                'id' => $app['id'],
                'name' => $app['name'],
                'url' => route($app['route']),
                'icon' => $app['icon'],
                'color' => $colors[$category] ?? ($app['color'] ?? '#64748b'),
                'category' => $category,
            ];
        }

        return $apps;
    }
}
