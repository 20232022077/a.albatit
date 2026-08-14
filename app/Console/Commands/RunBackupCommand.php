<?php

namespace App\Console\Commands;

use App\Support\ActivityLogger;
use App\Support\BackupService;
use Illuminate\Console\Command;

/**
 * The same BackupService the admin panel's "إنشاء نسخة احتياطية" button
 * calls, exposed as a CLI/scheduler entry point — for the daily automatic
 * backup (see routes/console.php) and for manually triggering one over
 * SSH without going through the browser.
 */
class RunBackupCommand extends Command
{
    protected $signature = 'backup:run';

    protected $description = 'إنشاء نسخة احتياطية كاملة (قاعدة البيانات + الملفات) وتطبيق سياسة الاحتفاظ';

    public function handle(BackupService $backups): int
    {
        $backup = $backups->create(null);

        if ($backup->status !== 'completed') {
            ActivityLogger::log('backups.failed', $backup, ['reason' => $backup->notes, 'source' => 'scheduler']);
            $this->error('فشل إنشاء النسخة الاحتياطية: '.$backup->notes);

            return self::FAILURE;
        }

        ActivityLogger::log('backups.created', $backup, ['filename' => $backup->filename, 'size' => $backup->size, 'source' => 'scheduler']);
        $this->info("تم إنشاء النسخة الاحتياطية: {$backup->filename} ({$backup->humanSize()})");

        return self::SUCCESS;
    }
}
