<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_id',
        'graph_id',
        'subject',
        'from_name',
        'from_email',
        'to_recipients',
        'received_at',
        'sent_at',
        'is_read',
        'has_attachments',
        'body_preview',
        'body_content',
        'body_content_type',
        'importance',
        'categories',
        'attachments',
        'last_synced_at',
        'ai_status',
        'ai_eligible',
        'ai_response',
        'ai_actions',
        'ai_error',
        'ai_processed_at',
        'ai_screening_result',
        'ai_screening_completed_at',
        'is_synced',
    ];

    protected $casts = [
        'received_at'               => 'datetime',
        'sent_at'                   => 'datetime',
        'last_synced_at'            => 'datetime',
        'ai_processed_at'           => 'datetime',
        'ai_screening_completed_at' => 'datetime',
        'is_read'                   => 'boolean',
        'has_attachments'           => 'boolean',
        'ai_eligible'               => 'boolean',
        'is_synced'                 => 'boolean',
        'to_recipients'             => 'array',
        'ai_actions'                => 'array',
        'ai_screening_result'       => 'array',
        'categories'                => 'array',
        'attachments'               => 'array',
    ];

    // AI Status constants
    const AI_STATUS_PENDING = 'pending';

    const AI_STATUS_SCREENING = 'screening';

    const AI_STATUS_PROCESSING = 'processing';

    const AI_STATUS_COMPLETED = 'completed';

    const AI_STATUS_SCREENED_ONLY = 'screened_only';

    const AI_STATUS_FAILED = 'failed';

    const AI_STATUS_SKIPPED = 'skipped';

    const AI_STATUS_NOT_ELIGIBLE = 'not_eligible';

    /**
     * Scope for emails pending AI processing
     */
    public function scopePendingAiProcessing($query)
    {
        return $query->where('ai_status', self::AI_STATUS_PENDING);
    }

    /**
     * Scope for unread emails
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for recent emails
     */
    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('received_at', '>=', Carbon::now()->subHours($hours));
    }

    /**
     * Scope for emails that need syncing
     */
    public function scopeNeedsSync($query)
    {
        return $query->where('is_synced', false);
    }

    /**
     * Mark email as processed by AI
     */
    public function markAiProcessed(string $response, array $actions = [], string $status = self::AI_STATUS_COMPLETED)
    {
        $this->update([
            'ai_status'       => $status,
            'ai_response'     => $response,
            'ai_actions'      => $actions,
            'ai_processed_at' => Carbon::now(),
            'ai_error'        => null,
        ]);
    }

    /**
     * Mark email as AI processing failed
     */
    public function markAiFailed(string $error)
    {
        $this->update([
            'ai_status'       => self::AI_STATUS_FAILED,
            'ai_error'        => $error,
            'ai_processed_at' => Carbon::now(),
        ]);
    }

    /**
     * Mark email as AI processing started
     */
    public function markAiProcessing()
    {
        $this->update([
            'ai_status' => self::AI_STATUS_PROCESSING,
            'ai_error'  => null,
        ]);
    }

    /**
     * Skip AI processing for this email
     */
    public function skipAiProcessing(string $reason = 'Skipped by system')
    {
        $this->update([
            'ai_status'       => self::AI_STATUS_SKIPPED,
            'ai_error'        => $reason,
            'ai_processed_at' => Carbon::now(),
        ]);
    }

    /**
     * Check if email needs AI processing
     */
    public function needsAiProcessing(): bool
    {
        return $this->ai_status === self::AI_STATUS_PENDING;
    }

    /**
     * Get formatted from address
     */
    protected function fromAddress(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->from_name ? "{$this->from_name} <{$this->from_email}>" : $this->from_email
        );
    }

    /**
     * Get formatted received date
     */
    protected function receivedForHumans(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->received_at?->diffForHumans()
        );
    }

    /**
     * Check if email is recent (last 24 hours)
     */
    protected function isRecent(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->received_at && $this->received_at->isAfter(Carbon::now()->subDay())
        );
    }

    /**
     * Get AI status badge color
     */
    public function getAiStatusBadgeColor(): string
    {
        return match ($this->ai_status) {
            self::AI_STATUS_PENDING       => 'yellow',
            self::AI_STATUS_SCREENING     => 'purple',
            self::AI_STATUS_PROCESSING    => 'blue',
            self::AI_STATUS_COMPLETED     => 'green',
            self::AI_STATUS_SCREENED_ONLY => 'teal',
            self::AI_STATUS_FAILED        => 'red',
            self::AI_STATUS_SKIPPED       => 'gray',
            self::AI_STATUS_NOT_ELIGIBLE  => 'gray',
            default                       => 'gray',
        };
    }

    /**
     * Get summary of AI actions taken
     */
    public function getAiActionsSummary(): string
    {
        if (empty($this->ai_actions)) {
            return 'No actions taken';
        }

        return collect($this->ai_actions)
            ->pluck('action')
            ->implode(', ');
    }
}
