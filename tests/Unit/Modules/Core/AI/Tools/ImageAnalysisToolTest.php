<?php

use App\Modules\Core\AI\Tools\ImageAnalysisTool;
use Tests\TestCase;
use Tests\Support\AssertsToolBehavior;

uses(TestCase::class, AssertsToolBehavior::class);

beforeEach(function () {
    $this->tool = new ImageAnalysisTool;
});

dataset('supported image formats', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

describe('tool metadata', function () {
    it('has the expected metadata', function () {
        $this->assertToolMetadata(
            $this->tool,
            'image_analysis',
            'ai.tool_image_analysis.execute',
            ['path', 'prompt'],
            ['path', 'prompt'],
        );
    });
});

describe('input validation', function () {
    it('rejects missing path', function () {
        $this->assertToolError(['prompt' => 'Describe this image']);
    });

    it('rejects empty path', function () {
        $this->assertToolError(['path' => '', 'prompt' => 'Describe this image']);
    });

    it('rejects non-string path', function () {
        $result = $this->tool->execute(['path' => 42, 'prompt' => 'Describe this image']);
        expect($result)->toContain('Error');
    });

    it('rejects missing prompt', function () {
        $this->assertToolError(['path' => '/images/photo.jpg']);
    });

    it('rejects empty prompt', function () {
        $this->assertToolError(['path' => '/images/photo.jpg', 'prompt' => '']);
    });

    it('rejects prompt exceeding max length', function () {
        $result = $this->tool->execute([
            'path' => '/images/photo.jpg',
            'prompt' => str_repeat('x', 5001),
        ]);
        expect($result)->toContain('Error')
            ->and($result)->toContain('exceed');
    });

    it('rejects unsupported image extension', function () {
        $result = $this->tool->execute([
            'path' => '/images/photo.bmp',
            'prompt' => 'Describe this',
        ]);
        expect($result)->toContain('Error')
            ->and($result)->toContain('Unsupported');
    });

    it('rejects unsupported extension with uppercase', function () {
        $result = $this->tool->execute([
            'path' => '/images/photo.BMP',
            'prompt' => 'Describe this',
        ]);
        expect($result)->toContain('Error');
    });

    it('rejects file with no extension', function () {
        $result = $this->tool->execute([
            'path' => '/images/photo',
            'prompt' => 'Describe this',
        ]);
        expect($result)->toContain('Error');
    });
});

describe('supported formats', function () {
    it('accepts supported image formats', function (string $extension) {
        $data = $this->decodeToolExecution([
            'path' => '/images/photo.'.$extension,
            'prompt' => 'Describe',
        ]);

        expect($data['status'])->toBe('analyzed');
    })->with('supported image formats');
});

describe('URL paths', function () {
    it('accepts http URL without extension check', function () {
        $result = $this->tool->execute([
            'path' => 'http://example.com/image',
            'prompt' => 'Describe this',
        ]);
        $data = $this->decodeToolResult($result);

        expect($data)->not->toBeNull()
            ->and($data['status'])->toBe('analyzed');
    });

    it('accepts https URL without extension check', function () {
        $result = $this->tool->execute([
            'path' => 'https://example.com/images/photo.png',
            'prompt' => 'Describe this',
        ]);
        $data = $this->decodeToolResult($result);

        expect($data['status'])->toBe('analyzed');
    });

    it('accepts https URL with query params', function () {
        $result = $this->tool->execute([
            'path' => 'https://example.com/image?w=500&h=300',
            'prompt' => 'Describe this',
        ]);
        $data = $this->decodeToolResult($result);

        expect($data['status'])->toBe('analyzed');
    });
});

describe('stub execution', function () {
    it('returns valid JSON with required fields', function () {
        $result = $this->tool->execute([
            'path' => '/images/photo.jpg',
            'prompt' => 'What is in this image?',
        ]);
        $data = $this->decodeToolResult($result);

        expect($data)->not->toBeNull()
            ->and($data)->toHaveKeys(['action', 'path', 'prompt', 'status', 'message'])
            ->and($data['action'])->toBe('image_analysis')
            ->and($data['status'])->toBe('analyzed');
    });

    it('includes path and prompt in response', function () {
        $result = $this->tool->execute([
            'path' => '/storage/images/chart.png',
            'prompt' => 'Extract data from chart',
        ]);
        $data = $this->decodeToolResult($result);

        expect($data['path'])->toBe('/storage/images/chart.png')
            ->and($data['prompt'])->toBe('Extract data from chart');
    });

    it('returns stub message', function () {
        $result = $this->tool->execute([
            'path' => '/images/photo.jpg',
            'prompt' => 'Describe',
        ]);
        $data = $this->decodeToolResult($result);

        expect($data['message'])->toContain('stub');
    });
});
