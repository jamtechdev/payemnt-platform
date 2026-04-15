<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:send-daily-report --period=daily')->dailyAt(config('app.daily_report_time', '08:00'));
Schedule::command('app:send-daily-report --period=weekly')->weeklyOn(1, config('app.daily_report_time', '08:00'));
Schedule::command('app:purge-audit-logs')->dailyAt('02:00');
