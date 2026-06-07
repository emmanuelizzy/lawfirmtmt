<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        User::factory()->create([
            'name'  => 'Super Admin',
            'email' => 'superadmin@igxtelle.test',
        ])->assignRole('Super Admin');

        User::factory()->create([
            'name'  => 'Admin User',
            'email' => 'admin@igxtelle.test',
        ])->assignRole('Admin');

        User::factory()->create([
            'name'  => 'Ops Manager',
            'email' => 'ops@igxtelle.test',
        ])->assignRole('Operations Manager');

        User::factory()->create([
            'name'  => 'Staff Member',
            'email' => 'staff@igxtelle.test',
        ])->assignRole('Staff');
    }
}
