<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Used by `php artisan schedule:work` locally. On cPanel a plain daily cron on
// `reminders:send` is simpler; both are safe together (each item is mailed once).
Schedule::command('reminders:send')->dailyAt('09:00')->timezone(config('expiry.timezone'));
