<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\AI\Tools;

use App\Base\AI\Services\UrlSafetyGuard;
use App\Base\AI\Services\WebFetchService;
use App\Modules\Core\AI\Contracts\DigitalWorkerTool;

/**
 * Web page fetching and content extraction tool for Digital Workers.
 *
 * Allows a DW to fetch external web pages and extract readable content
 * for research, data gathering, and contextual understanding.
 *
 * Safety: SSRF protection blocks requests to private/internal networks
 * by default. Response size is capped to prevent memory exhaustion.
 * Redirect count is limited to prevent redirect loops.
 *
 * Gated by `ai.tool_web_fetch.execute` authz capability.
 */
class WebFetchTool implements DigitalWorkerTool
{
    private const DEFAULT_TIMEOUT_SECONDS = 30;

    private const DEFAULT_MAX_RESPONSE_BYTES = 5242880; // 5MB

    private const DEFAULT_MAX_CHARS = 50000;

    private readonly WebFetchService $webFetchService;

    public function __construct(?WebFetchService $webFetchService = null)
    {
        $this->webFetchService = $webFetchService ?? new WebFetchService(new UrlSafetyGuard);
    }

    public function name(): string
    {
        return 'web_fetch';
    }

    public function description(): string
    {
        return 'Fetch a web page and extract its readable content. '
            .'Use this to read documentation, articles, product pages, or any public URL. '
            .'Returns extracted text content from the page.';
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'url' => [
                    'type' => 'string',
                    'description' => 'The URL to fetch (must be http or https).',
                ],
                'max_chars' => [
                    'type' => 'integer',
                    'description' => 'Maximum characters of content to return (default 50000). '
                        .'Reduce for concise summaries, increase for full-page content.',
                ],
                'extract_mode' => [
                    'type' => 'string',
                    'enum' => ['text', 'markdown'],
                    'description' => 'Content extraction mode: "text" for plain text (default), '
                        .'"markdown" to preserve headings, links, and formatting.',
                ],
            ],
            'required' => ['url'],
        ];
    }

    public function requiredCapability(): ?string
    {
        return 'ai.tool_web_fetch.execute';
    }

    public function execute(array $arguments): string
    {
        $url = $arguments['url'] ?? '';

        if (! is_string($url) || trim($url) === '') {
            return 'Error: No URL provided.';
        }

        $url = trim($url);

        $maxChars = self::DEFAULT_MAX_CHARS;
        if (isset($arguments['max_chars']) && is_int($arguments['max_chars'])) {
            $maxChars = max(1, $arguments['max_chars']);
        }

        $extractMode = 'text';
        if (isset($arguments['extract_mode']) && in_array($arguments['extract_mode'], ['text', 'markdown'], true)) {
            $extractMode = $arguments['extract_mode'];
        }

        $timeout = (int) config('ai.tools.web_fetch.timeout_seconds', self::DEFAULT_TIMEOUT_SECONDS);
        $maxBytes = (int) config('ai.tools.web_fetch.max_response_bytes', self::DEFAULT_MAX_RESPONSE_BYTES);

        $result = $this->webFetchService->fetch(
            url: $url,
            timeoutSeconds: $timeout,
            maxResponseBytes: $maxBytes,
            maxChars: $maxChars,
            extractMode: $extractMode,
            allowPrivateNetwork: (bool) config('ai.tools.web_fetch.ssrf_allow_private', false),
        );

        if (isset($result['validation_error'])) {
            return 'Error: '.$result['validation_error'];
        }

        if (isset($result['request_error'])) {
            return 'Error: Failed to fetch URL: '.$result['request_error'];
        }

        if (isset($result['http_status'])) {
            return 'Failed to fetch URL: HTTP '.$result['http_status'];
        }

        $content = $result['content'] ?? '';
        $truncated = (bool) ($result['truncated'] ?? false);

        if ($truncated === true) {
            $content .= "\n\n[Content truncated at {$maxChars} characters]";
        }

        $charCount = (int) ($result['char_count'] ?? mb_strlen($content));

        return "# Content from {$url}\n\n{$content}\n\n---\nFetched {$charCount} characters from {$url}";
    }
}
