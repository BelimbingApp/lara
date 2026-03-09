<?php

use App\Modules\Core\AI\Tools\MemoryGetTool;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->tool = new MemoryGetTool;
});

describe('tool metadata', function () {
    it('returns correct name', function () {
        expect($this->tool->name())->toBe('memory_get');
    });

    it('returns a description', function () {
        expect($this->tool->description())->not->toBeEmpty();
    });

    it('requires memory_get capability', function () {
        expect($this->tool->requiredCapability())->toBe('ai.tool_memory_get.execute');
    });

    it('has valid parameter schema', function () {
        $schema = $this->tool->parametersSchema();

        expect($schema['type'])->toBe('object')
            ->and($schema['properties'])->toHaveKey('path')
            ->and($schema['required'])->toBe(['path']);
    });
});

describe('input validation', function () {
    it('rejects empty path', function () {
        $result = $this->tool->execute(['path' => '']);
        expect($result)->toContain('Error');
    });

    it('rejects missing path', function () {
        $result = $this->tool->execute([]);
        expect($result)->toContain('Error');
    });

    it('rejects non-string path', function () {
        $result = $this->tool->execute(['path' => 123]);
        expect($result)->toContain('Error');
    });

    it('rejects absolute paths', function () {
        $result = $this->tool->execute(['path' => '/etc/passwd']);
        expect($result)->toContain('absolute');
    });

    it('rejects directory traversal', function () {
        $result = $this->tool->execute(['path' => '../../../etc/passwd']);
        expect($result)->toContain('traversal');
    });

    it('rejects null bytes', function () {
        $result = $this->tool->execute(['path' => "file\0.md"]);
        expect($result)->toContain('null bytes');
    });
});

describe('file reading', function () {
    it('reads a file from docs scope', function () {
        $result = $this->tool->execute(['path' => 'brief.md']);

        expect($result)->not->toContain('Error')
            ->and($result)->toContain('brief.md');
    });

    it('returns error for nonexistent file', function () {
        $result = $this->tool->execute(['path' => 'nonexistent-file-xyz.md']);
        expect($result)->toContain('not found');
    });

    it('respects from parameter', function () {
        $result = $this->tool->execute(['path' => 'brief.md', 'from' => 3]);

        expect($result)->not->toContain('# Project Brief: Belimbing')
            ->and($result)->toContain('lines 3-');
    });

    it('ignores from parameter when less than 1', function () {
        $result = $this->tool->execute(['path' => 'brief.md', 'from' => 0]);

        expect($result)->not->toContain('Error')
            ->and($result)->toContain('docs:brief.md');
    });

    it('respects lines parameter', function () {
        $result = $this->tool->execute(['path' => 'brief.md', 'lines' => 5]);
        expect($result)->toContain('5 lines');
    });

    it('ignores lines parameter when less than 1', function () {
        $result = $this->tool->execute(['path' => 'brief.md', 'lines' => 0]);

        expect($result)->not->toContain('Error')
            ->and($result)->toContain('docs:brief.md');
    });

    it('caps lines at MAX_LINES (500)', function () {
        $result = $this->tool->execute(['path' => 'brief.md', 'lines' => 9999]);

        expect($result)->not->toContain('Error')
            ->and($result)->toContain('docs:brief.md');
    });

    it('returns error when from exceeds file length', function () {
        $result = $this->tool->execute(['path' => 'brief.md', 'from' => 999999]);
        expect($result)->toContain('exceeds file length');
    });

    it('includes footer with scope info', function () {
        $result = $this->tool->execute(['path' => 'brief.md']);
        expect($result)->toContain('docs:brief.md');
    });

    it('includes line range in footer when from > 1', function () {
        $result = $this->tool->execute(['path' => 'brief.md', 'from' => 2, 'lines' => 3]);
        expect($result)->toContain('lines 2-');
    });
});

describe('scope selection', function () {
    it('defaults to docs scope', function () {
        $result = $this->tool->execute(['path' => 'brief.md']);
        expect($result)->toContain('docs:brief.md');
    });

    it('accepts docs scope explicitly', function () {
        $result = $this->tool->execute(['path' => 'brief.md', 'scope' => 'docs']);
        expect($result)->toContain('docs:brief.md');
    });

    it('falls back to docs for invalid scope', function () {
        $result = $this->tool->execute(['path' => 'brief.md', 'scope' => 'invalid']);
        expect($result)->toContain('docs:brief.md');
    });

    it('returns error for nonexistent workspace file', function () {
        $result = $this->tool->execute(['path' => 'MEMORY.md', 'scope' => 'workspace']);
        expect($result)->toContain('Error');
    });
});
