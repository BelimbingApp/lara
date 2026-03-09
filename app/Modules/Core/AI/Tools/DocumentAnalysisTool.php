<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\AI\Tools;

use App\Modules\Core\AI\Contracts\DigitalWorkerTool;

/**
 * Document analysis tool for Digital Workers.
 *
 * Reads and returns the text content of a file within the BLB project,
 * allowing the LLM to analyse, summarise, or extract information from it.
 *
 * Only text-based file types are supported. File size is capped to prevent
 * context-window exhaustion. Paths must resolve within the project root.
 *
 * Gated by `ai.tool_document_analysis.execute` authz capability.
 */
class DocumentAnalysisTool implements DigitalWorkerTool
{
    private const ERROR_PREFIX = 'Error: ';

    private const MAX_BYTES = 102_400; // 100 KB

    private const ALLOWED_EXTENSIONS = [
        'txt', 'md', 'csv', 'log', 'json',
        'yaml', 'yml', 'xml', 'html', 'htm',
        'php', 'js', 'ts', 'css', 'env',
    ];

    public function name(): string
    {
        return 'document_analysis';
    }

    public function description(): string
    {
        return 'Read and return the contents of a text file within the BLB project. '
            .'Use this to analyse configuration files, source code, CSVs, logs, or any text document. '
            .'Provide a relative path from the project root (e.g., "storage/app/report.csv").';
    }

    /**
     * @return array<string, mixed>
     */
    public function parametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'path' => [
                    'type' => 'string',
                    'description' => 'Relative file path from the BLB project root. '
                        .'Examples: "storage/app/export.csv", "config/app.php", "README.md".',
                ],
            ],
            'required' => ['path'],
        ];
    }

    public function requiredCapability(): ?string
    {
        return 'ai.tool_document_analysis.execute';
    }

    /**
     * Execute the tool with the given arguments.
     *
     * @param  array<string, mixed>  $arguments
     */
    public function execute(array $arguments): string
    {
        $path = $arguments['path'] ?? '';

        if (! is_string($path) || trim($path) === '') {
            return self::ERROR_PREFIX.'No file path provided.';
        }

        return $this->analyzeDocument(trim($path));
    }

    /**
     * Resolve, validate, and read the document at the given relative path.
     */
    private function analyzeDocument(string $path): string
    {
        $resolved = realpath(base_path($path));
        $error = $this->checkAccess($resolved);

        if ($error !== null) {
            return $error;
        }

        return $this->readContent((string) $resolved, $path);
    }

    /**
     * Verify the resolved path is safe and of a supported file type.
     */
    private function checkAccess(string|false $resolved): ?string
    {
        if ($resolved === false || ! str_starts_with($resolved, base_path())) {
            return self::ERROR_PREFIX.'File not found or path is outside the project root.';
        }

        $ext = strtolower(pathinfo($resolved, PATHINFO_EXTENSION));

        if (! in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            return sprintf(self::ERROR_PREFIX.'File type .%s is not supported for document analysis.', $ext);
        }

        return null;
    }

    /**
     * Read the file and return its content with a header line.
     */
    private function readContent(string $resolved, string $originalPath): string
    {
        $content = file_get_contents($resolved, false, null, 0, self::MAX_BYTES);

        if ($content === false) {
            return self::ERROR_PREFIX.'Could not read the file.';
        }

        $size = filesize($resolved);
        $truncated = ($size !== false && $size > self::MAX_BYTES)
            ? sprintf(' (truncated to %d bytes)', self::MAX_BYTES)
            : '';

        return sprintf("File: %s%s\n\n%s", $originalPath, $truncated, $content);
    }
}
