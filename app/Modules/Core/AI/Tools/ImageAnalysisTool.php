<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\AI\Tools;

use App\Modules\Core\AI\Contracts\DigitalWorkerTool;

/**
 * Image analysis tool for Digital Workers.
 *
 * Returns metadata and format information about an image file within the
 * BLB project, allowing the LLM to report on image properties or reason
 * about image content based on file attributes such as format and dimensions.
 *
 * Paths must resolve within the project root.
 *
 * Gated by `ai.tool_image_analysis.execute` authz capability.
 */
class ImageAnalysisTool implements DigitalWorkerTool
{
    private const ERROR_PREFIX = 'Error: ';

    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];

    public function name(): string
    {
        return 'image_analysis';
    }

    public function description(): string
    {
        return 'Retrieve metadata for an image file within the BLB project. '
            .'Returns the image format, dimensions, and file size. '
            .'Provide a relative path from the project root (e.g., "public/images/logo.png").';
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
                        .'Examples: "public/images/banner.jpg", "storage/app/uploads/photo.png".',
                ],
            ],
            'required' => ['path'],
        ];
    }

    public function requiredCapability(): ?string
    {
        return 'ai.tool_image_analysis.execute';
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

        return $this->analyzeImage(trim($path));
    }

    /**
     * Resolve, validate, and inspect the image file.
     */
    private function analyzeImage(string $path): string
    {
        $resolved = realpath(base_path($path));
        $error = $this->checkAccess($resolved);

        if ($error !== null) {
            return $error;
        }

        return $this->buildMetadata((string) $resolved, $path);
    }

    /**
     * Verify the resolved path is safe and of a supported image type.
     */
    private function checkAccess(string|false $resolved): ?string
    {
        if ($resolved === false || ! str_starts_with($resolved, base_path())) {
            return self::ERROR_PREFIX.'Image file not found or path is outside the project root.';
        }

        $ext = strtolower(pathinfo($resolved, PATHINFO_EXTENSION));

        if (! in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            return sprintf(self::ERROR_PREFIX.'File type .%s is not supported for image analysis.', $ext);
        }

        return null;
    }

    /**
     * Build a human-readable metadata summary for the image.
     */
    private function buildMetadata(string $resolved, string $originalPath): string
    {
        $size = filesize($resolved);
        $sizeLabel = $size !== false ? number_format((int) $size).' bytes' : 'unknown size';

        $ext = strtolower(pathinfo($resolved, PATHINFO_EXTENSION));
        $dimensions = $this->readDimensions($resolved, $ext);

        return sprintf(
            "Image: %s\nFormat: %s\nSize: %s%s",
            $originalPath,
            strtoupper($ext),
            $sizeLabel,
            $dimensions,
        );
    }

    /**
     * Attempt to read raster image dimensions; returns empty string for SVG or unreadable files.
     */
    private function readDimensions(string $resolved, string $ext): string
    {
        if ($ext === 'svg') {
            return '';
        }

        $info = @getimagesize($resolved);

        return $info !== false ? sprintf("\nDimensions: %dx%d px", $info[0], $info[1]) : '';
    }
}
