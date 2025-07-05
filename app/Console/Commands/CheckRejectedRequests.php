<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CheckRejectedRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'requests:check-rejected {--hours=24 : Number of hours to look back} {--ip= : Filter by specific IP}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check rejected requests from the log file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $logPath = storage_path('logs/rejected_requests.log');
        
        if (!File::exists($logPath)) {
            $this->error('Rejected requests log file not found: ' . $logPath);
            return 1;
        }

        $hours = $this->option('hours');
        $filterIp = $this->option('ip');
        
        $this->info("Checking rejected requests from the last {$hours} hours...");
        if ($filterIp) {
            $this->info("Filtering by IP: {$filterIp}");
        }

        $content = File::get($logPath);
        $lines = explode("\n", $content);
        
        $rejectedCount = 0;
        $rateLimitedCount = 0;
        $otherErrorsCount = 0;
        $ipStats = [];

        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            
            // Check if line contains log entry (basic check)
            if (strpos($line, 'rejected_requests') !== false) {
                $rejectedCount++;
                
                // Extract IP if present
                if (preg_match('/"ip":"([^"]+)"/', $line, $matches)) {
                    $ip = $matches[1];
                    $ipStats[$ip] = ($ipStats[$ip] ?? 0) + 1;
                }
                
                // Check for rate limiting
                if (strpos($line, 'Rate limit exceeded') !== false || strpos($line, 'rate_limited_') !== false) {
                    $rateLimitedCount++;
                } else {
                    $otherErrorsCount++;
                }
            }
        }

        $this->info("\n=== Rejected Requests Summary ===");
        $this->info("Total rejected requests: {$rejectedCount}");
        $this->info("Rate limited requests: {$rateLimitedCount}");
        $this->info("Other errors: {$otherErrorsCount}");

        if (!empty($ipStats)) {
            $this->info("\n=== Top IPs with Rejected Requests ===");
            arsort($ipStats);
            $count = 0;
            foreach ($ipStats as $ip => $count) {
                if ($count > 0) {
                    $this->line("{$ip}: {$count} requests");
                    $count++;
                    if ($count >= 10) break; // Show top 10
                }
            }
        }

        $this->info("\nTo view detailed logs, check: " . $logPath);
        
        return 0;
    }
} 