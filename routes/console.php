<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily automatic backup (database + files). Requires the server's cron to
// call `php artisan schedule:run` every minute — see the deployment section
// of README.md. Retention (config('backup.keep_count')) is enforced by
// BackupService itself after every successful run, manual or scheduled.
Schedule::command('backup:run')->daily()->at('03:00')->onOneServer();
