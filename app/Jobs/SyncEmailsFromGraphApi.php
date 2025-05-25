<?php

namespace App\Jobs;

use App\Models\Email;
use App\Services\MicrosoftGraphService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncEmailsFromGraphApi implements ShouldQueue
{
    use Queueable;

    private $batchSize;

    private $syncOnlyRecent;

    /**
     * Create a new job instance.
     */
    public function __construct(int $batchSize = 50, bool $syncOnlyRecent = true)
    {
        $this->batchSize      = $batchSize;
        $this->syncOnlyRecent = $syncOnlyRecent;
    }

    /**
     * Execute the job.
     */
    public function handle(MicrosoftGraphService $graphService): void
    {
        Log::info('🔄 Starting email sync from Microsoft Graph API', [
            'batch_size'       => $this->batchSize,
            'sync_only_recent' => $this->syncOnlyRecent,
        ]);

        if (! $graphService->isAuthenticated()) {
            Log::error('❌ Microsoft Graph service not authenticated - skipping sync');

            return;
        }

        try {
            // Get emails from Graph API
            $result = $graphService->getRecentEmails($this->batchSize);

            if (! $result['success']) {
                Log::error('❌ Failed to fetch emails from Graph API', [
                    'error' => $result['error'],
                ]);

                return;
            }

            $emails                  = $result['emails'];
            $newEmails               = 0;
            $updatedEmails           = 0;
            $aiScreeningQueuedEmails = 0;

            Log::info('📧 Processing ' . count($emails) . ' emails from Graph API');

            foreach ($emails as $emailData) {
                // Get full email content for this email
                $fullEmailResult = $graphService->getEmail($emailData['id']);

                if (! $fullEmailResult['success']) {
                    Log::warning("⚠️ Failed to get full content for email {$emailData['id']}", [
                        'error' => $fullEmailResult['error'],
                    ]);

                    continue;
                }

                $fullEmail = $fullEmailResult['email'];

                // Check if email already exists
                $existingEmail = Email::where('graph_id', $emailData['id'])->first();

                if ($existingEmail) {
                    // Update existing email if needed
                    $updated = $this->updateExistingEmail($existingEmail, $emailData, $fullEmail);
                    if ($updated) {
                        $updatedEmails++;
                    }
                } else {
                    // Create new email
                    $email = $this->createNewEmail($emailData, $fullEmail);
                    $newEmails++;

                    // Check if email is eligible for AI processing
                    $isEligible         = $this->shouldProcessWithAi($email);
                    $email->ai_eligible = $isEligible;

                    if ($isEligible) {
                        $email->ai_status = Email::AI_STATUS_PENDING;
                        ScreenEmailWithAi::dispatch($email->id);
                        $aiScreeningQueuedEmails++;
                        Log::info('🔍 Queued email for AI screening', [
                            'email_id' => $email->id,
                            'subject'  => $email->subject,
                        ]);
                    } else {
                        $email->ai_status = Email::AI_STATUS_NOT_ELIGIBLE; // Not eligible for AI processing
                    }

                    $email->save();
                }
            }

            Log::info('✅ Email sync completed', [
                'new_emails'                 => $newEmails,
                'updated_emails'             => $updatedEmails,
                'ai_screening_queued_emails' => $aiScreeningQueuedEmails,
                'total_processed'            => count($emails),
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Email sync failed with exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Create a new email record
     */
    private function createNewEmail(array $emailData, array $fullEmail): Email
    {
        // Extract body content
        $bodyContent     = '';
        $bodyContentType = 'html';

        if (isset($fullEmail['body'])) {
            $bodyContent     = $fullEmail['body']['content'] ?? '';
            $bodyContentType = strtolower($fullEmail['body']['contentType'] ?? 'html');
        }

        // Extract recipients
        $toRecipients = [];
        if (isset($fullEmail['toRecipients'])) {
            $toRecipients = collect($fullEmail['toRecipients'])->map(function ($recipient) {
                return [
                    'name'  => $recipient['emailAddress']['name'] ?? '',
                    'email' => $recipient['emailAddress']['address'] ?? '',
                ];
            })->toArray();
        }

        return Email::create([
            'graph_id'          => $emailData['id'],
            'email_id'          => $emailData['id'], // Keep compatibility
            'subject'           => $emailData['subject'] ?? 'No Subject',
            'from_name'         => $emailData['from_name'],
            'from_email'        => $emailData['from_email'],
            'to_recipients'     => $toRecipients,
            'received_at'       => Carbon::parse($emailData['received_at']),
            'is_read'           => $emailData['is_read'],
            'has_attachments'   => $emailData['has_attachments'],
            'body_preview'      => $emailData['preview'],
            'body_content'      => $bodyContent,
            'body_content_type' => $bodyContentType,
            'last_synced_at'    => Carbon::now(),
            'is_synced'         => true,
        ]);
    }

    /**
     * Update existing email if needed
     */
    private function updateExistingEmail(Email $email, array $emailData, array $fullEmail): bool
    {
        $updated = false;

        // Check if read status changed
        if ($email->is_read !== $emailData['is_read']) {
            $email->is_read = $emailData['is_read'];
            $updated        = true;
        }

        // Update sync timestamp
        $email->last_synced_at = Carbon::now();
        $email->is_synced      = true;

        if ($updated) {
            $email->save();
            Log::info('📝 Updated email', [
                'email_id' => $email->id,
                'graph_id' => $email->graph_id,
                'subject'  => $email->subject,
            ]);
        }

        return $updated;
    }

    /**
     * Determine if email should be processed with AI
     */
    private function shouldProcessWithAi(Email $email): bool
    {
        // Only process recent unread emails
        $isRecent = $email->received_at && $email->received_at->isAfter(Carbon::now()->subDays(2));
        // $needsProcessing = $email->needsAiProcessing();

        // Skip automated emails (newsletters, notifications, etc.)
        $subject   = strtolower($email->subject ?? '');
        $fromEmail = strtolower($email->from_email ?? '');

        $isAutomated = str_contains($subject, 'newsletter') ||
                      str_contains($subject, 'notification') ||
                      str_contains($subject, 'no-reply') ||
                      str_contains($fromEmail, 'noreply') ||
                      str_contains($fromEmail, 'no-reply');

        return $isRecent && ! $isAutomated;
    }

    /**
     * The job failed to process.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('❌ SyncEmailsFromGraphApi job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
