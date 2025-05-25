<?php

namespace App\Repositories;

use App\Contracts\EmailRepositoryInterface;
use App\Models\Email;
use Illuminate\Database\Eloquent\Collection;

class EmailRepository implements EmailRepositoryInterface
{
    public function getRecentEmails(int $limit = 50): Collection
    {
        return Email::orderBy('received_at', 'desc')
            ->take($limit)
            ->get();
    }

    public function findByGraphId(string $graphId): ?Email
    {
        return Email::where('graph_id', $graphId)->first();
    }

    public function search(string $query, int $limit = 50): Collection
    {
        return Email::where(function ($q) use ($query) {
            $q->where('subject', 'like', "%{$query}%")
                ->orWhere('from_name', 'like', "%{$query}%")
                ->orWhere('from_email', 'like', "%{$query}%")
                ->orWhere('body_preview', 'like', "%{$query}%")
                ->orWhere('body_content', 'like', "%{$query}%");
        })
            ->orderBy('received_at', 'desc')
            ->take($limit)
            ->get();
    }

    public function updateReadStatus(Email $email, bool $isRead): void
    {
        $email->update([
            'is_read'        => $isRead,
            'last_synced_at' => now(),
        ]);
    }
}
