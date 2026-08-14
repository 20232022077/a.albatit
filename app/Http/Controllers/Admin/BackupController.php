<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Support\ActivityLogger;
use App\Support\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function __construct(private readonly BackupService $backups)
    {
    }

    public function index(): View
    {
        $this->authorize('permission', 'backups.manage');

        return view('admin.backups.index', [
            'backups' => Backup::with('creator:id,name')->latest()->paginate(15),
            'retention' => config('backup.keep_count'),
        ]);
    }

    public function store(): RedirectResponse
    {
        $this->authorize('permission', 'backups.manage');

        $backup = $this->backups->create(auth()->id());

        if ($backup->status === 'completed') {
            ActivityLogger::log('backups.created', $backup, ['filename' => $backup->filename, 'size' => $backup->size]);

            return back()->with('status', 'تم إنشاء نسخة احتياطية جديدة بنجاح.');
        }

        ActivityLogger::log('backups.failed', $backup, ['reason' => $backup->notes]);

        return back()->with('error', 'فشل إنشاء النسخة الاحتياطية: '.$backup->notes);
    }

    public function download(Backup $backup): StreamedResponse
    {
        $this->authorize('permission', 'backups.manage');
        abort_unless($backup->status === 'completed' && $backup->exists(), 404);

        ActivityLogger::log('backups.downloaded', $backup, ['filename' => $backup->filename]);

        return Storage::disk($backup->disk)->download($backup->path, $backup->filename);
    }

    public function restore(Backup $backup): RedirectResponse
    {
        $this->authorize('permission', 'backups.manage');
        abort_unless($backup->status === 'completed', 404);

        try {
            $this->backups->restore($backup);
            ActivityLogger::log('backups.restored', $backup, ['filename' => $backup->filename]);

            return back()->with('status', 'تمت استعادة النسخة الاحتياطية بنجاح.');
        } catch (\Throwable $e) {
            ActivityLogger::log('backups.restore_failed', $backup, ['reason' => $e->getMessage()]);

            return back()->with('error', 'فشلت الاستعادة: '.$e->getMessage());
        }
    }

    public function destroy(Backup $backup): RedirectResponse
    {
        $this->authorize('permission', 'backups.manage');

        ActivityLogger::log('backups.deleted', $backup, ['filename' => $backup->filename]);
        $this->backups->delete($backup);

        return back()->with('status', 'تم حذف النسخة الاحتياطية.');
    }
}
