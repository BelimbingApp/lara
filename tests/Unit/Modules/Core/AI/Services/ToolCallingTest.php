<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

use App\Base\Authz\Contracts\AuthorizationService;
use App\Base\Authz\DTO\AuthorizationDecision;
use App\Base\Authz\Enums\AuthorizationReasonCode;
use App\Modules\Core\AI\Contracts\DigitalWorkerTool;
use App\Modules\Core\AI\Services\DigitalWorkerToolRegistry;
use App\Modules\Core\AI\Tools\ArtisanTool;
use App\Modules\Core\AI\Tools\BashTool;
use App\Modules\Core\AI\Tools\NavigateTool;
use Illuminate\Foundation\Testing\TestCase;

uses(TestCase::class);

function makeAllowAllAuthzService(): AuthorizationService
{
    $mock = Mockery::mock(AuthorizationService::class);
    $mock->shouldReceive('can')->andReturn(AuthorizationDecision::allow());

    return $mock;
}

function makeDenyAllAuthzService(): AuthorizationService
{
    $mock = Mockery::mock(AuthorizationService::class);
    $mock->shouldReceive('can')->andReturn(
        AuthorizationDecision::deny(AuthorizationReasonCode::DENIED_MISSING_CAPABILITY)
    );

    return $mock;
}

function makeSimpleTool(string $name, ?string $capability = null): DigitalWorkerTool
{
    return new class($name, $capability) implements DigitalWorkerTool
    {
        public function __construct(
            private readonly string $toolName,
            private readonly ?string $toolCapability,
        ) {}

        public function name(): string
        {
            return $this->toolName;
        }

        public function description(): string
        {
            return 'Test tool: '.$this->toolName;
        }

        public function parametersSchema(): array
        {
            return ['type' => 'object', 'properties' => ['input' => ['type' => 'string']]];
        }

        public function requiredCapability(): ?string
        {
            return $this->toolCapability;
        }

        public function execute(array $arguments): string
        {
            return 'executed:'.$this->toolName.':'.(string) ($arguments['input'] ?? 'no-input');
        }
    };
}

describe('DigitalWorkerToolRegistry', function () {
    it('returns empty tool definitions when no tools registered', function () {
        $registry = new DigitalWorkerToolRegistry(makeAllowAllAuthzService());

        expect($registry->toolDefinitionsForCurrentUser())->toBe([]);
    });

    it('returns registered tools in OpenAI format', function () {
        $registry = new DigitalWorkerToolRegistry(makeAllowAllAuthzService());
        $registry->register(makeSimpleTool('echo'));

        $definitions = $registry->toolDefinitionsForCurrentUser();

        expect($definitions)->toHaveCount(1);
        expect($definitions[0]['type'])->toBe('function');
        expect($definitions[0]['function']['name'])->toBe('echo');
        expect($definitions[0]['function']['description'])->toBe('Test tool: echo');
    });

    it('executes a registered tool and returns result', function () {
        $registry = new DigitalWorkerToolRegistry(makeAllowAllAuthzService());
        $registry->register(makeSimpleTool('echo'));

        $result = $registry->execute('echo', ['input' => 'hello']);

        expect($result)->toBe('executed:echo:hello');
    });

    it('returns error for unknown tool', function () {
        $registry = new DigitalWorkerToolRegistry(makeAllowAllAuthzService());

        $result = $registry->execute('nonexistent', []);

        expect($result)->toContain('Error: Unknown tool');
    });

    it('filters tools by user authz capabilities', function () {
        $registry = new DigitalWorkerToolRegistry(makeDenyAllAuthzService());
        $registry->register(makeSimpleTool('restricted', 'ai.tool_artisan.execute'));
        $registry->register(makeSimpleTool('public', null));

        $definitions = $registry->toolDefinitionsForCurrentUser();

        // Only the tool with no capability requirement should be available
        expect($definitions)->toHaveCount(1);
        expect($definitions[0]['function']['name'])->toBe('public');
    });

    it('denies execution for tools the user lacks capability for', function () {
        $registry = new DigitalWorkerToolRegistry(makeDenyAllAuthzService());
        $registry->register(makeSimpleTool('restricted', 'ai.tool_artisan.execute'));

        $result = $registry->execute('restricted', ['input' => 'test']);

        expect($result)->toContain('do not have permission');
    });

    it('catches exceptions during tool execution', function () {
        $failingTool = new class implements DigitalWorkerTool
        {
            public function name(): string
            {
                return 'fails';
            }

            public function description(): string
            {
                return 'Always fails';
            }

            public function parametersSchema(): array
            {
                return ['type' => 'object', 'properties' => []];
            }

            public function requiredCapability(): ?string
            {
                return null;
            }

            public function execute(array $arguments): string
            {
                throw new RuntimeException('Boom!');
            }
        };

        $registry = new DigitalWorkerToolRegistry(makeAllowAllAuthzService());
        $registry->register($failingTool);

        $result = $registry->execute('fails', []);

        expect($result)->toContain('Error executing fails');
        expect($result)->toContain('Boom!');
    });
});

