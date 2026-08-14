<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * The only supported way to create the first administrator account. Never
 * documented as "insert a row via tinker/SQL" — that path skips validation
 * and password hashing review, and is easy to get subtly wrong (e.g. a
 * plain-text password column). Safe to re-run: it can also be used later
 * to create additional super-admin accounts.
 */
class CreateAdminCommand extends Command
{
    protected $signature = 'make:admin';

    protected $description = 'إنشاء حساب مسؤول عام (super-admin) بشكل آمن';

    public function handle(): int
    {
        $name = $this->ask('اسم المسؤول');
        $email = $this->ask('البريد الإلكتروني');
        $password = $this->secret('كلمة المرور (12 حرفًا على الأقل)');
        $passwordConfirmation = $this->secret('تأكيد كلمة المرور');

        $validator = Validator::make(
            compact('name', 'email', 'password', 'passwordConfirmation'),
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:12'],
                'passwordConfirmation' => ['required', 'same:password'],
            ],
            [
                'passwordConfirmation.same' => 'كلمة المرور وتأكيدها غير متطابقين.',
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $superAdminRole = Role::where('name', 'super-admin')->first();
        if (! $superAdminRole) {
            $this->error('الدور super-admin غير موجود. شغّل "php artisan db:seed --class=AccessControlSeeder" أولًا.');

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'is_active' => true,
        ]);
        $user->roles()->attach($superAdminRole->id, ['assigned_at' => now()]);

        $this->info("تم إنشاء حساب المسؤول بنجاح: {$user->email}");

        return self::SUCCESS;
    }
}
