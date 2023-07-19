<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = Role::create(['name' => 'admin']);
        $user = Role::create(['name' => 'user']);

        User::create([
            "first_name" => "Admin",
            "last_name" => "Admin",
            "email" => "admin@gmail.com",
            "password" => bcrypt("password123"),
            "email_verified_at" => now(),
        ])->assignRole($admin);
    }
}
