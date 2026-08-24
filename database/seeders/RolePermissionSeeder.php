<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::where('name', 'Admin')->first();

        if (!$role) {
            return;
        }

        $permissions = Permission::whereIn('name', [
            'dashboard',
            'ops dashboard',
            'dashboard metrics',
            'sr_calculator',
            'client_fee',
            'sub_store',
            'permission',
            'payin',
            'reversals',
            'payout',
            'reversed_payin',
            'settlement',
            'wallet_history',
            'archive_folders',
            'payin_search',
            'payout_search',
            'export_transaction',
            'setting',
            'api_docs',
        ])->get();

        $role->syncPermissions($permissions);
    }
}
