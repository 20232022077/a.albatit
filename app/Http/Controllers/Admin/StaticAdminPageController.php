<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaticAdminPageController extends Controller
{
    private const SECTIONS = [
        'content' => ['title' => 'إدارة المحتوى', 'description' => 'ستظهر هنا أدوات إدارة محتوى المنصة عند إضافة وحدات المحتوى في المهام القادمة.', 'permission' => 'content.view'],
        'categories' => ['title' => 'التصنيفات', 'description' => 'إدارة تصنيفات المحتوى وتنظيمها هرميًا.', 'permission' => 'content.view'],
        'tags' => ['title' => 'الوسوم', 'description' => 'إدارة الوسوم وإعادة استخدامها عبر المحتوى.', 'permission' => 'content.view'],
        'activity-logs' => ['title' => 'سجل العمليات', 'description' => 'سجل الأحداث والعمليات الإدارية سيظهر هنا.', 'permission' => 'activity_logs.view'],
        'backups' => ['title' => 'النسخ الاحتياطية', 'description' => 'إدارة النسخ الاحتياطية ستكون متاحة هنا.', 'permission' => 'backups.manage'],
    ];

    public function __invoke(Request $request, string $section): View
    {
        abort_unless(isset(self::SECTIONS[$section]), 404);
        $page = self::SECTIONS[$section];
        $this->authorize('permission', $page['permission']);

        return view('admin.placeholder', compact('page', 'section'));
    }
}
