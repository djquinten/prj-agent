<?php

namespace App\Contracts;

use App\Models\Email;

interface ToolCallInterface
{
    /**
     * Get the tool name
     */
    public function getName(): string;

    /**
     * Get the tool description
     */
    public function getDescription(): string;

    /**
     * Get the tool parameters schema
     */
    public function getParametersSchema(): array;

    /**
     * Execute the tool with given parameters
     */
    public function execute(Email $email, array $parameters): array;

    /**
     * Check if this tool should be available for the given email
     */
    public function isAvailable(Email $email): bool;
}
