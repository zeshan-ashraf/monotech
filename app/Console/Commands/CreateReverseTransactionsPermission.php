<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;

class CreateReverseTransactionsPermission extends Command
{
    protected $signature = 'permission:create-reverse-transactions';
    protected $description = 'Create Reverse Transactions permission';

    public function handle()
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'Reverse Transactions', 'guard_name' => 'user'],
            ['name' => 'Reverse Transactions', 'guard_name' => 'user']
        );

        if ($permission->wasRecentlyCreated) {
            $this->info('Permission "Reverse Transactions" created successfully.');
        } else {
            $this->info('Permission "Reverse Transactions" already exists.');
        }

        return 0;
    }
}
