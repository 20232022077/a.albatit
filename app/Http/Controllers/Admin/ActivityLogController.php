<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('permission', 'activity_logs.view');

        $sections = DB::table('activity_logs')
            ->selectRaw("DISTINCT SUBSTRING_INDEX(event, '.', 1) as section")
            ->orderBy('section')
            ->pluck('section');

        $logs = ActivityLog::query()
            ->with('user:id,name,email')
            ->when($request->filled('user_id'), fn (Builder $q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->filled('section'), fn (Builder $q) => $q->where('event', 'like', $request->string('section').'.%'))
            ->when($request->filled('from'), fn (Builder $q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn (Builder $q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->when($request->filled('q'), fn (Builder $q) => $q->where('event', 'like', '%'.$request->string('q').'%'))
            ->latest('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.activity-logs.index', [
            'logs' => $logs,
            'sections' => $sections,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['user_id', 'section', 'from', 'to', 'q']),
        ]);
    }
}
