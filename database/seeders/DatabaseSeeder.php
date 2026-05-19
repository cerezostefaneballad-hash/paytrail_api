<?php
namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Admin::create([
            'username'  => 'admin',
            'full_name' => 'System Administrator',
            'password'  => Hash::make('admin123'),
        ]);

        Setting::create([
            'acad_year'     => '2025-2026',
            'semester'      => '1st sem',
            'semestral_fee' => 300.00,
        ]);
    }
}