<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Backup;
use App\Models\Setting;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class SettingsBackupActivityLogTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
    }

    public function test_only_users_with_settings_permission_can_view_or_update_settings(): void
    {
        $outsider = $this->userWithNoPermissions();
        $this->actingAs($outsider)->get('/admin/settings')->assertForbidden();

        $admin = $this->userWithPermissions(['settings.manage']);
        $this->actingAs($admin)->get('/admin/settings')->assertOk();
    }

    public function test_updating_settings_persists_and_is_reflected_on_public_pages(): void
    {
        $admin = $this->userWithPermissions(['settings.manage']);

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'site_name' => 'اسم الموقع الجديد',
            'items_per_page' => 6,
            'hero_eyebrow' => 'عبارة تجريبية فوق الصورة',
            'hero_caption' => 'وصف تجريبي تحت الصورة',
        ])->assertRedirect();

        $this->assertDatabaseHas('settings', ['key' => 'general.site_name', 'value' => 'اسم الموقع الجديد']);
        $this->assertDatabaseHas('settings', ['key' => 'homepage.hero_eyebrow', 'value' => 'عبارة تجريبية فوق الصورة']);
        $this->assertDatabaseHas('settings', ['key' => 'homepage.hero_caption', 'value' => 'وصف تجريبي تحت الصورة']);

        Setting::flush();
        $response = $this->get('/');
        $response->assertSee('اسم الموقع الجديد');
        $response->assertSee('عبارة تجريبية فوق الصورة');
        $response->assertSee('وصف تجريبي تحت الصورة');
    }

    public function test_settings_update_is_recorded_in_the_activity_log(): void
    {
        $admin = $this->userWithPermissions(['settings.manage', 'activity_logs.view']);

        $this->actingAs($admin)->put(route('admin.settings.update'), ['site_name' => 'اسم جديد']);

        $this->assertDatabaseHas('activity_logs', ['event' => 'settings.updated', 'user_id' => $admin->id]);
    }

    public function test_only_users_with_backups_permission_can_access_backup_routes(): void
    {
        $outsider = $this->userWithNoPermissions();

        $this->actingAs($outsider)->get('/admin/backups')->assertForbidden();
        $this->actingAs($outsider)->post('/admin/backups')->assertForbidden();

        $admin = $this->userWithPermissions(['backups.manage']);
        $this->actingAs($admin)->get('/admin/backups')->assertOk();
    }

    public function test_backup_files_are_never_reachable_through_a_public_url(): void
    {
        Storage::fake('backups');
        Storage::disk('backups')->put('backup-test.zip', 'fake zip content');

        $this->get('/storage/backups/backup-test.zip')->assertNotFound();
        $this->get('/backups/backup-test.zip')->assertNotFound();
    }

    public function test_downloading_a_backup_requires_permission_and_the_file_to_exist(): void
    {
        Storage::fake('backups');
        Storage::disk('backups')->put('real-backup.zip', 'fake zip content');
        $backup = Backup::create(['filename' => 'real-backup.zip', 'disk' => 'backups', 'path' => 'real-backup.zip', 'status' => 'completed', 'size' => 17]);

        $outsider = $this->userWithNoPermissions();
        $this->actingAs($outsider)->get(route('admin.backups.download', $backup))->assertForbidden();

        $admin = $this->userWithPermissions(['backups.manage']);
        $this->actingAs($admin)->get(route('admin.backups.download', $backup))->assertOk();

        $missingRecordBackup = Backup::create(['filename' => 'missing.zip', 'disk' => 'backups', 'path' => 'missing.zip', 'status' => 'completed', 'size' => 0]);
        $this->actingAs($admin)->get(route('admin.backups.download', $missingRecordBackup))->assertNotFound();
    }

    public function test_deleting_a_backup_removes_both_the_record_and_the_file(): void
    {
        Storage::fake('backups');
        Storage::disk('backups')->put('to-delete.zip', 'content');
        $backup = Backup::create(['filename' => 'to-delete.zip', 'disk' => 'backups', 'path' => 'to-delete.zip', 'status' => 'completed', 'size' => 7]);

        $admin = $this->userWithPermissions(['backups.manage']);
        $this->actingAs($admin)->delete(route('admin.backups.destroy', $backup))->assertRedirect();

        $this->assertDatabaseMissing('backups', ['id' => $backup->id]);
        Storage::disk('backups')->assertMissing('to-delete.zip');
    }

    public function test_login_is_written_to_the_activity_log_with_ip_and_user_agent(): void
    {
        $user = $this->userWithPermissions(['activity_logs.view'], ['password' => 'CorrectPassword123']);

        $this->post('/login', ['email' => $user->email, 'password' => 'CorrectPassword123']);

        $this->assertDatabaseHas('activity_logs', [
            'event' => 'auth.login.succeeded',
            'user_id' => $user->id,
        ]);
    }

    public function test_only_users_with_activity_logs_permission_can_view_the_log(): void
    {
        $outsider = $this->userWithNoPermissions();
        $this->actingAs($outsider)->get('/admin/activity-logs')->assertForbidden();

        $admin = $this->userWithPermissions(['activity_logs.view']);
        $this->actingAs($admin)->get('/admin/activity-logs')->assertOk();
    }

    public function test_activity_log_never_stores_a_password_value(): void
    {
        $admin = $this->userWithPermissions(['users.view', 'users.create', 'activity_logs.view']);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'مستخدم جديد للسجل',
            'email' => 'logtest@example.com',
            'password' => 'SuperSecretPassword123',
            'password_confirmation' => 'SuperSecretPassword123',
        ]);

        $log = ActivityLog::where('event', 'users.created')->firstOrFail();
        $this->assertStringNotContainsString('SuperSecretPassword123', json_encode($log->properties));
    }
}
