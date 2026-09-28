<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Create or update a sign-in account.
 *
 *   php artisan ptt:user ana@pacifictt.com "Ana Lopez" assistant
 *
 * Prints a generated password once; it is never stored in plain text.
 */
class CreateUser extends Command
{
    protected $signature = 'ptt:user {email} {name} {role=assistant} {--deactivate : Block this account from signing in}';

    protected $description = 'Create or update an owner/assistant account';

    public function handle(): int
    {
        $role = $this->argument('role');
        if (! array_key_exists($role, User::ROLES)) {
            $this->error('Role must be one of: '.implode(', ', array_keys(User::ROLES)));

            return self::FAILURE;
        }

        $user = User::firstOrNew(['email' => strtolower($this->argument('email'))]);
        $isNew = ! $user->exists;
        $password = null;

        $user->name = $this->argument('name');
        $user->role = $role;
        $user->is_active = ! $this->option('deactivate');
        if ($isNew) {
            $password = Str::password(14, symbols: false);
            $user->password = $password;
        }
        $user->save();

        $this->info(($isNew ? 'Created' : 'Updated')." {$user->roleLabel()} account {$user->email}".($user->is_active ? '' : ' (deactivated)'));
        if ($password) {
            $this->line("Temporary password: {$password}");
        }

        return self::SUCCESS;
    }
}
