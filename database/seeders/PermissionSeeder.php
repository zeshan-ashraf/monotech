<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'dashboard',
            'ops_dashboard',
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
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }
}
