<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\AI\Contracts;

/**
 * Contract for Digital Worker tool implementations.
 *
 * Each tool is a discrete capability that a DW can invoke during an agentic
 * conversation turn. Tools are registered in the DigitalWorkerToolRegistry
 * and gated by authz capabilities before execution.
 */
interface DigitalWorkerTool
{
    /**
     * Unique tool name (used as the function name in OpenAI tool calling).
     */
    public function name(): string;

    /**
     * Human-readable description for the LLM to understand when to use this tool.
     */
    public function description(): string;

    /**
     * JSON Schema for tool parameters (OpenAI function parameters format).
     *
     * @return array<string, mixed>
     */
    public function parametersSchema(): array;

    /**
     * Authz capability required to use this tool (e.g., 'ai.tool_artisan.execute').
     *
     * Returns null if the tool requires no special capability (auth-only).
     */
    public function requiredCapability(): ?string;

    /**
     * Execute the tool with the given arguments.
     *
     * @param  array<string, mixed>  $arguments  Parsed arguments from LLM
     * @return string Tool result (will be sent back to LLM as tool message content)
     */
    public function execute(array $arguments): string;
}
