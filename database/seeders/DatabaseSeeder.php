<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::query()->firstOrCreate(
            ['code' => 'MAIN'],
            [
                'name' => 'TPT2 - Cơ sở chính',
                'address' => 'Hà Nội',
                'phone' => '0900000000',
                'is_active' => true,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'admin@crm.local'],
            [
                'name' => 'admin',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'branch_id' => $branch->id,
                'is_active' => true,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'sales@crm.local'],
            [
                'name' => 'Nhân viên Sales',
                'password' => Hash::make('password'),
                'role' => 'sales',
                'branch_id' => $branch->id,
                'is_active' => true,
            ]
        );

        Setting::set('center_name', 'CRM Trung Tâm');
        Setting::set('logo_text', 'TPT2');
    }
}
