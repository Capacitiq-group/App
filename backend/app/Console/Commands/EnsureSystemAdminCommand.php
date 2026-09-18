<?php

namespace App\Console\Commands;

use App\Enums\User\RoleTypeEnum;
use App\Enums\User\UserVerifyStatusEnum;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Creates (or repairs) the system administrator account from the
 * SYSTEM_ADMIN_* environment values.
 *
 * This replaces DatabaseSeeder for production use: the seeder relies on
 * model factories and fakerphp/faker, which are dev-only dependencies and
 * are not installed in the production image.
 */
class EnsureSystemAdminCommand extends Command
{
    protected $signature = 'app:ensure-system-admin';

    protected $description = 'Create or update the system administrator account from SYSTEM_ADMIN_* env values';

    public function handle(): int
    {
        $email = trim((string) env('SYSTEM_ADMIN_EMAIL', ''));
        $username = trim((string) env('SYSTEM_ADMIN_USER_NAME', 'admin'));
        $password = (string) env('SYSTEM_ADMIN_PASSWORD', '');

        if ($email === '' || $password === '') {
            $this->warn('SYSTEM_ADMIN_EMAIL / SYSTEM_ADMIN_PASSWORD not set — skipping.');

            return self::SUCCESS;
        }

        /** @var User|null $user */
        $user = User::withTrashed()->where('email', $email)->first();

        if (! $user) {
            $user = new User;
            $user->email = $email;
        }

        if (empty($user->uuid)) {
            $user->uuid = (string) Str::uuid();
        }

        if (empty($user->name)) {
            $user->name = $username !== '' ? $username : 'Administrator';
        }

        if (empty($user->username)) {
            $user->username = $username !== '' ? $username : 'admin';
        }

        $user->password = Hash::make($password);
        $user->verify = UserVerifyStatusEnum::VERIFIED->value;
        $user->role = RoleTypeEnum::SUPER_ADMIN->value;
        $user->deleted_at = null;
        $user->save();

        $this->info("System administrator ready: {$email}");

        return self::SUCCESS;
    }
}
