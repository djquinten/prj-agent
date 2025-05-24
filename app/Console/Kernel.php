<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Sync emails from Microsoft Graph API every 5 minutes
        $schedule->job(new \App\Jobs\SyncEmailsFromGraphApi(50, true))
                 ->everyFiveMinutes()
                 ->name('sync-emails-from-graph')
                 ->description('Sync emails from Microsoft Graph API and queue for AI processing')
                 ->withoutOverlapping(10); // Prevent overlapping jobs for 10 minutes

        // Optional: Clean up old processed emails (keep last 30 days)
        $schedule->call(function () {
            \App\Models\Email::where('received_at', '<', now()->subDays(30))
                             ->where('ai_status', \App\Models\Email::AI_STATUS_COMPLETED)
                             ->delete();
        })->daily()->at('02:00')->name('cleanup-old-emails');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
} 