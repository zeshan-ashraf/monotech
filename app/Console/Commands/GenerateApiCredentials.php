<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateApiCredentials extends Command
{
    protected $signature = 'api:generate-credentials {user_id? : The ID of the user to generate credentials for}';
    protected $description = 'Generate API credentials for users';

    public function handle()
    {
        if ($userId = $this->argument('user_id')) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User with ID {$userId} not found.");
                return 1;
            }
            $this->generateCredentials($user);
        } else {
            $users = User::whereNull('api_key')->get();
            if ($users->isEmpty()) {
                $this->info('No users found without API credentials.');
                return 0;
            }
            foreach ($users as $user) {
                $this->generateCredentials($user);
            }
        }
        return 0;
    }

    protected function generateCredentials(User $user)
    {
        $user->api_key = Str::random(32);
        $user->api_secret = Str::random(64);
        $user->save();

        $this->info("Generated API credentials for user {$user->id}:");
        $this->line("API Key: {$user->api_key}");
        $this->line("API Secret: {$user->api_secret}");
        $this->newLine();
    }
} 