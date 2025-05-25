<?php

namespace App\Console\Commands;

use App\Models\Email;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateEmailAiEligibility extends Command
{
    protected $signature = 'emails:update-ai-eligibility';

    protected $description = 'Update ai_eligible field for all existing emails based on AI processing criteria';

    public function handle(): int
    {
        $this->info('🔄 Updating AI eligibility for all emails...');

        $emails   = Email::all();
        $updated  = 0;
        $eligible = 0;

        foreach ($emails as $email) {
            $isEligible = $this->shouldProcessWithAi($email);

            if ($email->ai_eligible !== $isEligible) {
                $email->ai_eligible = $isEligible;

                if ($isEligible && $email->ai_status === null) {
                    $email->ai_status = Email::AI_STATUS_PENDING;
                } elseif (! $isEligible) {
                    $email->ai_status = Email::AI_STATUS_NOT_ELIGIBLE;
                }

                $email->save();
                $updated++;
            }

            if ($isEligible) {
                $eligible++;
            }
        }

        $this->info("✅ Updated {$updated} emails");
        $this->info("📊 Total AI eligible emails: {$eligible}");

        return 0;
    }

    private function isAutomatedEmail(Email $email): bool
    {
        $subject   = strtolower($email->subject ?? '');
        $fromEmail = strtolower($email->from_email ?? '');

        return str_contains($subject, 'newsletter') ||
               str_contains($subject, 'notification') ||
               str_contains($subject, 'no-reply') ||
               str_contains($fromEmail, 'noreply') ||
               str_contains($fromEmail, 'no-reply');
    }

    private function shouldProcessWithAi(Email $email): bool
    {
        $isRecent        = $email->received_at && $email->received_at->isAfter(Carbon::now()->subDays(7));
        $isUnread        = ! $email->is_read;
        $needsProcessing = $email->needsAiProcessing() || $email->ai_status === null || $email->ai_status === Email::AI_STATUS_NOT_ELIGIBLE;

        $isAutomated = $this->isAutomatedEmail($email);

        return $isRecent && $isUnread && $needsProcessing && ! $isAutomated;
    }
}
