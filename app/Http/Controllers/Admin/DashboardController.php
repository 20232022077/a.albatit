<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                ['label' => 'إجمالي المحتوى', 'value' => DB::table('content_items')->count(), 'icon' => 'document'],
                ['label' => 'المحتوى المنشور', 'value' => DB::table('content_items')->where('status', 'published')->count(), 'icon' => 'check'],
                ['label' => 'المستخدمون المفعّلون', 'value' => User::where('is_active', true)->count(), 'icon' => 'users'],
                ['label' => 'الوسائط', 'value' => DB::table('media')->count(), 'icon' => 'image'],
            ],
            'recentActivities' => DB::table('activity_logs')->leftJoin('users', 'activity_logs.user_id', '=', 'users.id')->select('activity_logs.event', 'activity_logs.created_at', 'users.name as user_name')->latest('activity_logs.created_at')->limit(6)->get(),
        ]);
    }
}
