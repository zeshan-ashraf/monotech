<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateReverseTransactionsPermission extends Command
{
    protected $signature = 'permission:create-reverse-transactions';
    protected $description = 'Create Reverse Transactions permission';

    public function handle()
    {
        try {
            // Check if permission already exists
            $exists = DB::table('permissions')
                ->where('name', 'Reverse Transactions')
                ->where('guard_name', 'web')
                ->exists();

            if ($exists) {
                $this->info('Permission "Reverse Transactions" already exists.');
                return 0;
            }

            // Insert the new permission
            DB::table('permissions')->insert([
                'name' => 'Reverse Transactions',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->info('Permission "Reverse Transactions" created successfully.');
            return 0;
        } catch (\Exception $e) {
            $this->error('Error creating permission: ' . $e->getMessage());
            return 1;
        }
    }
}
