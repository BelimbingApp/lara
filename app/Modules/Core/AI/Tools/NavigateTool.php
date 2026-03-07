<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\AI\Tools;

use App\Modules\Core\AI\Contracts\DigitalWorkerTool;

/**
 * Browser navigation tool for Digital Workers.
 *
 * Allows a DW to navigate the user's browser to BLB pages.
 * Returns a `<lara-action>` block that the client-side executor handles.
 *
 * Gated by `ai.tool_navigate.execute` authz capability.
 */
class NavigateTool implements DigitalWorkerTool
{
    public function name(): string
    {
        return 'navigate';
    }

    public function description(): string
    {
        return 'Navigate the user\'s browser to a BLB page. '
            .'Use this when the user asks to go to a page, or after completing a task to show results. '
            .'Provide the relative URL path (e.g., "/admin/users", "/admin/geonames/postcodes", "/dashboard").';
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'url' => [
                    'type' => 'string',
                    'description' => 'Relative URL path to navigate to (must start with "/").',
                ],
            ],
            'required' => ['url'],
        ];
    }

    public function requiredCapability(): ?string
    {
        return 'ai.tool_navigate.execute';
    }

    public function execute(array $arguments): string
    {
        $url = $arguments['url'] ?? '';

        if (! is_string($url) || ! str_starts_with($url, '/')) {
            return 'Error: URL must be a relative path starting with "/".';
        }

        // Sanitize: only allow path characters
        if (preg_match('#^/[a-zA-Z0-9/_\-\.]+$#', $url) !== 1) {
            return 'Error: URL contains invalid characters.';
        }

        return '<lara-action>Livewire.navigate(\''.$url.'\')</lara-action>Navigation initiated to '.$url.'.';
    }
}
