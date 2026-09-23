<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('tasks:create-staff-attendance')->dailyAt('05:30');
Schedule::command('tasks:create-today-sessions')->dailyAt('05:45');
Schedule::command('system:remind-backup')->weeklyOn(1, '06:00');
Schedule::command('finance:remind-debts')->dailyAt('08:00');
Schedule::command('crm:remind-lead-followups')->dailyAt('08:15');
Schedule::command('crm:remind-interactions')->hourly();
Schedule::command('crm:remind-stale-sessions')->hourly();
Schedule::command('crm:remind-empty-journals')->hourly();
Schedule::command('crm:remind-upcoming-sessions', ['--minutes' => 120, '--window' => 12])->everyFifteenMinutes();
Schedule::command('tasks:remind-deadlines')->hourly();
Schedule::command('finance:remind-monthly-invoices')->monthlyOn(1, '07:00');
Schedule::command('finance:remind-payroll')->monthlyOn(25, '07:00');
Schedule::command('finance:remind-commissions')->monthlyOn(28, '07:00');
