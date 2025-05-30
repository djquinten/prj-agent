<?php

namespace App\Jobs;

use App\Models\Email;
use App\Services\MicrosoftGraphService;
use App\Services\ToolCallService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessEmailWithAi implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public $timeout = 300; // 5 minutes

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
    public function handle(MicrosoftGraphService $graphService, ToolCallService $toolCallService): void
    {
        $email = Email::find($this->emailId);

        if (! $email) {
            Log::error('❌ Email not found for AI processing', ['email_id' => $this->emailId]);

            return;
        }

        if (! $email->needsAiProcessing()) {
            Log::info('⏭️ Email already processed or skipped', [
                'email_id' => $this->emailId,
                'status'   => $email->ai_status,
            ]);

            return;
        }

        Log::info('🤖 Starting AI processing for email', [
            'email_id' => $this->emailId,
            'subject'  => $email->subject,
            'from'     => $email->from_email,
        ]);

        try {
            // Mark as processing
            $email->markAiProcessing();

            // Prepare email content for AI
            $emailContent = $this->prepareEmailContent($email);

            // Get available tools for this email
            $availableTools = $toolCallService->getAvailableTools($email);

            // Get AI analysis with tool calls
            $aiResponse = $this->callLmStudioWithTools($emailContent, $availableTools);

            if (! $aiResponse) {
                $email->markAiFailed('Failed to get response from LM Studio');

                return;
            }

            // Parse AI response and extract actions/tool calls
            $analysis = $this->parseAiResponse($aiResponse);

            // Execute traditional actions
            $executedActions = $this->executeActions($email, $analysis['actions'] ?? [], $graphService);

            // Execute tool calls if any
            $executedToolCalls = $this->executeToolCalls($email, $analysis['tool_calls'] ?? [], $toolCallService);

            // Combine all executed actions
            $allExecutedActions = array_merge($executedActions, $executedToolCalls);

            // Mark as completed
            $email->markAiProcessed(
                $analysis['response'],
                $allExecutedActions,
                Email::AI_STATUS_COMPLETED
            );

            Log::info('✅ AI processing completed', [
                'email_id'         => $this->emailId,
                'actions_executed' => count($executedActions),
                'tools_executed'   => count($executedToolCalls),
                'summary'          => $analysis['summary'] ?? 'No summary',
            ]);

        } catch (\Exception $e) {
            Log::error('❌ AI processing failed', [
                'email_id' => $this->emailId,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);

            $email->markAiFailed($e->getMessage());
        }
    }

    /**
     * Prepare email content for AI analysis
     */
    private function prepareEmailContent(Email $email): array
    {
        // Step 1: Raw body
        $rawBody = $email->body_content ?? '';

        // Step 2: Remove invisible characters
        $cleanBody = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $rawBody);

        // Step 3: Decode HTML entities
        $cleanBody = html_entity_decode($cleanBody, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Step 4: Strip HTML tags
        $cleanBody = strip_tags($cleanBody);

        // Step 5: Remove non-breaking spaces and normalize whitespace
        $cleanBody = str_replace("\u{00A0}", ' ', $cleanBody); // \u00a0 is NBSP
        $cleanBody = preg_replace('/\s+/u', ' ', $cleanBody);

        // Step 6: Optionally remove footer patterns (e.g., unsubscribe, company info)
        $cleanBody = preg_replace('/(Privacybeleid|Afmelden|Webversie).*/i', '', $cleanBody);

        // Step 7: Clean UTF-8 and trim and limit
        $cleanBody = $this->cleanUtf8($cleanBody);
        $cleanBody = strlen($cleanBody) > 1000 ? substr($cleanBody, 0, 1000) . '...' : $cleanBody;

        return [
            'subject' => $this->cleanUtf8($email->subject ?? ''),
            'from'    => $this->cleanUtf8("{$email->from_name} <{$email->from_email}>"),
            'content' => $cleanBody,
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
     * Call LM Studio AI service with tool support
     */
    private function callLmStudioWithTools(array $emailContent, array $availableTools): ?string
    {
        try {
            $systemPrompt = $this->getSystemPromptWithTools($availableTools);
            $userPrompt   = $this->getUserPrompt($emailContent);
            $timeout      = config('services.lm_studio.timeout', 120);

            Log::info('🔄 Calling LM Studio API with tools', [
                'url'             => $this->lmStudioUrl,
                'model'           => $this->aiModel,
                'timeout'         => $timeout,
                'tools_available' => count($availableTools),
                'system_prompt'   => $systemPrompt,
                'user_prompt'     => $userPrompt,
            ]);

            $requestData = [
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
                        'name'   => 'email_analysis_response',
                        'strict' => true,
                        'schema' => $this->getResponseSchema($availableTools),
                    ],
                ],
                'temperature' => 0.7,
                'max_tokens'  => 1500,
            ];

            $response = Http::timeout($timeout)->post($this->lmStudioUrl, $requestData);

            if (! $response->successful()) {
                Log::error('LM Studio API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            if (! isset($data['choices'][0]['message']['content'])) {
                Log::error('Invalid LM Studio response format', ['data' => $data]);

                return null;
            }

            return $data['choices'][0]['message']['content'];

        } catch (\Exception $e) {
            Log::error('LM Studio API call failed', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get system prompt with tool information
     */
    private function getSystemPromptWithTools(array $availableTools): string
    {
        $basePrompt = 'You are an intelligent email assistant. Analyze emails and suggest actions and tool calls.

Analyze the email content and provide:
- summary: Brief summary of the email
- priority: high|medium|low based on urgency and importance
- category: work|personal|newsletter|spam|support
- sentiment: positive|neutral|negative tone of the email
- actions: Array of suggested traditional actions with reasons and execution flags
- tool_calls: Array of tool calls to execute with parameters
- response: Detailed analysis and recommendations

Available traditional actions: mark_important, mark_read, create_task, schedule_reply, flag_spam

IMPORTANT: When creating calendar events, you MUST convert all relative dates to absolute ISO 8601 format:
- "tomorrow at 6 PM" → "2024-01-16T18:00:00Z" (use actual tomorrow\'s date)
- "next Monday at 2 PM" → "2024-01-22T14:00:00Z" (use actual next Monday\'s date)
- "today at 3:30 PM" → "2024-01-15T15:30:00Z" (use actual today\'s date)

Current date/time context: ' . now()->toISOString() . ' (use this as reference for relative dates)

Only suggest actions and tool calls that are appropriate and safe. Never suggest deleting emails or taking destructive actions.';

        if (! empty($availableTools)) {
            $basePrompt .= "\n\nAvailable tools for this email:\n";
            foreach ($availableTools as $tool) {
                $basePrompt .= "- {$tool['name']}: {$tool['description']}\n";
            }
            $basePrompt .= "\nUse tool_calls when you detect relevant content that matches the tool capabilities.";
        }

        return $basePrompt;
    }

    /**
     * Get response schema including tool calls
     */
    private function getResponseSchema(array $availableTools): array
    {
        $schema = [
            'type'       => 'object',
            'properties' => [
                'summary' => [
                    'type' => 'string',
                ],
                'priority' => [
                    'type' => 'string',
                    'enum' => ['high', 'medium', 'low'],
                ],
                'category' => [
                    'type' => 'string',
                    'enum' => ['work', 'personal', 'newsletter', 'spam', 'support'],
                ],
                'sentiment' => [
                    'type' => 'string',
                    'enum' => ['positive', 'neutral', 'negative'],
                ],
                'actions' => [
                    'type'  => 'array',
                    'items' => [
                        'type'       => 'object',
                        'properties' => [
                            'action' => [
                                'type' => 'string',
                                'enum' => ['mark_important', 'mark_read', 'create_task', 'schedule_reply', 'flag_spam'],
                            ],
                            'reason' => [
                                'type' => 'string',
                            ],
                            'execute' => [
                                'type' => 'boolean',
                            ],
                        ],
                        'required' => ['action', 'reason', 'execute'],
                    ],
                ],
                'response' => [
                    'type' => 'string',
                ],
            ],
            'required' => ['summary', 'priority', 'category', 'sentiment', 'actions', 'response'],
        ];

        // Add tool_calls to schema if tools are available
        if (! empty($availableTools)) {
            $schema['properties']['tool_calls'] = [
                'type'  => 'array',
                'items' => [
                    'type'       => 'object',
                    'properties' => [
                        'tool_name' => [
                            'type' => 'string',
                            'enum' => array_column($availableTools, 'name'),
                        ],
                        'parameters' => [
                            'type' => 'object',
                        ],
                        'reason' => [
                            'type' => 'string',
                        ],
                        'execute' => [
                            'type' => 'boolean',
                        ],
                    ],
                    'required' => ['tool_name', 'parameters', 'reason', 'execute'],
                ],
            ];
            $schema['required'][] = 'tool_calls';
        }

        return $schema;
    }

    /**
     * Get user prompt with email content
     */
    private function getUserPrompt(array $emailContent): string
    {
        return "Please analyze this email and suggest appropriate actions:

SUBJECT: {$emailContent['subject']}
FROM: {$emailContent['from']}

CONTENT:
{$emailContent['content']}

Analyze this email and provide your assessment and recommendations.";
    }

    /**
     * Parse AI response
     */
    private function parseAiResponse(string $response): array
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
                'summary'   => 'AI analysis completed',
                'priority'  => 'medium',
                'category'  => 'general',
                'sentiment' => 'neutral',
                'actions'   => [],
                'response'  => $response,
            ];

        } catch (\Exception $e) {
            Log::warning('Failed to parse AI response as JSON', [
                'error'    => $e->getMessage(),
                'response' => substr($response, 0, 500),
            ]);

            return [
                'summary'   => 'Analysis completed (parsing error)',
                'priority'  => 'medium',
                'category'  => 'general',
                'sentiment' => 'neutral',
                'actions'   => [],
                'response'  => $response,
            ];
        }
    }

    /**
     * Execute traditional actions
     */
    private function executeActions(Email $email, array $actions, MicrosoftGraphService $graphService): array
    {
        $executedActions = [];

        foreach ($actions as $action) {
            if (! isset($action['execute']) || ! $action['execute']) {
                continue;
            }

            $actionType = $action['action'] ?? '';
            $reason     = $action['reason'] ?? 'No reason provided';

            try {
                switch ($actionType) {
                    case 'mark_read':
                        if (! $email->is_read) {
                            $result = $graphService->markAsRead($email->graph_id);
                            if ($result['success']) {
                                $email->update(['is_read' => true]);
                                $executedActions[] = [
                                    'action' => 'mark_read',
                                    'status' => 'success',
                                    'reason' => $reason,
                                ];
                            }
                        }
                        break;

                    case 'mark_important':
                        // Could implement with Graph API categories/flags
                        $executedActions[] = [
                            'action' => 'mark_important',
                            'status' => 'logged',
                            'reason' => $reason,
                        ];
                        break;

                    case 'create_task':
                        // Log the task suggestion
                        Log::info('📋 Task suggested by AI', [
                            'email_id' => $email->id,
                            'subject'  => $email->subject,
                            'reason'   => $reason,
                        ]);

                        $executedActions[] = [
                            'action' => 'create_task',
                            'status' => 'logged',
                            'reason' => $reason,
                        ];
                        break;

                    case 'schedule_reply':
                        // Log the reply suggestion
                        Log::info('📝 Reply suggested by AI', [
                            'email_id' => $email->id,
                            'subject'  => $email->subject,
                            'reason'   => $reason,
                        ]);

                        $executedActions[] = [
                            'action' => 'schedule_reply',
                            'status' => 'logged',
                            'reason' => $reason,
                        ];
                        break;

                    case 'flag_spam':
                        // Log potential spam
                        Log::warning('🚩 Potential spam detected by AI', [
                            'email_id' => $email->id,
                            'subject'  => $email->subject,
                            'from'     => $email->from_email,
                            'reason'   => $reason,
                        ]);

                        $executedActions[] = [
                            'action' => 'flag_spam',
                            'status' => 'logged',
                            'reason' => $reason,
                        ];
                        break;

                    default:
                        Log::info('❓ Unknown action suggested by AI', [
                            'action' => $actionType,
                            'reason' => $reason,
                        ]);
                }

            } catch (\Exception $e) {
                Log::error('❌ Failed to execute AI action', [
                    'action' => $actionType,
                    'error'  => $e->getMessage(),
                ]);

                $executedActions[] = [
                    'action' => $actionType,
                    'status' => 'failed',
                    'reason' => $reason,
                    'error'  => $e->getMessage(),
                ];
            }
        }

        return $executedActions;
    }

    /**
     * Execute tool calls
     */
    private function executeToolCalls(Email $email, array $toolCalls, ToolCallService $toolCallService): array
    {
        $executedToolCalls = [];

        foreach ($toolCalls as $toolCall) {
            if (! isset($toolCall['execute']) || ! $toolCall['execute']) {
                continue;
            }

            $toolName   = $toolCall['tool_name'] ?? '';
            $parameters = $toolCall['parameters'] ?? [];
            $reason     = $toolCall['reason'] ?? 'No reason provided';

            try {
                $result = $toolCallService->executeTool($email, $toolName, $parameters);

                $executedToolCalls[] = [
                    'type'       => 'tool_call',
                    'tool_name'  => $toolName,
                    'parameters' => $parameters,
                    'reason'     => $reason,
                    'status'     => $result['success'] ? 'success' : 'failed',
                    'result'     => $result,
                ];

                if ($result['success']) {
                    Log::info('🔧 Tool call executed successfully', [
                        'email_id'  => $email->id,
                        'tool_name' => $toolName,
                        'result'    => $result,
                    ]);
                } else {
                    Log::warning('🔧 Tool call failed', [
                        'email_id'  => $email->id,
                        'tool_name' => $toolName,
                        'error'     => $result['error'] ?? 'Unknown error',
                    ]);
                }

            } catch (\Exception $e) {
                Log::error('❌ Tool call execution failed', [
                    'email_id'  => $email->id,
                    'tool_name' => $toolName,
                    'error'     => $e->getMessage(),
                ]);

                $executedToolCalls[] = [
                    'type'       => 'tool_call',
                    'tool_name'  => $toolName,
                    'parameters' => $parameters,
                    'reason'     => $reason,
                    'status'     => 'failed',
                    'error'      => $e->getMessage(),
                ];
            }
        }

        return $executedToolCalls;
    }

    /**
     * The job failed to process.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('❌ ProcessEmailWithAi job failed', [
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
