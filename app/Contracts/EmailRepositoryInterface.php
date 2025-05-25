<?php

namespace App\Contracts;

use App\Models\Email;
use Illuminate\Database\Eloquent\Collection;

interface EmailRepositoryInterface
{
    public function getRecentEmails(int $limit = 50): Collection;

    public function findByGraphId(string $graphId): ?Email;

    public function search(string $query, int $limit = 50): Collection;

    public function updateReadStatus(Email $email, bool $isRead): void;
}
