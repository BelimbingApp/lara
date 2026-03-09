<?php

use App\Modules\Core\AI\Services\Browser\BrowserPoolManager;
use App\Modules\Core\AI\Services\Browser\BrowserSsrfGuard;
use App\Modules\Core\AI\Tools\BrowserTool;
use Tests\TestCase;
use Tests\Support\AssertsToolBehavior;

uses(TestCase::class, AssertsToolBehavior::class);

beforeEach(function () {
    $this->poolManager = Mockery::mock(BrowserPoolManager::class);
    $this->ssrfGuard = Mockery::mock(BrowserSsrfGuard::class);
    $this->tool = new BrowserTool($this->poolManager, $this->ssrfGuard);

    $this->poolManager->shouldReceive('isAvailable')->andReturn(true)->byDefault();
    $this->ssrfGuard->shouldReceive('validate')->andReturn(true)->byDefault();
});

describe('tool metadata', function () {
    it('has the expected metadata', function () {
        $this->assertToolMetadata(
            $this->tool,
            'browser',
            'ai.tool_browser.execute',
            ['action'],
            ['action'],
        );
    });
});

describe('input validation', function () {
    it('rejects missing action', function () {
        $this->assertToolError([]);
    });

    it('rejects invalid action', function () {
        $this->assertToolError(['action' => 'bogus'], 'Must be one of');
    });

    it('returns error when pool unavailable', function () {
        $this->poolManager->shouldReceive('isAvailable')->andReturn(false);

        $result = $this->tool->execute(['action' => 'navigate', 'url' => 'https://example.com']);

        expect($result)->toContain('not available');
    });
});

describe('navigate action', function () {
    it('rejects missing url', function () {
        $this->assertToolError(['action' => 'navigate'], 'url');
    });

    it('rejects SSRF blocked url', function () {
        $this->ssrfGuard->shouldReceive('validate')
            ->with('https://evil.internal')
            ->andReturn('Blocked: private');

        $result = $this->tool->execute(['action' => 'navigate', 'url' => 'https://evil.internal']);

        expect($result)->toContain('Error')
            ->and($result)->toContain('Blocked');
    });

    it('navigates successfully', function () {
        $data = $this->decodeToolExecution(['action' => 'navigate', 'url' => 'https://example.com']);
        expect($data['status'])->toBe('navigated');
    });
});

describe('snapshot action', function () {
    it('returns snapshot with default format', function () {
        $data = $this->decodeToolExecution(['action' => 'snapshot']);

        expect($data['format'])->toBe('ai')
            ->and($data['status'])->toBe('captured');
    });

    it('accepts aria format', function () {
        $data = $this->decodeToolExecution(['action' => 'snapshot', 'format' => 'aria']);
        expect($data['format'])->toBe('aria');
    });
});

describe('screenshot action', function () {
    it('returns screenshot stub', function () {
        $data = $this->decodeToolExecution(['action' => 'screenshot']);
        expect($data['status'])->toBe('captured');
    });

    it('accepts full_page flag', function () {
        $data = $this->decodeToolExecution(['action' => 'screenshot', 'full_page' => true]);
        expect($data['full_page'])->toBeTrue();
    });
});

describe('act action', function () {
    it('rejects missing kind', function () {
        $this->assertToolError(['action' => 'act'], 'kind');
    });

    it('rejects invalid kind', function () {
        $result = $this->tool->execute(['action' => 'act', 'kind' => 'bogus']);
        expect($result)->toContain('Error');
    });

    it('rejects missing ref', function () {
        $this->assertToolError(['action' => 'act', 'kind' => 'click'], 'ref');
    });

    it('performs action successfully', function () {
        $data = $this->decodeToolExecution(['action' => 'act', 'kind' => 'click', 'ref' => 'e1']);
        expect($data['status'])->toBe('performed');
    });
});

describe('tabs action', function () {
    it('lists tabs', function () {
        $data = $this->decodeToolExecution(['action' => 'tabs']);
        expect($data['status'])->toBe('listed');
    });
});

