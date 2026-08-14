<?php

namespace App\Support;

use App\Models\Backup;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process as SymfonyProcess;
use ZipArchive;

/**
 * Creates, restores, and prunes full backups (database dump + the files
 * configured in config('backup.include_paths')) as a single zip archive on
 * the private "backups" disk — never the public one, and never reachable by
 * any route except the authorized admin download/restore actions.
 */
class BackupService
{
    public function create(?int $userId): Backup
    {
        set_time_limit(300);

        $workDir = storage_path('app/tmp/backup-'.now()->format('YmdHis'));
        File::ensureDirectoryExists($workDir);

        try {
            $sqlPath = $workDir.'/database.sql';
            $this->dumpDatabase($sqlPath);

            $filename = 'backup-'.now()->format('Y-m-d-His').'.zip';
            $zipPath = $workDir.'/'.$filename;
            $this->buildArchive($zipPath, $sqlPath);

            File::ensureDirectoryExists(Storage::disk('backups')->path(''));
            $destination = $filename;
            File::copy($zipPath, Storage::disk('backups')->path($destination));

            $backup = Backup::create([
                'filename' => $filename,
                'disk' => 'backups',
                'path' => $destination,
                'size' => Storage::disk('backups')->size($destination),
                'status' => 'completed',
                'created_by' => $userId,
            ]);

            $this->pruneOld();

            return $backup;
        } catch (\Throwable $e) {
            return Backup::create([
                'filename' => '—',
                'disk' => 'backups',
                'path' => '',
                'status' => 'failed',
                'notes' => Str::limit($e->getMessage(), 500, ''),
                'created_by' => $userId,
            ]);
        } finally {
            File::deleteDirectory($workDir);
        }
    }

    public function restore(Backup $backup): void
    {
        if (! $backup->exists()) {
            throw new RuntimeException('ملف النسخة الاحتياطية غير موجود على القرص.');
        }

        set_time_limit(300);

        $workDir = storage_path('app/tmp/restore-'.now()->format('YmdHis'));
        File::ensureDirectoryExists($workDir);

        try {
            $zip = new ZipArchive();
            if ($zip->open(Storage::disk('backups')->path($backup->path)) !== true) {
                throw new RuntimeException('تعذّر فتح ملف النسخة الاحتياطية.');
            }
            $zip->extractTo($workDir);
            $zip->close();

            $sqlPath = $workDir.'/database.sql';
            if (File::exists($sqlPath)) {
                $this->restoreDatabase($sqlPath);
            }

            foreach (config('backup.include_paths') as $label => $targetDir) {
                $sourceDir = $workDir.'/'.$label;
                if (File::isDirectory($sourceDir)) {
                    File::ensureDirectoryExists($targetDir);
                    File::cleanDirectory($targetDir);
                    File::copyDirectory($sourceDir, $targetDir);
                }
            }
        } finally {
            File::deleteDirectory($workDir);
        }
    }

    public function delete(Backup $backup): void
    {
        if ($backup->path) {
            Storage::disk($backup->disk)->delete($backup->path);
        }
        $backup->delete();
    }

    private function dumpDatabase(string $sqlPath): void
    {
        $config = config('database.connections.'.config('database.default'));
        $binary = config('backup.mysqldump_path') ?: 'mysqldump';

        $command = [
            $binary,
            '--host='.$config['host'],
            '--port='.$config['port'],
            '--user='.$config['username'],
            '--single-transaction',
            '--no-tablespaces',
            '--routines',
            '--skip-comments',
            $config['database'],
        ];

        $handle = fopen($sqlPath, 'w');
        if ($handle === false) {
            throw new RuntimeException('تعذّر إنشاء ملف تفريغ قاعدة البيانات.');
        }

        $result = Process::env($this->mysqlEnv($config))
            ->timeout(240)
            ->run($command, function (string $type, string $buffer) use ($handle) {
                if ($type === SymfonyProcess::OUT) {
                    fwrite($handle, $buffer);
                }
            });
        fclose($handle);

        if (! $result->successful()) {
            throw new RuntimeException('فشل تفريغ قاعدة البيانات: '.Str::limit($result->errorOutput(), 500, ''));
        }
    }

    private function restoreDatabase(string $sqlPath): void
    {
        $config = config('database.connections.'.config('database.default'));
        $binary = config('backup.mysql_path') ?: 'mysql';

        $command = [
            $binary,
            '--host='.$config['host'],
            '--port='.$config['port'],
            '--user='.$config['username'],
            $config['database'],
        ];

        $result = Process::env($this->mysqlEnv($config))
            ->timeout(240)
            ->input(File::get($sqlPath))
            ->run($command);

        if (! $result->successful()) {
            throw new RuntimeException('فشلت استعادة قاعدة البيانات: '.Str::limit($result->errorOutput(), 500, ''));
        }
    }

    /**
     * Explicitly merged with the current process environment (rather than
     * relying on Symfony Process's inherit-by-default behavior) because on
     * Windows, proc_open needs SystemRoot present for WinSock to initialize
     * — a bare ['MYSQL_PWD' => ...] array reliably breaks mysqldump/mysql
     * there with "Can't create TCP/IP socket".
     */
    private function mysqlEnv(array $config): array
    {
        $env = getenv();

        return filled($config['password'] ?? null) ? [...$env, 'MYSQL_PWD' => $config['password']] : $env;
    }

    private function buildArchive(string $zipPath, string $sqlPath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('تعذّر إنشاء أرشيف النسخة الاحتياطية.');
        }

        $zip->addFile($sqlPath, 'database.sql');

        foreach (config('backup.include_paths') as $label => $dir) {
            if (File::isDirectory($dir)) {
                $this->addDirectory($zip, $dir, $label);
            }
        }

        $zip->close();
    }

    private function addDirectory(ZipArchive $zip, string $dir, string $prefix): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }
            $relative = $prefix.'/'.substr($file->getPathname(), strlen($dir) + 1);
            $zip->addFile($file->getPathname(), str_replace('\\', '/', $relative));
        }
    }

    private function pruneOld(): void
    {
        $keep = max(1, (int) config('backup.keep_count'));

        Backup::where('status', 'completed')
            ->orderByDesc('id')
            ->get()
            ->slice($keep)
            ->each(fn (Backup $old) => $this->delete($old));
    }
}
