<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
    }

    public function test_make_admin_creates_an_active_super_admin_account(): void
    {
        $this->artisan('make:admin')
            ->expectsQuestion('اسم المسؤول', 'مدير الموقع')
            ->expectsQuestion('البريد الإلكتروني', 'siteadmin@example.com')
            ->expectsQuestion('كلمة المرور (12 حرفًا على الأقل)', 'CorrectHorseBattery123')
            ->expectsQuestion('تأكيد كلمة المرور', 'CorrectHorseBattery123')
            ->assertExitCode(0);

        $admin = User::where('email', 'siteadmin@example.com')->firstOrFail();
        $this->assertTrue($admin->is_active);
        $this->assertTrue($admin->hasRole('super-admin'));
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('CorrectHorseBattery123', $admin->password));
    }

    public function test_make_admin_rejects_a_short_password(): void
    {
        $this->artisan('make:admin')
            ->expectsQuestion('اسم المسؤول', 'مدير الموقع')
            ->expectsQuestion('البريد الإلكتروني', 'shortpass@example.com')
            ->expectsQuestion('كلمة المرور (12 حرفًا على الأقل)', 'short')
            ->expectsQuestion('تأكيد كلمة المرور', 'short')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['email' => 'shortpass@example.com']);
    }

    public function test_make_admin_rejects_mismatched_password_confirmation(): void
    {
        $this->artisan('make:admin')
            ->expectsQuestion('اسم المسؤول', 'مدير الموقع')
            ->expectsQuestion('البريد الإلكتروني', 'mismatch@example.com')
            ->expectsQuestion('كلمة المرور (12 حرفًا على الأقل)', 'CorrectHorseBattery123')
            ->expectsQuestion('تأكيد كلمة المرور', 'DifferentPassword123')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['email' => 'mismatch@example.com']);
    }

    public function test_make_admin_rejects_a_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $this->artisan('make:admin')
            ->expectsQuestion('اسم المسؤول', 'مدير آخر')
            ->expectsQuestion('البريد الإلكتروني', 'existing@example.com')
            ->expectsQuestion('كلمة المرور (12 حرفًا على الأقل)', 'CorrectHorseBattery123')
            ->expectsQuestion('تأكيد كلمة المرور', 'CorrectHorseBattery123')
            ->assertExitCode(1);
    }

    /**
     * .env.testing intentionally leaves BACKUP_MYSQLDUMP_PATH unset (mysqldump
     * isn't guaranteed to be on PATH in every environment), so this exercises
     * BackupService's failure path rather than a real dump — confirming the
     * command surfaces the failure with a non-zero exit code instead of
     * silently reporting success.
     */
    public function test_backup_run_command_reports_failure_when_mysqldump_is_unavailable(): void
    {
        $this->artisan('backup:run')->assertExitCode(1);

        $this->assertDatabaseHas('backups', ['status' => 'failed']);
    }
}
