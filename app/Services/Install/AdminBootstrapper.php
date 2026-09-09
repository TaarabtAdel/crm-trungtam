<?php

namespace App\Services\Install;

use App\Models\Branch;
use App\Models\Setting;
use App\Models\User;
use App\Support\Permissions;
use Database\Seeders\NotificationTemplateSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminBootstrapper
{
    /**
     * @param  array{center_name:string,admin_name:string,admin_email:string,admin_password:string}  $data
     */
    public function bootstrap(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $branch = Branch::query()->updateOrCreate(
                ['code' => 'MAIN'],
                [
                    'name' => $data['center_name'],
                    'address' => '',
                    'phone' => '',
                    'is_active' => true,
                ]
            );

            $user = User::query()->updateOrCreate(
                ['email' => $data['admin_email']],
                [
                    'name' => $data['admin_name'],
                    'password' => Hash::make($data['admin_password']),
                    'role' => 'super_admin',
                    'branch_id' => $branch->id,
                    'is_active' => true,
                ]
            );
            $user->syncRoles(['super_admin']);

            Setting::set('center_name', $data['center_name']);
            Setting::set('logo_text', mb_substr($data['center_name'], 0, 12));
            Setting::set('app_schema_version', config('app.schema_version', '1.0'));

            $this->seedRolePermissions();
            $this->seedCommissionRule();
            (new NotificationTemplateSeeder)->run();

            return $user->fresh();
        });
    }

    protected function seedRolePermissions(): void
    {
        if (DB::table('role_permissions')->exists()) {
            return;
        }

        $defaults = config('permissions.defaults', []);
        foreach ($defaults as $role => $permissions) {
            if ($role === 'super_admin') {
                continue;
            }
            Permissions::syncRole($role, $permissions);
        }
    }

    protected function seedCommissionRule(): void
    {
        if (DB::table('commission_rules')->exists()) {
            return;
        }

        DB::table('commission_rules')->insert([
            'scope' => 'global',
            'class_id' => null,
            'percent' => 5,
            'tier_min_revenue' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
