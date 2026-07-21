<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class TalashowMakeAdmin extends Command
{
    protected $signature = 'talashow:make-admin {email} {--name=Admin} {--password=} {--reset-password : Met à jour le mot de passe même si le compte existe}';
    protected $description = 'Créer (ou promouvoir) un utilisateur admin Talashow';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $name = (string) $this->option('name');
        $password = (string) ($this->option('password') ?: 'password');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $roleAdmin = Role::firstOrCreate(['key' => 'admin'], ['name' => 'Admin']);
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'is_admin' => true,
                'role_id' => $roleAdmin->id,
                'coins' => 0,
                'reward_coins' => 0,
            ]);
            $this->info("Admin créé: {$user->email} (password: {$password})");
            return self::SUCCESS;
        }

        $roleAdmin = Role::firstOrCreate(['key' => 'admin'], ['name' => 'Admin']);
        $user->is_admin = true;
        $user->role_id = $roleAdmin->id;
        if (! $user->password || $this->option('reset-password')) {
            $user->password = Hash::make($password);
        }
        $user->save();

        $msg = $this->option('reset-password')
            ? "Mot de passe mis à jour pour: {$user->email}"
            : "Utilisateur promu admin: {$user->email}";
        $this->info($msg);
        return self::SUCCESS;
    }
}

