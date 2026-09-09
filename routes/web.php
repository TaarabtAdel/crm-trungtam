<?php

use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\ClassController;
use App\Http\Controllers\Admin\CommissionController;
use App\Http\Controllers\Admin\CrmDashboardController;
use App\Http\Controllers\Admin\CurrentBranchController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DebtController;
use App\Http\Controllers\Admin\DemoDataController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\FinanceDashboardController;
use App\Http\Controllers\Admin\FinanceReportController;
use App\Http\Controllers\Admin\GuideController;
use App\Http\Controllers\Admin\InteractionController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\LookupController;
use App\Http\Controllers\Admin\MyPayrollController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\NotificationTemplateController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RefundController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SchedulerTickController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\StaffAttendanceController;
use App\Http\Controllers\Admin\StaffPayrollController;
use App\Http\Controllers\Admin\TeacherPayrollController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])
        ->middleware('throttle:10,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// Cron công khai (GET, không đăng nhập) — bảo vệ bằng SCHEDULER_TICK_TOKEN
Route::get('/scheduler/tick', SchedulerTickController::class)
    ->middleware('throttle:60,1')
    ->name('scheduler.tick');

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->middleware('permission:dashboard.view')->name('dashboard');
    Route::post('current-branch', [CurrentBranchController::class, 'update'])->name('current-branch.update');
    Route::post('scheduler/tick', SchedulerTickController::class)->name('scheduler.tick');

    Route::get('quick-setup', [\App\Http\Controllers\Admin\QuickSetupController::class, 'index'])
        ->middleware('permission:system.settings.manage,system.branches.manage,training.classes.manage')
        ->name('quick-setup.index');
    Route::get('quick-setup/{step}', [\App\Http\Controllers\Admin\QuickSetupController::class, 'show'])
        ->middleware('permission:system.settings.manage,system.branches.manage,training.classes.manage')
        ->name('quick-setup.show');
    Route::post('quick-setup/{step}', [\App\Http\Controllers\Admin\QuickSetupController::class, 'store'])
        ->middleware('permission:system.settings.manage,system.branches.manage,training.classes.manage')
        ->name('quick-setup.store');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('notifications/{id}/mark-read', [NotificationController::class, 'markAsReadOnly'])->name('notifications.mark-read');

    Route::get('my-payroll', [MyPayrollController::class, 'show'])->name('my-payroll');
    Route::get('my-payroll/pdf', [MyPayrollController::class, 'pdf'])->name('my-payroll.pdf');

    // Select2 AJAX lookups (phân trang)
    Route::get('lookup/students', [LookupController::class, 'students'])->name('lookup.students');
    Route::get('lookup/leads', [LookupController::class, 'leads'])->name('lookup.leads');
    Route::get('lookup/classes', [LookupController::class, 'classes'])->name('lookup.classes');
    Route::get('lookup/teachers', [LookupController::class, 'teachers'])->name('lookup.teachers');

    Route::get('guide', [GuideController::class, 'index'])->name('guide');

    Route::get('/crm/sales', [CrmDashboardController::class, 'index'])->middleware('permission:crm.sales.view')->name('crm.sales');

    Route::middleware('permission:crm.leads.view')->group(function () {
        Route::get('leads', [LeadController::class, 'index'])->name('leads.index');
        Route::get('leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
    });
    Route::middleware('permission:crm.leads.manage')->group(function () {
        Route::post('leads', [LeadController::class, 'store'])->name('leads.store');
        Route::put('leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
        Route::delete('leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');
        Route::post('leads/{lead}/convert-student', [LeadController::class, 'convertToStudent'])
            ->middleware('permission:students.manage')
            ->name('leads.convert-student');
        Route::post('leads/{lead}/placement-tests', [LeadController::class, 'storePlacement'])
            ->name('leads.placement.store');
    });
    Route::post('leads/{lead}/interactions', [LeadController::class, 'storeInteraction'])
        ->middleware('permission:crm.interactions.manage,crm.leads.manage')
        ->name('leads.interactions.store');
    Route::get('leads-import/template', [LeadController::class, 'importTemplate'])->middleware('permission:crm.leads.import')->name('leads.import.template');
    Route::post('leads-import', [LeadController::class, 'import'])->middleware('permission:crm.leads.import')->name('leads.import');

    Route::middleware('permission:crm.interactions.view')->group(function () {
        Route::get('interactions', [InteractionController::class, 'index'])->name('interactions.index');
    });
    Route::middleware('permission:crm.interactions.manage')->group(function () {
        Route::post('interactions', [InteractionController::class, 'store'])->name('interactions.store');
        Route::put('interactions/{interaction}', [InteractionController::class, 'update'])->name('interactions.update');
        Route::delete('interactions/{interaction}', [InteractionController::class, 'destroy'])->name('interactions.destroy');
    });

    Route::middleware('permission:training.subjects.view')->group(function () {
        Route::get('subjects', [SubjectController::class, 'index'])->name('subjects.index');
    });
    Route::middleware('permission:training.subjects.manage')->group(function () {
        Route::post('subjects', [SubjectController::class, 'store'])->name('subjects.store');
        Route::put('subjects/{subject}', [SubjectController::class, 'update'])->name('subjects.update');
        Route::delete('subjects/{subject}', [SubjectController::class, 'destroy'])->name('subjects.destroy');
    });

    Route::middleware('permission:training.teachers.view')->group(function () {
        Route::get('teachers', [TeacherController::class, 'index'])->name('teachers.index');
        Route::get('teachers/{teacher}', [TeacherController::class, 'show'])->name('teachers.show');
    });
    Route::middleware('permission:training.teachers.manage')->group(function () {
        Route::post('teachers', [TeacherController::class, 'store'])->name('teachers.store');
        Route::put('teachers/{teacher}', [TeacherController::class, 'update'])->name('teachers.update');
        Route::delete('teachers/{teacher}', [TeacherController::class, 'destroy'])->name('teachers.destroy');
    });
    Route::get('teachers-payroll/export', [TeacherController::class, 'exportPayroll'])->middleware('permission:training.teachers.payroll')->name('teachers.payroll.export');

    Route::middleware('permission:training.classes.view')->group(function () {
        Route::get('classes', [ClassController::class, 'index'])->name('classes.index');
        Route::get('classes/{class}', [ClassController::class, 'show'])->name('classes.show');
        Route::get('classes/{class}/timetable', [ClassController::class, 'timetable'])->name('classes.timetable');
        Route::get('classes/{class}/timetable/pdf', [ClassController::class, 'exportTimetablePdf'])->name('classes.timetable.pdf');
        Route::get('classes/{class}/available-students', [ClassController::class, 'availableStudents'])->name('classes.students.available');
    });
    Route::middleware('permission:training.classes.manage')->group(function () {
        Route::post('classes', [ClassController::class, 'store'])->name('classes.store');
        Route::put('classes/{class}', [ClassController::class, 'update'])->name('classes.update');
        Route::delete('classes/{class}', [ClassController::class, 'destroy'])->name('classes.destroy');
        Route::post('classes/{class}/students', [ClassController::class, 'attachStudent'])->name('classes.students.attach');
        Route::delete('classes/{class}/students/{student}', [ClassController::class, 'detachStudent'])->name('classes.students.detach');
        Route::post('classes/{class}/invoices/generate', [ClassController::class, 'generateInvoices'])->name('classes.invoices.generate');
        Route::post('classes/{class}/invoices', [ClassController::class, 'storeInvoice'])->name('classes.invoices.store');
        Route::post('classes/{class}/timetable/generate', [ClassController::class, 'generateTimetable'])->name('classes.timetable.generate');
        Route::post('classes/{class}/timetable/sessions', [ClassController::class, 'storeSession'])->name('classes.timetable.sessions.store');
        Route::put('classes/{class}/timetable/sessions/{session}', [ClassController::class, 'updateSession'])->name('classes.timetable.sessions.update');
        Route::delete('classes/{class}/timetable/sessions/{session}', [ClassController::class, 'destroySession'])->name('classes.timetable.sessions.destroy');
    });
    Route::put('classes/{class}/timetable/sessions/{session}/journal', [ClassController::class, 'updateJournal'])
        ->middleware('permission:training.journals.manage,training.classes.manage')
        ->name('classes.timetable.sessions.journal.update');

    Route::middleware('permission:students.view')->group(function () {
        Route::get('students', [StudentController::class, 'index'])->name('students.index');
        Route::get('students/{student}', [StudentController::class, 'show'])->name('students.show');
    });
    Route::middleware('permission:students.manage')->group(function () {
        Route::post('students', [StudentController::class, 'store'])->name('students.store');
        Route::put('students/{student}', [StudentController::class, 'update'])->name('students.update');
        Route::delete('students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
        Route::post('students/{student}/classes', [StudentController::class, 'attachClass'])->name('students.classes.attach');
        Route::delete('students/{student}/classes/{class}', [StudentController::class, 'detachClass'])->name('students.classes.detach');
    });
    Route::get('students-import/template', [StudentController::class, 'importTemplate'])->middleware('permission:students.import')->name('students.import.template');
    Route::post('students-import', [StudentController::class, 'import'])->middleware('permission:students.import')->name('students.import');

    Route::get('attendances', [AttendanceController::class, 'index'])->middleware('permission:attendances.view')->name('attendances.index');
    Route::post('attendances', [AttendanceController::class, 'store'])->middleware('permission:attendances.manage')->name('attendances.store');

    Route::middleware('permission:finance.dashboard.view')->group(function () {
        Route::get('finance', [FinanceDashboardController::class, 'index'])->name('finance.dashboard');
    });

    Route::middleware('permission:finance.invoices.view')->group(function () {
        Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('invoices-suggest', [InvoiceController::class, 'suggest'])->name('invoices.suggest');
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
    });
    Route::middleware('permission:finance.invoices.manage')->group(function () {
        Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::put('invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
        Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
        Route::post('invoices/bulk-destroy', [InvoiceController::class, 'bulkDestroy'])->name('invoices.bulk-destroy');
        Route::post('invoices/{invoice}/installments', [InvoiceController::class, 'storeInstallments'])->name('invoices.installments.store');
    });
    Route::middleware('permission:finance.payments.manage')->group(function () {
        Route::post('invoices/{invoice}/payments', [InvoiceController::class, 'storePayment'])->name('invoices.payments.store');
    });

    Route::middleware('permission:finance.debts.view')->group(function () {
        Route::get('debts', [DebtController::class, 'index'])->name('debts.index');
    });

    Route::middleware('permission:finance.expenses.view')->group(function () {
        Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    });
    Route::middleware('permission:finance.expenses.manage')->group(function () {
        Route::post('expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
        Route::post('expenses/{expense}/paid', [ExpenseController::class, 'markPaid'])->name('expenses.paid');
    });
    Route::middleware('permission:finance.expenses.approve')->group(function () {
        Route::post('expenses/{expense}/approve', [ExpenseController::class, 'approve'])->name('expenses.approve');
        Route::post('expenses/{expense}/reject', [ExpenseController::class, 'reject'])->name('expenses.reject');
    });

    Route::middleware('permission:finance.refunds.manage')->group(function () {
        Route::get('refunds', [RefundController::class, 'index'])->name('refunds.index');
        Route::post('refunds', [RefundController::class, 'store'])->name('refunds.store');
        Route::post('refunds/{refund}/approve', [RefundController::class, 'approve'])->name('refunds.approve');
        Route::post('refunds/{refund}/reject', [RefundController::class, 'reject'])->name('refunds.reject');
    });

    Route::middleware('permission:finance.commissions.view')->group(function () {
        Route::get('commissions', [CommissionController::class, 'index'])->name('commissions.index');
    });
    Route::middleware('permission:finance.commissions.manage')->group(function () {
        Route::post('commissions/{commission}/paid', [CommissionController::class, 'markPaid'])->name('commissions.paid');
        Route::get('commission-rules', [CommissionController::class, 'rules'])->name('commission-rules.index');
        Route::post('commission-rules', [CommissionController::class, 'storeRule'])->name('commission-rules.store');
        Route::put('commission-rules/{rule}', [CommissionController::class, 'updateRule'])->name('commission-rules.update');
        Route::delete('commission-rules/{rule}', [CommissionController::class, 'destroyRule'])->name('commission-rules.destroy');
    });

    Route::middleware('permission:finance.reports.view')->group(function () {
        Route::get('finance/reports', [FinanceReportController::class, 'index'])->name('finance.reports');
        Route::get('finance/teacher-payroll', [TeacherPayrollController::class, 'index'])->name('finance.teacher-payroll');
        Route::get('finance/teacher-payroll/pdf', [TeacherPayrollController::class, 'pdf'])->name('finance.teacher-payroll.pdf');
        Route::get('finance/teacher-payroll/{teacher}/pdf', [TeacherPayrollController::class, 'pdfPerson'])->name('finance.teacher-payroll.person-pdf');
    });
    Route::middleware('permission:finance.staff_payroll.view')->group(function () {
        Route::get('finance/staff-payroll', [StaffPayrollController::class, 'index'])->name('finance.staff-payroll');
        Route::get('finance/staff-payroll/pdf', [StaffPayrollController::class, 'pdf'])->name('finance.staff-payroll.pdf');
        Route::get('finance/staff-payroll/{user}/pdf', [StaffPayrollController::class, 'pdfPerson'])->name('finance.staff-payroll.person-pdf');
    });
    Route::middleware('permission:finance.expenses.manage')->group(function () {
        Route::post('finance/teacher-payroll/pay', [TeacherPayrollController::class, 'pay'])->name('finance.teacher-payroll.pay');
        Route::post('finance/teacher-payroll/adjustments', [TeacherPayrollController::class, 'storeAdjustment'])->name('finance.teacher-payroll.adjustments.store');
        Route::post('finance/teacher-payroll/adjustments/bulk', [TeacherPayrollController::class, 'bulkAdjustment'])->name('finance.teacher-payroll.adjustments.bulk');
        Route::delete('finance/teacher-payroll/adjustments/{adjustment}', [TeacherPayrollController::class, 'destroyAdjustment'])->name('finance.teacher-payroll.adjustments.destroy');
        Route::post('finance/staff-payroll/pay', [StaffPayrollController::class, 'pay'])->name('finance.staff-payroll.pay');
        Route::post('finance/staff-payroll/adjustments', [StaffPayrollController::class, 'storeAdjustment'])->name('finance.staff-payroll.adjustments.store');
        Route::post('finance/staff-payroll/adjustments/bulk', [StaffPayrollController::class, 'bulkAdjustment'])->name('finance.staff-payroll.adjustments.bulk');
        Route::delete('finance/staff-payroll/adjustments/{adjustment}', [StaffPayrollController::class, 'destroyAdjustment'])->name('finance.staff-payroll.adjustments.destroy');
    });
    Route::middleware('permission:finance.reports.export')->group(function () {
        Route::get('finance/reports/export-excel', [FinanceReportController::class, 'exportExcel'])->name('finance.reports.excel');
        Route::get('finance/reports/export-pdf', [FinanceReportController::class, 'exportPdf'])->name('finance.reports.pdf');
    });

    Route::middleware('permission:system.branches.view')->group(function () {
        Route::get('branches', [BranchController::class, 'index'])->name('branches.index');
    });
    Route::middleware('permission:system.branches.manage')->group(function () {
        Route::post('branches', [BranchController::class, 'store'])->name('branches.store');
        Route::put('branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
        Route::delete('branches/{branch}', [BranchController::class, 'destroy'])->name('branches.destroy');
    });

    Route::middleware('permission:system.users.view')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
    });
    Route::middleware('permission:system.users.manage')->group(function () {
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
    Route::middleware('permission:system.staff_attendances.view')->group(function () {
        Route::get('staff-attendances', [StaffAttendanceController::class, 'index'])->name('staff-attendances.index');
    });
    Route::middleware('permission:system.staff_attendances.manage')->group(function () {
        Route::post('staff-attendances', [StaffAttendanceController::class, 'store'])->name('staff-attendances.store');
        Route::delete('staff-attendances', [StaffAttendanceController::class, 'destroy'])->name('staff-attendances.destroy');
    });

    Route::get('permissions', [PermissionController::class, 'edit'])->middleware('permission:system.permissions.manage')->name('permissions.edit');
    Route::put('permissions', [PermissionController::class, 'update'])->middleware('permission:system.permissions.manage')->name('permissions.update');

    Route::get('reports', [ReportController::class, 'index'])->middleware('permission:system.reports.view')->name('reports.index');
    Route::get('settings', [SettingController::class, 'edit'])->middleware('permission:system.settings.manage')->name('settings.edit');
    Route::put('settings', [SettingController::class, 'update'])->middleware('permission:system.settings.manage')->name('settings.update');
    Route::post('settings/test-mail', [SettingController::class, 'testMail'])->middleware('permission:system.settings.manage')->name('settings.test-mail');

    Route::middleware('permission:system.notification_templates.manage')->group(function () {
        Route::get('notification-templates', [NotificationTemplateController::class, 'index'])->name('notification-templates.index');
        Route::post('notification-templates', [NotificationTemplateController::class, 'store'])->name('notification-templates.store');
        Route::put('notification-templates/{notification_template}', [NotificationTemplateController::class, 'update'])->name('notification-templates.update');
        Route::delete('notification-templates/{notification_template}', [NotificationTemplateController::class, 'destroy'])->name('notification-templates.destroy');
    });

    Route::get('demo-data', [DemoDataController::class, 'index'])->middleware('permission:system.demo_data.manage')->name('demo-data.index');
    Route::post('demo-data', [DemoDataController::class, 'run'])->middleware('permission:system.demo_data.manage')->name('demo-data.run');

    Route::middleware('permission:system.backups.manage')->group(function () {
        Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
        Route::post('backups', [BackupController::class, 'store'])->name('backups.store');
        Route::get('backups/{filename}/download', [BackupController::class, 'download'])->name('backups.download');
        Route::delete('backups/{filename}', [BackupController::class, 'destroy'])->name('backups.destroy');
    });
});
