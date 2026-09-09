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
        )->syncRoles(['super_admin']);

        User::query()->updateOrCreate(
            ['email' => 'sales@crm.local'],
            [
                'name' => 'Nhân viên Sales',
                'password' => Hash::make('password'),
                'role' => 'sales',
                'branch_id' => $branch->id,
                'is_active' => true,
            ]
        )->syncRoles(['sales']);

        User::query()->updateOrCreate(
            ['email' => 'daotao@crm.local'],
            [
                'name' => 'Nhân viên Đào tạo',
                'password' => Hash::make('password'),
                'role' => 'training',
                'branch_id' => $branch->id,
                'is_active' => true,
            ]
        )->syncRoles(['training']);

        $teacherUser = User::query()->updateOrCreate(
            ['email' => 'teacher@crm.local'],
            [
                'name' => 'Giáo viên Demo',
                'password' => Hash::make('password'),
                'role' => 'teacher',
                'branch_id' => $branch->id,
                'is_active' => true,
            ]
        );
        $teacherUser->syncRoles(['teacher']);

        \App\Models\Teacher::query()->updateOrCreate(
            ['email' => 'teacher@crm.local'],
            [
                'branch_id' => $branch->id,
                'name' => 'Giáo viên Demo',
                'phone' => '0900000001',
                'specialty' => 'Tiếng Anh',
                'hourly_rate' => 200000,
                'status' => 'active',
            ]
        );

        Setting::set('center_name', 'CRM Trung Tâm');
        Setting::set('logo_text', 'TPT2');

        $this->call(NotificationTemplateSeeder::class);
    }
}
