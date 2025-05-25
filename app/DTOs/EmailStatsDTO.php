<?php

namespace App\DTOs;

use App\Models\Email;

class EmailStatsDTO
{
    public function __construct(
        public readonly int $totalEmails,
        public readonly int $unreadEmails,
        public readonly int $pendingAi,
        public readonly int $processingAi,
        public readonly int $completedAi,
        public readonly int $failedAi,
        public readonly int $notEligibleAi,
        public readonly int $skippedAi,
        public readonly bool $isSearchResults = false,
    ) {}

    public static function fromDatabase(): self
    {
        return new self(
            totalEmails: Email::count(),
            unreadEmails: Email::unread()->count(),
            pendingAi: Email::where('ai_eligible', true)->whereIn('ai_status', ['pending', 'processing'])->count(),
            processingAi: Email::where('ai_status', Email::AI_STATUS_PROCESSING)->count(),
            completedAi: Email::where('ai_status', Email::AI_STATUS_COMPLETED)->count(),
            failedAi: Email::where('ai_status', Email::AI_STATUS_FAILED)->count(),
            notEligibleAi: Email::where('ai_status', Email::AI_STATUS_NOT_ELIGIBLE)->count(),
            skippedAi: Email::where('ai_status', Email::AI_STATUS_SKIPPED)->count(),
        );
    }

    public static function fromSearchResults(int $resultCount): self
    {
        return new self(
            totalEmails: $resultCount,
            unreadEmails: 0,
            pendingAi: 0,
            processingAi: 0,
            completedAi: 0,
            failedAi: 0,
            notEligibleAi: 0,
            skippedAi: 0,
            isSearchResults: true,
        );
    }

    public function toArray(): array
    {
        $stats = [
            'total_emails' => $this->totalEmails,
        ];

        if ($this->isSearchResults) {
            $stats['search_results'] = true;
        } else {
            $stats = array_merge($stats, [
                'unread_emails'   => $this->unreadEmails,
                'pending_ai'      => $this->pendingAi,
                'processing_ai'   => $this->processingAi,
                'completed_ai'    => $this->completedAi,
                'failed_ai'       => $this->failedAi,
                'not_eligible_ai' => $this->notEligibleAi,
                'skipped_ai'      => $this->skippedAi,
            ]);
        }

        return $stats;
    }
}
