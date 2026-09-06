<?php

return [
    'roles' => [
        'super_admin' => 'Super Admin',
        'admin' => 'Admin',
        'sales' => 'Sales',
        'teacher' => 'Giáo viên',
    ],

    /*
    | Quyền theo nhóm. key dùng trong middleware / sidebar / @canPerm
    */
    'groups' => [
        'overview' => [
            'label' => 'Tổng quan',
            'permissions' => [
                'dashboard.view' => 'Xem bảng điều khiển',
            ],
        ],
        'crm' => [
            'label' => 'Tuyển sinh (CRM)',
            'permissions' => [
                'crm.sales.view' => 'Dashboard Sales',
                'crm.leads.view' => 'Xem danh sách Leads',
                'crm.leads.manage' => 'Thêm / sửa / xóa Leads',
                'crm.leads.import' => 'Nhập Leads từ Excel',
                'crm.interactions.view' => 'Xem lịch hẹn / tương tác',
                'crm.interactions.manage' => 'Thêm / sửa / xóa tương tác',
            ],
        ],
        'training' => [
            'label' => 'Quản trị đào tạo',
            'permissions' => [
                'training.classes.view' => 'Xem lớp học',
                'training.classes.manage' => 'Quản lý lớp / TKB / học viên lớp',
                'training.subjects.view' => 'Xem môn học',
                'training.subjects.manage' => 'Thêm / sửa / xóa môn học',
                'training.teachers.view' => 'Xem giáo viên',
                'training.teachers.manage' => 'Thêm / sửa / xóa giáo viên',
                'training.teachers.payroll' => 'Xem / xuất bảng lương GV',
            ],
        ],
        'students' => [
            'label' => 'Quản trị học viên',
            'permissions' => [
                'students.view' => 'Xem học sinh',
                'students.manage' => 'Thêm / sửa / xóa học sinh',
                'attendances.view' => 'Xem điểm danh',
                'attendances.manage' => 'Lưu điểm danh',
            ],
        ],
        'finance' => [
            'label' => 'Quản trị tài chính',
            'permissions' => [
                'finance.invoices.view' => 'Xem hóa đơn học phí',
                'finance.invoices.manage' => 'Tạo / thu / xóa hóa đơn',
            ],
        ],
        'system' => [
            'label' => 'Hệ thống',
            'permissions' => [
                'system.branches.view' => 'Xem chi nhánh',
                'system.branches.manage' => 'Thêm / sửa / xóa chi nhánh',
                'system.users.view' => 'Xem người dùng',
                'system.users.manage' => 'Thêm / sửa / xóa người dùng',
                'system.permissions.manage' => 'Cấu hình phân quyền',
                'system.reports.view' => 'Xem báo cáo',
                'system.settings.manage' => 'Cài đặt hệ thống',
                'system.demo_data.manage' => 'Khởi tạo data demo',
            ],
        ],
    ],

    /*
    | Quyền mặc định khi chưa có cấu hình / khi seed
    | super_admin luôn bypass toàn bộ quyền
    */
    'defaults' => [
        'admin' => [
            'dashboard.view',
            'crm.sales.view',
            'crm.leads.view', 'crm.leads.manage', 'crm.leads.import',
            'crm.interactions.view', 'crm.interactions.manage',
            'training.classes.view', 'training.classes.manage',
            'training.subjects.view', 'training.subjects.manage',
            'training.teachers.view', 'training.teachers.manage', 'training.teachers.payroll',
            'students.view', 'students.manage',
            'attendances.view', 'attendances.manage',
            'finance.invoices.view', 'finance.invoices.manage',
            'system.branches.view', 'system.branches.manage',
            'system.users.view', 'system.users.manage',
            'system.permissions.manage',
            'system.reports.view',
            'system.settings.manage',
            'system.demo_data.manage',
        ],
        'sales' => [
            'dashboard.view',
            'crm.sales.view',
            'crm.leads.view', 'crm.leads.manage', 'crm.leads.import',
            'crm.interactions.view', 'crm.interactions.manage',
            'students.view',
            'system.reports.view',
        ],
        'teacher' => [
            'dashboard.view',
            'training.classes.view',
            'training.teachers.view',
            'students.view',
            'attendances.view', 'attendances.manage',
        ],
    ],
];
