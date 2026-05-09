<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@honey-store.test'],
            [
                'name' => 'Honey Store Admin',
                'password' => 'password',
                'role' => 'admin',
            ],
        );
    }
}