<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds Export Payin + API DOC permissions and assigns them to Super Admin.
 *
 * Run: php artisan db:seed --class=ExportPayinAndApiDocPermissionSeeder
 */
class ExportPayinAndApiDocPermissionSeeder extends Seeder
{
    public const PERMISSIONS = [
        'Export Payin',
        'API DOC',
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = config('auth.defaults.guard', 'web');
        $permissions = [];

        foreach (self::PERMISSIONS as $name) {
            $permissions[] = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => $guard,
            ]);
        }

        $role = Role::where('name', 'Super Admin')
            ->where('guard_name', $guard)
            ->first();

        if (!$role) {
            $role = Role::where('name', 'Super Admin')->first();
        }

        if ($role) {
            $role->givePermissionTo($permissions);
            $this->command?->info('Assigned permissions to role: Super Admin');
        } else {
            $this->command?->warn('Role "Super Admin" not found — assigning directly to Super Admin users.');
        }

        User::query()
            ->where('user_role', 'Super Admin')
            ->get()
            ->each(function (User $user) use ($permissions) {
                $user->givePermissionTo($permissions);
            });

        $this->command?->info('Seeded permissions: ' . implode(', ', self::PERMISSIONS));
    }
}
