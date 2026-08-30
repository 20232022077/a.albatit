<?php

use App\Models\Backup;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily automatic backup (database + files). Requires the server's cron to
// call `php artisan schedule:run` — see the deployment section of README.md.
// Retention (config('backup.keep_count')) is enforced by BackupService
// itself after every successful run, manual or scheduled.
//
// Deliberately `everyMinute()` + a `when()` window/idempotency check rather
// than `daily()->at('03:00')`: some shared-hosting cPanel accounts run a
// "cron-frequency-monitor" that silently rewrites a account's own crontab
// entries to a load-spreading minute offset (e.g. "* * * * *" becomes
// "8-59/15 * * * *") whenever it's invoked more often than the account's
// plan allows — real production behaviour observed on this site's own
// hosting. `daily()->at('03:00')` needs schedule:run to be invoked in the
// *exact* minute 03:00, which that redistribution can (and here, did) make
// impossible — the task would then silently never run again. `everyMinute()`
// matches whichever minute schedule:run actually gets invoked on, so this
// still runs once during the 03:00 hour regardless of the host's offset;
// the `when()` closure below is what keeps it to once a day.
Schedule::command('backup:run')
    ->everyMinute()
    ->when(fn () => now()->hour === 3 && ! Backup::where('status', 'completed')->whereDate('created_at', today())->exists())
    ->onOneServer();
