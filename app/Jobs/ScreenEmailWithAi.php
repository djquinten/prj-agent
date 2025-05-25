<?php

namespace App\Jobs;

use App\Models\Email;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScreenEmailWithAi implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public $timeout = 60; // 1 minute for quick screening

    private $emailId;

    private $lmStudioUrl;

    private $aiModel;

    /**
     * Create a new job instance.
     */
    public function __construct(int $emailId)
    {
        $this->emailId     = $emailId;
        $this->lmStudioUrl = config('services.lm_studio.url', 'http://localhost:1234/v1/chat/completions');
        $this->aiModel     = config('services.lm_studio.model', 'local-model');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $email = Email::find($this->emailId);

        if (! $email) {
            Log::error('❌ Email not found for AI screening', ['email_id' => $this->emailId]);

            return;
        }

        if (! $email->needsAiProcessing()) {
            Log::info('⏭️ Email already processed or not eligible for screening', [
                'email_id' => $this->emailId,
                'status'   => $email->ai_status,
            ]);

            return;
        }

        Log::info('🔍 Starting AI screening for email', [
            'email_id' => $this->emailId,
            'subject'  => $email->subject,
            'from'     => $email->from_email,
        ]);

        try {
            // Mark as screening
            $email->update(['ai_status' => Email::AI_STATUS_SCREENING]);

            // Prepare email metadata for AI screening
            $emailMetadata = $this->prepareEmailMetadata($email);

            // Get AI screening analysis
            $aiResponse = $this->callLmStudioForScreening($emailMetadata);

            if (! $aiResponse) {
                $email->markAiFailed('Failed to get screening response from LM Studio');

                return;
            }

            // Parse AI screening response
            $screening = $this->parseScreeningResponse($aiResponse);

            // Store screening results
            $email->update([
                'ai_screening_result'       => $screening,
                'ai_screening_completed_at' => now(),
            ]);

            // Decide next action based on screening
            if ($screening['needs_full_processing'] ?? false) {
                // Queue for full AI processing
                $email->update(['ai_status' => Email::AI_STATUS_PENDING]);
                ProcessEmailWithAi::dispatch($email->id);

                Log::info('🤖 Email queued for full AI processing after screening', [
                    'email_id' => $this->emailId,
                    'reason'   => $screening['full_processing_reason'] ?? 'Needs detailed analysis',
                ]);
            } else {
                // Mark as completed with screening only
                $email->markAiProcessed(
                    $screening['quick_analysis'] ?? 'Screening completed',
                    [], // No actions executed
                    Email::AI_STATUS_SCREENED_ONLY
                );

                Log::info('✅ Email screening completed - no full processing needed', [
                    'email_id' => $this->emailId,
                    'category' => $screening['category'] ?? 'unknown',
                    'priority' => $screening['priority'] ?? 'low',
                ]);
            }

        } catch (\Exception $e) {
            Log::error('❌ AI screening failed', [
                'email_id' => $this->emailId,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);

            $email->markAiFailed($e->getMessage());
        }
    }

    /**
     * Prepare email metadata for AI screening
     */
    private function prepareEmailMetadata(Email $email): array
    {
        return [
            'subject'         => $this->cleanUtf8($email->subject ?? ''),
            'from_name'       => $this->cleanUtf8($email->from_name ?? ''),
            'from_email'      => $this->cleanUtf8($email->from_email ?? ''),
            'preview'         => $this->cleanUtf8($email->body_preview ?? ''),
            'received_at'     => $email->received_at?->format('Y-m-d H:i:s'),
            'has_attachments' => $email->has_attachments ?? false,
        ];
    }

    /**
     * Clean UTF-8 string to prevent encoding errors
     */
    private function cleanUtf8(string $text): string
    {
        // Remove null bytes
        $text = str_replace("\0", '', $text);

        // Convert to UTF-8 and remove invalid sequences
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

        // Remove non-printable characters except common whitespace
        $text = preg_replace('/[^\P{C}\t\n\r]++/u', '', $text);

        // Normalize whitespace
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim($text);
    }

    /**
     * Call LM Studio AI service for screening
     */
    private function callLmStudioForScreening(array $emailMetadata): ?string
    {
        try {
            $systemPrompt = $this->getScreeningSystemPrompt();
            $userPrompt   = $this->getScreeningUserPrompt($emailMetadata);
            $timeout      = config('services.lm_studio.timeout', 60);

            Log::info('🔄 Calling LM Studio API for screening', [
                'url'     => $this->lmStudioUrl,
                'model'   => $this->aiModel,
                'timeout' => $timeout,
            ]);

            $response = Http::timeout($timeout)->post($this->lmStudioUrl, [
                'model'    => $this->aiModel,
                'messages' => [
                    [
                        'role'    => 'system',
                        'content' => $systemPrompt,
                    ],
                    [
                        'role'    => 'user',
                        'content' => $userPrompt,
                    ],
                ],
                'response_format' => [
                    'type'        => 'json_schema',
                    'json_schema' => [
                        'name'   => 'email_screening_response',
                        'strict' => true,
                        'schema' => [
                            'type'       => 'object',
                            'properties' => [
                                'category' => [
                                    'type' => 'string',
                                    'enum' => ['urgent', 'work', 'personal', 'newsletter', 'promotional', 'notification', 'spam', 'support'],
                                ],
                                'priority' => [
                                    'type' => 'string',
                                    'enum' => ['high', 'medium', 'low'],
                                ],
                                'sentiment' => [
                                    'type' => 'string',
                                    'enum' => ['positive', 'neutral', 'negative'],
                                ],
                                'needs_full_processing' => [
                                    'type' => 'boolean',
                                ],
                                'full_processing_reason' => [
                                    'type' => 'string',
                                ],
                                'quick_analysis' => [
                                    'type' => 'string',
                                ],
                                'confidence' => [
                                    'type' => 'string',
                                    'enum' => ['high', 'medium', 'low'],
                                ],
                            ],
                            'required' => ['category', 'priority', 'sentiment', 'needs_full_processing', 'quick_analysis', 'confidence'],
                        ],
                    ],
                ],
                'temperature' => 0.5, // Lower temperature for more consistent screening
                'max_tokens'  => 500, // Less tokens needed for screening
            ]);

            if (! $response->successful()) {
                Log::error('LM Studio screening API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            if (! isset($data['choices'][0]['message']['content'])) {
                Log::error('Invalid LM Studio screening response format', ['data' => $data]);

                return null;
            }

            return $data['choices'][0]['message']['content'];

        } catch (\Exception $e) {
            Log::error('LM Studio screening API call failed', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get system prompt for AI screening
     */
    private function getScreeningSystemPrompt(): string
    {
        return 'You are an intelligent email screening assistant. Your job is to quickly analyze email metadata and determine if an email needs full content analysis.

Analyze emails and categorize them appropriately:
- category: urgent|work|personal|newsletter|promotional|notification|spam|support
- priority: high|medium|low  
- sentiment: positive|neutral|negative
- needs_full_processing: true for urgent work emails, personal messages requiring action, complex support requests; false for newsletters, promotional emails, automated notifications, simple confirmations
- full_processing_reason: Explain why full processing is needed (if applicable)
- quick_analysis: Brief analysis based on metadata
- confidence: high|medium|low for your assessment

Be conservative - when in doubt, suggest full processing for important senders or urgent subjects.';
    }

    /**
     * Get user prompt for screening
     */
    private function getScreeningUserPrompt(array $emailMetadata): string
    {
        $preview = strlen($emailMetadata['preview']) > 200
            ? substr($emailMetadata['preview'], 0, 200) . '...'
            : $emailMetadata['preview'];

        return "Screen this email based on metadata only:

SUBJECT: {$emailMetadata['subject']}
FROM: {$emailMetadata['from_name']} <{$emailMetadata['from_email']}>
RECEIVED: {$emailMetadata['received_at']}
HAS ATTACHMENTS: " . ($emailMetadata['has_attachments'] ? 'Yes' : 'No') . "

PREVIEW:
{$preview}

Analyze this email metadata and determine if it needs full content processing.";
    }

    /**
     * Parse AI screening response
     */
    private function parseScreeningResponse(string $response): array
    {
        try {
            // Try to extract JSON from response
            $jsonStart = strpos($response, '{');
            $jsonEnd   = strrpos($response, '}');

            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
                $parsed     = json_decode($jsonString, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    return $parsed;
                }
            }

            // Fallback if JSON parsing fails
            return [
                'category'               => 'unknown',
                'priority'               => 'medium',
                'sentiment'              => 'neutral',
                'needs_full_processing'  => false, // Conservative fallback
                'full_processing_reason' => '',
                'quick_analysis'         => 'Screening completed (parsing error)',
                'confidence'             => 'low',
            ];

        } catch (\Exception $e) {
            Log::warning('Failed to parse AI screening response as JSON', [
                'error'    => $e->getMessage(),
                'response' => substr($response, 0, 500),
            ]);

            return [
                'category'               => 'unknown',
                'priority'               => 'medium',
                'sentiment'              => 'neutral',
                'needs_full_processing'  => false,
                'full_processing_reason' => '',
                'quick_analysis'         => 'Screening error - ' . $e->getMessage(),
                'confidence'             => 'low',
            ];
        }
    }

    /**
     * The job failed to process.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('❌ ScreenEmailWithAi job failed', [
            'email_id' => $this->emailId,
            'error'    => $exception->getMessage(),
            'trace'    => $exception->getTraceAsString(),
        ]);

        // Mark email as failed if it exists
        $email = Email::find($this->emailId);
        if ($email) {
            $email->markAiFailed($exception->getMessage());
        }
    }
}
