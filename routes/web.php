<?php

use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\ClassController;
use App\Http\Controllers\Admin\CrmDashboardController;
use App\Http\Controllers\Admin\CurrentBranchController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DemoDataController;
use App\Http\Controllers\Admin\InteractionController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->middleware('permission:dashboard.view')->name('dashboard');
    Route::post('current-branch', [CurrentBranchController::class, 'update'])->name('current-branch.update');

    Route::get('/crm/sales', [CrmDashboardController::class, 'index'])->middleware('permission:crm.sales.view')->name('crm.sales');

    Route::middleware('permission:crm.leads.view')->group(function () {
        Route::get('leads', [LeadController::class, 'index'])->name('leads.index');
        Route::get('leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
    });
    Route::middleware('permission:crm.leads.manage')->group(function () {
        Route::post('leads', [LeadController::class, 'store'])->name('leads.store');
        Route::put('leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
        Route::delete('leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');
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

    Route::middleware('permission:students.view')->group(function () {
        Route::get('students', [StudentController::class, 'index'])->name('students.index');
    });
    Route::middleware('permission:students.manage')->group(function () {
        Route::post('students', [StudentController::class, 'store'])->name('students.store');
        Route::put('students/{student}', [StudentController::class, 'update'])->name('students.update');
        Route::delete('students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
    });

    Route::get('attendances', [AttendanceController::class, 'index'])->middleware('permission:attendances.view')->name('attendances.index');
    Route::post('attendances', [AttendanceController::class, 'store'])->middleware('permission:attendances.manage')->name('attendances.store');

    Route::middleware('permission:finance.invoices.view')->group(function () {
        Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('invoices-suggest', [InvoiceController::class, 'suggest'])->name('invoices.suggest');
    });
    Route::middleware('permission:finance.invoices.manage')->group(function () {
        Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::put('invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
        Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
        Route::post('invoices/{invoice}/paid', [InvoiceController::class, 'markPaid'])->name('invoices.paid');
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
    });
    Route::middleware('permission:system.users.manage')->group(function () {
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    Route::get('permissions', [PermissionController::class, 'edit'])->middleware('permission:system.permissions.manage')->name('permissions.edit');
    Route::put('permissions', [PermissionController::class, 'update'])->middleware('permission:system.permissions.manage')->name('permissions.update');

    Route::get('reports', [ReportController::class, 'index'])->middleware('permission:system.reports.view')->name('reports.index');
    Route::get('settings', [SettingController::class, 'edit'])->middleware('permission:system.settings.manage')->name('settings.edit');
    Route::put('settings', [SettingController::class, 'update'])->middleware('permission:system.settings.manage')->name('settings.update');
    Route::get('demo-data', [DemoDataController::class, 'index'])->middleware('permission:system.demo_data.manage')->name('demo-data.index');
    Route::post('demo-data', [DemoDataController::class, 'run'])->middleware('permission:system.demo_data.manage')->name('demo-data.run');
});
