<?php

namespace App\Services;

use App\Contracts\EmailRepositoryInterface;
use App\DTOs\EmailStatsDTO;
use App\Http\Resources\EmailDetailResource;
use App\Http\Resources\EmailResource;
use App\Jobs\SyncEmailsFromGraphApi;
use Illuminate\Database\Eloquent\Collection;

class EmailService
{
    public function __construct(
        private EmailRepositoryInterface $emailRepository,
        private MicrosoftGraphService $graphService
    ) {}

    public function getRecentEmails(): array
    {
        $emails = $this->emailRepository->getRecentEmails();

        return EmailResource::collection($emails)->resolve();
    }

    public function getEmailDetail(string $graphId): ?array
    {
        $email = $this->emailRepository->findByGraphId($graphId);

        if (! $email) {
            return null;
        }

        return (new EmailDetailResource($email))->resolve();
    }

    public function searchEmails(string $query): array
    {
        if (empty($query)) {
            return $this->getRecentEmails();
        }

        $emails = $this->emailRepository->search($query);

        return EmailResource::collection($emails)->resolve();
    }

    public function markAsRead(string $graphId): array
    {
        $email = $this->emailRepository->findByGraphId($graphId);

        if (! $email) {
            return [
                'success' => false,
                'error'   => 'Email not found',
            ];
        }

        $result = $this->graphService->markAsRead($graphId);

        if ($result['success']) {
            $this->emailRepository->updateReadStatus($email, true);

            return [
                'success' => true,
                'message' => 'Email marked as read',
            ];
        }

        return [
            'success' => false,
            'error'   => $result['error'],
        ];
    }

    public function markAsUnread(string $graphId): array
    {
        $email = $this->emailRepository->findByGraphId($graphId);

        if (! $email) {
            return [
                'success' => false,
                'error'   => 'Email not found',
            ];
        }

        $result = $this->graphService->markAsUnread($graphId);

        if ($result['success']) {
            $this->emailRepository->updateReadStatus($email, false);

            return [
                'success' => true,
                'message' => 'Email marked as unread',
            ];
        }

        return [
            'success' => false,
            'error'   => $result['error'],
        ];
    }

    public function toggleReadStatus(string $graphId, bool $currentReadStatus): array
    {
        $email = $this->emailRepository->findByGraphId($graphId);

        if (! $email) {
            return [
                'success' => false,
                'error'   => 'Email not found',
            ];
        }

        $result = $this->graphService->toggleReadStatus($graphId, $currentReadStatus);

        if ($result['success']) {
            $this->emailRepository->updateReadStatus($email, ! $currentReadStatus);

            return [
                'success'    => true,
                'message'    => $result['message'],
                'new_status' => ! $currentReadStatus,
            ];
        }

        return [
            'success' => false,
            'error'   => $result['error'],
        ];
    }

    public function syncEmails(): array
    {
        if (! $this->graphService->isAuthenticated()) {
            return [
                'success' => false,
                'error'   => 'Not authenticated with Microsoft Graph',
            ];
        }

        SyncEmailsFromGraphApi::dispatch(50, false);

        return [
            'success' => true,
            'message' => 'Email sync job dispatched',
        ];
    }

    public function getStats(): EmailStatsDTO
    {
        return EmailStatsDTO::fromDatabase();
    }

    public function getSearchStats(Collection $emails): EmailStatsDTO
    {
        return EmailStatsDTO::fromSearchResults($emails->count());
    }
}
