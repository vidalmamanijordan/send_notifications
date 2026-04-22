<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superadmin = User::updateOrCreate(
            ['email' => 'vidal_mamani@upeu.edu.pe'],
            [
                'name' => 'Vidal Mamani Jordan',
                'password' => Hash::make('SendNotif2026'),
                'email_verified_at' => now(),
                'remember_token' => \Illuminate\Support\Str::random(10),
            ]
        );
        $superadmin->syncRoles('superadmin');
    }
}
