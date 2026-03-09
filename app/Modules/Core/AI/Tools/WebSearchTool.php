<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\AI\Tools;

use App\Base\AI\Services\WebSearchService;
use App\Modules\Core\AI\Contracts\DigitalWorkerTool;
use Illuminate\Support\Facades\Cache;

/**
 * Web search tool for Digital Workers.
 *
 * Allows a DW to search the web for real-time information via configurable
 * search providers (Parallel, Brave Search). Results are cached to reduce
 * API calls for repeated queries.
 *
 * Gated by `ai.tool_web_search.execute` authz capability.
 */
class WebSearchTool implements DigitalWorkerTool
{
    private const TIMEOUT_SECONDS = 15;

    private const DEFAULT_COUNT = 5;

    private const MAX_COUNT = 10;

    private const DEFAULT_CACHE_TTL_MINUTES = 15;

    private const VALID_FRESHNESS = ['day', 'week', 'month'];

    private string $provider;

    private string $apiKey;

    private int $cacheTtlMinutes;

    private readonly WebSearchService $webSearchService;

    /**
     * @param  string  $provider  Search provider name ('parallel' or 'brave')
     * @param  string  $apiKey  API key for the configured provider
     * @param  int  $cacheTtlMinutes  Cache TTL in minutes for search results
     */
    public function __construct(
        string $provider,
        string $apiKey,
        int $cacheTtlMinutes = self::DEFAULT_CACHE_TTL_MINUTES,
        ?WebSearchService $webSearchService = null,
    ) {
        $this->provider = $provider;
        $this->apiKey = $apiKey;
        $this->cacheTtlMinutes = $cacheTtlMinutes;
        $this->webSearchService = $webSearchService ?? new WebSearchService;
    }

    /**
     * Create an instance if the active provider has an API key configured.
     *
     * Returns null when no API key is available, allowing the registry
     * to skip registration of this tool.
     */
    public static function createIfConfigured(?WebSearchService $webSearchService = null): ?self
    {
        $provider = config('ai.tools.web_search.provider', 'parallel');
        $apiKey = config('ai.tools.web_search.'.$provider.'.api_key');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            return null;
        }

        $cacheTtl = (int) config('ai.tools.web_search.cache_ttl_minutes', self::DEFAULT_CACHE_TTL_MINUTES);

        return new self($provider, $apiKey, $cacheTtl, $webSearchService);
    }

    public function name(): string
    {
        return 'web_search';
    }

    public function description(): string
    {
        return 'Search the web for current information. '
            .'Use this when the user asks about recent events, needs up-to-date data, '
            .'or when your training data may be outdated. '
            .'Returns a list of relevant web pages with titles, URLs, and snippets.';
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'The search query or objective text.',
                ],
                'count' => [
                    'type' => 'integer',
                    'description' => 'Number of results to return (1–'.self::MAX_COUNT.', default '.self::DEFAULT_COUNT.').',
                ],
                'freshness' => [
                    'type' => 'string',
                    'enum' => self::VALID_FRESHNESS,
                    'description' => 'Recency filter: "day", "week", or "month".',
                ],
            ],
            'required' => ['query'],
        ];
    }

    public function requiredCapability(): ?string
    {
        return 'ai.tool_web_search.execute';
    }

    public function execute(array $arguments): string
    {
        $query = $arguments['query'] ?? '';

        if (! is_string($query) || trim($query) === '') {
            return 'Error: No search query provided.';
        }

        $query = trim($query);

        $count = self::DEFAULT_COUNT;
        if (isset($arguments['count']) && is_int($arguments['count'])) {
            $count = max(1, min($arguments['count'], self::MAX_COUNT));
        }

        $freshness = null;
        if (isset($arguments['freshness']) && is_string($arguments['freshness']) && in_array($arguments['freshness'], self::VALID_FRESHNESS, true)) {
            $freshness = $arguments['freshness'];
        }

        $cacheKey = 'lara_tool:web_search:'.md5($query.$count.$freshness);

        return Cache::remember($cacheKey, $this->cacheTtlMinutes * 60, function () use ($query, $count, $freshness): string {
            return $this->performSearch($query, $count, $freshness);
        });
    }

    /**
     * Dispatch to the appropriate provider and return formatted results.
     */
    private function performSearch(string $query, int $count, ?string $freshness): string
    {
        $result = $this->webSearchService->search(
            provider: $this->provider,
            apiKey: $this->apiKey,
            query: $query,
            count: $count,
            freshness: $freshness,
            timeoutSeconds: self::TIMEOUT_SECONDS,
        );

        if (isset($result['error'])) {
            return 'Search failed: '.$result['error'];
        }

        $results = $result['results'] ?? [];

        if ($results === []) {
            return 'No results found for: '.$query;
        }

        return $this->formatResults($results);
    }

    /**
     * Format search results as a numbered list.
     *
     * @param  list<array{title: string, url: string, snippet: string}>  $results
     */
    private function formatResults(array $results): string
    {
        $lines = [];

        foreach ($results as $index => $result) {
            $number = $index + 1;
            $title = $result['title'] ?? 'Untitled';
            $url = $result['url'] ?? '';
            $snippet = $result['snippet'] ?? '';

            $lines[] = $number.'. '.$title;
            $lines[] = '   '.$url;
            $lines[] = '   '.$snippet;

            if ($index < count($results) - 1) {
                $lines[] = '';
            }
        }

        return implode("\n", $lines);
    }
}
