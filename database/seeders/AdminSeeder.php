<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/** One demo admin account, used to review reports and change their status (F4). */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrNew(['email' => 'admin@nagarbondhu.test']);
        $admin->fill(['name' => 'City Admin', 'password' => 'password']);
        $admin->role = User::ROLE_ADMIN;
        $admin->save();
    }
}