describe('open action', function () {
    it('rejects missing url', function () {
        $this->assertToolError(['action' => 'open']);
    });

    it('opens tab successfully', function () {
        $data = $this->decodeToolExecution(['action' => 'open', 'url' => 'https://example.com']);
        expect($data['status'])->toBe('opened');
    });
});

describe('close action', function () {
    it('rejects missing tab_id', function () {
        $this->assertToolError(['action' => 'close']);
    });

    it('closes tab', function () {
        $data = $this->decodeToolExecution(['action' => 'close', 'tab_id' => 'tab1']);
        expect($data['status'])->toBe('closed');
    });
});

describe('evaluate action', function () {
    it('rejects when evaluate disabled', function () {
        config()->set('ai.tools.browser.evaluate_enabled', false);

        $result = $this->tool->execute(['action' => 'evaluate', 'script' => 'alert(1)']);

        expect($result)->toContain('Error')
            ->and($result)->toContain('disabled');
    });

    it('rejects missing script when enabled', function () {
        config()->set('ai.tools.browser.evaluate_enabled', true);

        $result = $this->tool->execute(['action' => 'evaluate']);

        expect($result)->toContain('Error')
            ->and($result)->toContain('script');
    });

    it('evaluates when enabled', function () {
        config()->set('ai.tools.browser.evaluate_enabled', true);

        $data = $this->decodeToolExecution(['action' => 'evaluate', 'script' => 'document.title']);
        expect($data['status'])->toBe('evaluated');
    });
});

describe('pdf action', function () {
    it('exports pdf', function () {
        $data = $this->decodeToolExecution(['action' => 'pdf']);
        expect($data['status'])->toBe('exported');
    });
});

describe('cookies action', function () {
    it('rejects missing cookie_action', function () {
        $result = $this->tool->execute(['action' => 'cookies']);
        expect($result)->toContain('Error')
            ->and($result)->toContain('cookie_action');
    });

    it('rejects invalid cookie_action', function () {
        $result = $this->tool->execute(['action' => 'cookies', 'cookie_action' => 'bogus']);
        expect($result)->toContain('Error');
    });

    it('gets cookies', function () {
        $data = $this->decodeToolExecution(['action' => 'cookies', 'cookie_action' => 'get']);
        expect($data['status'])->toBe('retrieved');
    });

    it('rejects set without name', function () {
        $result = $this->tool->execute(['action' => 'cookies', 'cookie_action' => 'set']);
        expect($result)->toContain('Error');
    });

    it('sets cookie', function () {
        $data = $this->decodeToolExecution([
            'action' => 'cookies',
            'cookie_action' => 'set',
            'cookie_name' => 'test',
            'cookie_value' => 'val',
        ]);
        expect($data['status'])->toBe('set');
    });

    it('clears cookies', function () {
        $data = $this->decodeToolExecution(['action' => 'cookies', 'cookie_action' => 'clear']);
        expect($data['status'])->toBe('cleared');
    });
});

describe('wait action', function () {
    it('rejects when no condition specified', function () {
        $result = $this->tool->execute(['action' => 'wait']);
        expect($result)->toContain('Error')
            ->and($result)->toContain('At least one');
    });

    it('accepts text condition', function () {
        $data = $this->decodeToolExecution(['action' => 'wait', 'text' => 'Hello']);
        expect($data['status'])->toBe('waited');
    });

    it('accepts selector condition', function () {
        $data = $this->decodeToolExecution(['action' => 'wait', 'selector' => '#main']);
        expect($data['status'])->toBe('waited');
    });

    it('accepts url condition', function () {
        $data = $this->decodeToolExecution(['action' => 'wait', 'url' => 'https://example.com/done']);
        expect($data['status'])->toBe('waited');
    });

    it('uses default timeout', function () {
        $data = $this->decodeToolExecution(['action' => 'wait', 'text' => 'Hello']);
        expect($data['timeout_ms'])->toBe(5000);
    });

    it('accepts custom timeout', function () {
        $data = $this->decodeToolExecution(['action' => 'wait', 'text' => 'Hi', 'timeout_ms' => 10000]);
        expect($data['timeout_ms'])->toBe(10000);
    });
});
