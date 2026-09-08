<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('finance:remind-debts')->dailyAt('08:00');
Schedule::command('crm:remind-lead-followups')->dailyAt('08:15');
Schedule::command('crm:remind-stale-sessions')->hourly();
Schedule::command('crm:remind-upcoming-sessions', ['--minutes' => 120, '--window' => 12])->everyFifteenMinutes();
