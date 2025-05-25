<?php

namespace App\Http\Controllers;

use App\Http\Requests\ToggleReadStatusRequest;
use App\Services\EmailService;
use Illuminate\Http\JsonResponse;

class EmailActionController extends Controller
{
    public function __construct(
        private EmailService $emailService
    ) {}

    public function markAsRead(string $graphId): JsonResponse
    {
        $result = $this->emailService->markAsRead($graphId);

        return response()->json($result, $result['success'] ? 200 : 404);
    }

    public function markAsUnread(string $graphId): JsonResponse
    {
        $result = $this->emailService->markAsUnread($graphId);

        return response()->json($result, $result['success'] ? 200 : 404);
    }

    public function toggleReadStatus(ToggleReadStatusRequest $request, string $graphId): JsonResponse
    {
        $currentReadStatus = $request->getCurrentReadStatus();
        $result            = $this->emailService->toggleReadStatus($graphId, $currentReadStatus);

        return response()->json($result, $result['success'] ? 200 : 404);
    }

    public function syncEmails(): JsonResponse
    {
        $result = $this->emailService->syncEmails();

        return response()->json($result, $result['success'] ? 200 : 401);
    }

    public function aiStats(): JsonResponse
    {
        $stats = $this->emailService->getStats();

        return response()->json($stats->toArray());
    }
}
