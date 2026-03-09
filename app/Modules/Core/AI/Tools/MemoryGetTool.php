<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\AI\Tools;

use App\Modules\Core\AI\Contracts\DigitalWorkerTool;
use App\Modules\Core\Employee\Models\Employee;

/**
 * Workspace and documentation file reading tool for Digital Workers.
 *
 * Allows a DW to read files from two scopes:
 * - `docs`: The BLB project documentation directory (base_path('docs/'))
 * - `workspace`: Lara's workspace directory (config('ai.workspace_path')/LARA_ID/)
 *
 * Safety: Path traversal is blocked, absolute paths are rejected, binary
 * files are detected, and output is capped at 500 lines.
 *
 * Gated by `ai.tool_memory_get.execute` authz capability.
 */
class MemoryGetTool implements DigitalWorkerTool
{
    private const ERROR_PREFIX = 'Error: ';

    private const MAX_LINES = 500;

    private const BINARY_CHECK_BYTES = 1024;

    public function name(): string
    {
        return 'memory_get';
    }

    public function description(): string
    {
        return 'Read a file from the project documentation or Lara\'s workspace. '
            .'Use scope "docs" to read architecture specs, blueprints, and guides (e.g., "architecture/database.md"). '
            .'Use scope "workspace" to read Lara\'s workspace files (e.g., "MEMORY.md", "notes/meeting-2026-03-06.md"). '
            .'Supports optional line range selection for large files.';
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'path' => [
                    'type' => 'string',
                    'description' => 'Relative file path within the chosen scope '
                        .'(e.g., "MEMORY.md", "architecture/database.md").',
                ],
                'scope' => [
                    'type' => 'string',
                    'enum' => ['docs', 'workspace'],
                    'description' => 'Where to read from: "docs" for project documentation (default), '
                        .'"workspace" for Lara\'s workspace files.',
                ],
                'from' => [
                    'type' => 'integer',
                    'description' => 'Start reading from this line number (1-indexed, default: 1).',
                ],
                'lines' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of lines to return (default: all, capped at 500).',
                ],
            ],
            'required' => ['path'],
        ];
    }

    public function requiredCapability(): ?string
    {
        return 'ai.tool_memory_get.execute';
    }

    /**
     * Execute the tool: validate the path, then delegate to file reading.
     *
     * Overrides the contract method to add path validation before reading.
     */
    public function execute(array $arguments): string
    {
        $path = $arguments['path'] ?? '';

        if (! is_string($path) || trim($path) === '') {
            return self::ERROR_PREFIX.'No path provided.';
        }

        $path = trim($path);
        $pathError = $this->validatePath($path);

        return $pathError ?? $this->readFile($path, $arguments);
    }

    /**
     * Resolve the filesystem path, guard against binary content, then read lines.
     *
     * @param  string  $path  Validated relative path
     * @param  array<string, mixed>  $arguments  Raw tool arguments
     */
    private function readFile(string $path, array $arguments): string
    {
        $scope = $this->resolveScope($arguments);
        $from = $this->resolveFrom($arguments);
        $maxLines = $this->resolveMaxLines($arguments);

        [$error, $realFull] = $this->resolveFilePath($path, $scope);

        if ($error !== null) {
            return $error;
        }

        return $this->isBinary($realFull)
            ? self::ERROR_PREFIX.'Cannot read binary file: '.$path
            : $this->readLines($realFull, $path, $scope, $from, $maxLines);
    }

    /**
     * Resolve and validate the absolute filesystem path for the given scope.
     *
     * Returns a two-element array: [error|null, resolvedPath|''].
     * When error is non-null, resolvedPath is an empty string.
     *
     * @param  string  $path  Validated relative path
     * @param  string  $scope  Resolved scope ('docs' or 'workspace')
     * @return array{0: string|null, 1: string}
     */
    private function resolveFilePath(string $path, string $scope): array
    {
        $basePath = $this->resolveBasePath($scope);
        $realBase = realpath($basePath);

        if ($realBase === false) {
            return [self::ERROR_PREFIX.'Scope directory does not exist.', ''];
        }

        $realFull = realpath($basePath.'/'.ltrim($path, '/'));

        if ($realFull === false || ! is_file($realFull)) {
            return [self::ERROR_PREFIX.'File not found: '.$path, ''];
        }

        if (! str_starts_with($realFull, $realBase.'/')) {
            return [self::ERROR_PREFIX.'Invalid path: directory traversal is not allowed.', ''];
        }

        return [null, $realFull];
    }

    /**
     * Read the requested line range from an already-validated file path.
     *
     * @param  string  $realFull  Absolute, validated filesystem path
     * @param  string  $path  Original relative path (for error messages and footer)
     * @param  string  $scope  Scope label ('docs' or 'workspace')
     * @param  int  $from  1-indexed start line
     * @param  int  $maxLines  Maximum lines to return
     */
    private function readLines(string $realFull, string $path, string $scope, int $from, int $maxLines): string
    {
        $allLines = file($realFull, FILE_IGNORE_NEW_LINES);

        if ($allLines === false) {
            return self::ERROR_PREFIX.'Unable to read file: '.$path;
        }

        $totalLines = count($allLines);

        if ($from > $totalLines) {
            return self::ERROR_PREFIX.'Start line '.$from.' exceeds file length ('.$totalLines.' lines).';
        }

        return $this->formatOutput($path, $scope, $from, $maxLines, $allLines, $totalLines);
    }

    /**
     * Build the formatted output string with header, content, and footer.
     *
     * @param  string  $path  Relative path (used in footer and heading)
     * @param  string  $scope  Scope label ('docs' or 'workspace')
     * @param  int  $from  1-indexed start line
     * @param  int  $maxLines  Maximum lines to include
     * @param  list<string>  $allLines  All file lines
     * @param  int  $totalLines  Total line count in the file
     */
    private function formatOutput(string $path, string $scope, int $from, int $maxLines, array $allLines, int $totalLines): string
    {
        $selectedLines = array_slice($allLines, $from - 1, $maxLines);
        $returnedCount = count($selectedLines);
        $content = implode("\n", $selectedLines);

        $footer = $returnedCount.' lines';
        if ($from > 1 || $returnedCount < $totalLines) {
            $endLine = $from + $returnedCount - 1;
            $footer .= ' (lines '.$from.'-'.$endLine.' of '.$totalLines.')';
        }
        $footer .= ' from '.$scope.':'.$path;

        return '# '.basename($path)."\n\n".$content."\n\n---\n".$footer;
    }

    /**
     * Validate the relative path for safety.
     *
     * Rejects absolute paths, directory traversal sequences, and null bytes.
     *
     * @param  string  $path  The relative path to validate
     * @return string|null Error message if invalid, null if valid
     */
    private function validatePath(string $path): ?string
    {
        if (str_starts_with($path, '/')) {
            return self::ERROR_PREFIX.'Invalid path: absolute paths are not allowed.';
        }

        if (str_contains($path, '..')) {
            return self::ERROR_PREFIX.'Invalid path: directory traversal is not allowed.';
        }

        if (str_contains($path, "\0")) {
            return self::ERROR_PREFIX.'Invalid path: null bytes are not allowed.';
        }

        return null;
    }

    /**
     * Resolve the base directory path for the given scope.
     *
     * @param  string  $scope  Either 'docs' or 'workspace'
     * @return string Absolute path to the scope's base directory
     */
    private function resolveBasePath(string $scope): string
    {
        if ($scope === 'workspace') {
            return config('ai.workspace_path').'/'.Employee::LARA_ID;
        }

        return base_path('docs');
    }

    /**
     * Resolve the scope parameter, defaulting to 'docs'.
     *
     * @param  array<string, mixed>  $arguments  Raw tool arguments
     */
    private function resolveScope(array $arguments): string
    {
        if (isset($arguments['scope']) && in_array($arguments['scope'], ['docs', 'workspace'], true)) {
            return $arguments['scope'];
        }

        return 'docs';
    }

    /**
     * Resolve the from parameter, defaulting to 1.
     *
     * @param  array<string, mixed>  $arguments  Raw tool arguments
     */
    private function resolveFrom(array $arguments): int
    {
        if (isset($arguments['from']) && is_int($arguments['from']) && $arguments['from'] >= 1) {
            return $arguments['from'];
        }

        return 1;
    }

    /**
     * Resolve the lines parameter, capped at MAX_LINES.
     *
     * @param  array<string, mixed>  $arguments  Raw tool arguments
     */
    private function resolveMaxLines(array $arguments): int
    {
        if (isset($arguments['lines']) && is_int($arguments['lines']) && $arguments['lines'] >= 1) {
            return min($arguments['lines'], self::MAX_LINES);
        }

        return self::MAX_LINES;
    }

    /**
     * Check whether the file appears to be binary by scanning for null bytes.
     *
     * @param  string  $filePath  Absolute path to the file
     */
    private function isBinary(string $filePath): bool
    {
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            return false;
        }

        $chunk = fread($handle, self::BINARY_CHECK_BYTES);
        fclose($handle);

        if ($chunk === false || $chunk === '') {
            return false;
        }

        return str_contains($chunk, "\0");
    }
}
