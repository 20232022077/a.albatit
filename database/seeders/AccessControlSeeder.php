<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'users.view' => 'عرض المستخدمين', 'users.create' => 'إنشاء مستخدم', 'users.update' => 'تعديل أو تفعيل المستخدم', 'users.delete' => 'حذف المستخدمين',
            'roles.view' => 'عرض الأدوار', 'roles.create' => 'إنشاء دور', 'roles.update' => 'تعديل الدور والصلاحيات', 'roles.delete' => 'حذف الدور',
            'content.view' => 'عرض المحتوى', 'content.create' => 'إنشاء المحتوى', 'content.update' => 'تعديل المحتوى', 'content.delete' => 'حذف المحتوى', 'content.publish' => 'نشر المحتوى', 'content.unpublish' => 'إلغاء نشر المحتوى',
            'settings.manage' => 'إدارة إعدادات الموقع', 'backups.manage' => 'إدارة النسخ الاحتياطية', 'activity_logs.view' => 'مشاهدة سجل العمليات',
        ];
        foreach ($permissions as $name => $displayName) {
            Permission::firstOrCreate(['name' => $name], ['display_name' => $displayName]);
        }
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin'], ['display_name' => 'مدير عام', 'description' => 'وصول كامل إلى المنصة.']);
        $superAdmin->permissions()->sync(Permission::pluck('id'));
    }
}
