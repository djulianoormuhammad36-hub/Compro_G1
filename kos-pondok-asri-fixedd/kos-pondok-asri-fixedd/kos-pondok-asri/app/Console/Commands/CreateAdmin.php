<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdmin extends Command
{
    protected $signature = 'admin:create {email : Email admin} {--name=Pengelola Kos : Nama tampilan}';

    protected $description = 'Membuat atau memperbarui akun admin (password diminta secara interaktif)';

    public function handle(): int
    {
        $password = $this->secret('Password (min. 8 karakter)');

        if (strlen((string) $password) < 8) {
            $this->error('Password minimal 8 karakter.');

            return self::FAILURE;
        }

        $user = User::firstOrNew(['email' => $this->argument('email')]);
        $user->name = $this->option('name');
        $user->password = Hash::make($password);
        $user->is_admin = true;
        $user->email_verified_at ??= now();
        $user->save();

        $this->info("Admin {$user->email} siap digunakan.");

        return self::SUCCESS;
    }
}
