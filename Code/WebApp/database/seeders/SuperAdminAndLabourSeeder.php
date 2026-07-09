<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminAndLabourSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Delete existing Super Admin to avoid duplicate email errors
        User::where('email', 'superadmin@example.com')->delete();

        // Create Super Admin user
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('admin123'),
            'role' => 'super-admin',
            'is_active' => true,
        ]);

        // Delete existing Verifier
        User::where('email', 'verifier@example.com')->delete();

        // Create Verifier user
        User::create([
            'name' => 'Verifier User',
            'email' => 'verifier@example.com',
            'password' => Hash::make('verifier123'),
            'role' => 'verifier',
            'is_active' => true,
        ]);

        // Delete existing Approver
        User::where('email', 'approver@example.com')->delete();

        // Create Approver user
        User::create([
            'name' => 'Approver User',
            'email' => 'approver@example.com',
            'password' => Hash::make('approver123'),
            'role' => 'approver',
            'is_active' => true,
        ]);
    }
}
