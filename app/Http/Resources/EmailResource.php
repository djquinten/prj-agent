<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->graph_id,
            'database_id'        => $this->id,
            'subject'            => $this->subject,
            'from_name'          => $this->from_name,
            'from_email'         => $this->from_email,
            'received_at'        => $this->received_at,
            'is_read'            => $this->is_read,
            'has_attachments'    => $this->has_attachments,
            'preview'            => $this->body_preview,
            'ai_status'          => $this->ai_status,
            'ai_eligible'        => $this->ai_eligible,
            'ai_status_color'    => $this->getAiStatusBadgeColor(),
            'ai_response'        => $this->ai_response,
            'ai_actions_summary' => $this->getAiActionsSummary(),
            'ai_processed_at'    => $this->ai_processed_at,
        ];
    }
}
