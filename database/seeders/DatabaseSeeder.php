<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Roles create karo
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $merchantRole = Role::firstOrCreate(['name' => 'merchant']);

        // Ek Admin user banao
        $admin = User::firstOrCreate(
            ['email' => 'admin@formzy.test'],
            [
                'name' => 'Formzy Admin',
                'password' => bcrypt('Admin@123'),
            ]
        );
        $admin->assignRole($adminRole);
    }
}