describe('ArtisanTool', function () {
    it('has correct name and required capability', function () {
        $tool = new ArtisanTool;

        expect($tool->name())->toBe('artisan');
        expect($tool->requiredCapability())->toBe('ai.tool_artisan.execute');
    });

    it('returns error for empty command', function () {
        $tool = new ArtisanTool;

        expect($tool->execute([]))->toContain('No command provided');
        expect($tool->execute(['command' => '']))->toContain('No command provided');
    });

    it('strips php artisan prefix if LLM included it', function () {
        $tool = new ArtisanTool;

        // This will run 'php artisan list' — should work without error
        $result = $tool->execute(['command' => 'php artisan list --raw']);

        expect($result)->not->toContain('Error');
    });
});

describe('NavigateTool', function () {
    it('has correct name and required capability', function () {
        $tool = new NavigateTool;

        expect($tool->name())->toBe('navigate');
        expect($tool->requiredCapability())->toBe('ai.tool_navigate.execute');
    });

    it('returns lara-action block for valid URL', function () {
        $tool = new NavigateTool;

        $result = $tool->execute(['url' => '/admin/users']);

        expect($result)->toContain('<lara-action>');
        expect($result)->toContain("Livewire.navigate('/admin/users')");
        expect($result)->toContain('Navigation initiated');
    });

    it('rejects URLs not starting with slash', function () {
        $tool = new NavigateTool;

        expect($tool->execute(['url' => 'admin/users']))->toContain('Error');
        expect($tool->execute(['url' => 'https://evil.com']))->toContain('Error');
    });

    it('rejects URLs with invalid characters', function () {
        $tool = new NavigateTool;

        expect($tool->execute(['url' => '/admin/<script>']))->toContain('Error');
        expect($tool->execute(['url' => "/admin/users' OR 1=1"]))->toContain('Error');
    });
});

describe('BashTool', function () {
    it('has correct name and required capability', function () {
        $tool = new BashTool;

        expect($tool->name())->toBe('bash');
        expect($tool->requiredCapability())->toBe('ai.tool_bash.execute');
    });

    it('returns error for empty command', function () {
        $tool = new BashTool;

        expect($tool->execute([]))->toContain('No command provided');
        expect($tool->execute(['command' => '']))->toContain('No command provided');
    });

    it('executes a simple command successfully', function () {
        $tool = new BashTool;

        $result = $tool->execute(['command' => 'echo hello-bash-tool']);

        expect($result)->toBe('hello-bash-tool');
    });

    it('returns failure message for bad command', function () {
        $tool = new BashTool;

        $result = $tool->execute(['command' => 'cat /nonexistent/file/path']);

        expect($result)->toContain('Command failed');
    });

    it('returns success message when command produces no output', function () {
        $tool = new BashTool;

        $result = $tool->execute(['command' => 'true']);

        expect($result)->toBe('Command completed successfully (no output).');
    });
});
