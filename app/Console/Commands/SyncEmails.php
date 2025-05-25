<?php

namespace App\Console\Commands;

use App\Jobs\SyncEmailsFromGraphApi;
use App\Models\Email;
use Illuminate\Console\Command;

class SyncEmails extends Command
{
    protected $signature = 'emails:sync {--batch-size=50 : Number of emails to sync} {--force : Force sync even recent emails}';

    protected $description = 'Sync emails from Microsoft Graph API and queue for AI processing';

    public function handle(): int
    {
        $batchSize      = (int) $this->option('batch-size');
        $syncOnlyRecent = ! $this->option('force');

        $this->info('🔄 Dispatching email sync job...');
        $this->info("📧 Batch size: {$batchSize}");
        $this->info('⏱️ Sync only recent: ' . ($syncOnlyRecent ? 'Yes' : 'No'));

        SyncEmailsFromGraphApi::dispatch($batchSize, $syncOnlyRecent);

        $this->info('✅ Email sync job dispatched to queue');
        $this->showEmailStats();

        return self::SUCCESS;
    }

    private function showEmailStats()
    {
        $this->newLine();
        $this->info('📊 Current Email Statistics:');

        $totalEmails    = Email::count();
        $unreadEmails   = Email::unread()->count();
        $pendingAi      = Email::where('ai_status', Email::AI_STATUS_PENDING)->count();
        $screeningAi    = Email::where('ai_status', Email::AI_STATUS_SCREENING)->count();
        $processingAi   = Email::where('ai_status', Email::AI_STATUS_PROCESSING)->count();
        $completedAi    = Email::where('ai_status', Email::AI_STATUS_COMPLETED)->count();
        $screenedOnlyAi = Email::where('ai_status', Email::AI_STATUS_SCREENED_ONLY)->count();
        $failedAi       = Email::where('ai_status', Email::AI_STATUS_FAILED)->count();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Emails', $totalEmails],
                ['Unread Emails', $unreadEmails],
                ['Pending AI Screening', $pendingAi],
                ['AI Screening in Progress', $screeningAi],
                ['AI Processing (Full)', $processingAi],
                ['AI Processed (Full)', $completedAi],
                ['AI Screened Only', $screenedOnlyAi],
                ['AI Failed', $failedAi],
            ]
        );

        if ($pendingAi > 0) {
            $this->warn("⚠️ {$pendingAi} emails are pending AI screening");
        }

        if ($screeningAi > 0) {
            $this->info("🔍 {$screeningAi} emails are currently being screened");
        }

        if ($processingAi > 0) {
            $this->info("🤖 {$processingAi} emails are currently being processed with full AI analysis");
        }

        if ($failedAi > 0) {
            $this->error("❌ {$failedAi} emails failed AI processing");
        }
    }
}
