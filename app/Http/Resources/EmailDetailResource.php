<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmailDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'      => $this->graph_id,
            'subject' => $this->subject,
            'from'    => [
                'emailAddress' => [
                    'name'    => $this->from_name,
                    'address' => $this->from_email,
                ],
            ],
            'toRecipients'     => $this->to_recipients ?? [],
            'receivedDateTime' => $this->received_at,
            'isRead'           => $this->is_read,
            'hasAttachments'   => $this->has_attachments,
            'body'             => [
                'content'     => $this->body_content ?? $this->body_preview,
                'contentType' => $this->body_content_type,
            ],
            // AI specific fields
            'ai_status'       => $this->ai_status,
            'ai_status_color' => $this->getAiStatusBadgeColor(),
            'ai_response'     => $this->ai_response,
            'ai_actions'      => $this->ai_actions ?? [],
            'ai_processed_at' => $this->ai_processed_at,
            'ai_error'        => $this->ai_error,
        ];
    }
}
