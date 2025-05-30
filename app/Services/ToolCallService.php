<?php

namespace App\Services;

use App\Contracts\ToolCallInterface;
use App\Models\Email;
use App\Tools\GoogleCalendarTool;
use Illuminate\Support\Facades\Log;

class ToolCallService
{
    private array $tools = [];

    public function __construct()
    {
        $this->registerTools();
    }

    /**
     * Register all available tools
     */
    private function registerTools(): void
    {
        $this->tools = [
            app(GoogleCalendarTool::class),
        ];
    }

    /**
     * Get all available tools for an email
     */
    public function getAvailableTools(Email $email): array
    {
        Log::debug('🔧 Checking tool availability', [
            'email_id'               => $email->id ?? 'unknown',
            'subject'                => $email->subject ?? 'unknown',
            'total_tools_registered' => count($this->tools),
        ]);

        $availableTools = collect($this->tools)
            ->filter(function (ToolCallInterface $tool) use ($email) {
                $isAvailable = $tool->isAvailable($email);
                Log::debug('🔧 Tool availability check', [
                    'tool_name'    => $tool->getName(),
                    'email_id'     => $email->id ?? 'unknown',
                    'is_available' => $isAvailable,
                ]);

                return $isAvailable;
            })
            ->map(fn (ToolCallInterface $tool) => [
                'name'        => $tool->getName(),
                'description' => $tool->getDescription(),
                'parameters'  => $tool->getParametersSchema(),
            ])
            ->values()
            ->toArray();

        Log::info('🔧 Tool availability summary', [
            'email_id'               => $email->id ?? 'unknown',
            'subject'                => $email->subject ?? 'unknown',
            'total_tools_registered' => count($this->tools),
            'available_tools_count'  => count($availableTools),
            'available_tools'        => array_column($availableTools, 'name'),
        ]);

        return $availableTools;
    }

    /**
     * Execute a tool call
     */
    public function executeTool(Email $email, string $toolName, array $parameters): array
    {
        $tool = collect($this->tools)
            ->first(fn (ToolCallInterface $tool) => $tool->getName() === $toolName);

        if (! $tool) {
            Log::warning('🔧 Unknown tool requested', [
                'tool_name' => $toolName,
                'email_id'  => $email->id,
            ]);

            return [
                'success' => false,
                'error'   => "Tool '{$toolName}' not found",
            ];
        }

        if (! $tool->isAvailable($email)) {
            Log::warning('🔧 Tool not available for this email', [
                'tool_name' => $toolName,
                'email_id'  => $email->id,
            ]);

            return [
                'success' => false,
                'error'   => "Tool '{$toolName}' is not available for this email",
            ];
        }

        try {
            Log::info('🔧 Executing tool', [
                'tool_name'  => $toolName,
                'email_id'   => $email->id,
                'parameters' => $parameters,
            ]);

            $result = $tool->execute($email, $parameters);

            Log::info('✅ Tool executed successfully', [
                'tool_name' => $toolName,
                'email_id'  => $email->id,
                'result'    => $result,
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('❌ Tool execution failed', [
                'tool_name' => $toolName,
                'email_id'  => $email->id,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Get tool schema for AI prompt
     */
    public function getToolsSchema(Email $email): array
    {
        return $this->getAvailableTools($email);
    }
}
