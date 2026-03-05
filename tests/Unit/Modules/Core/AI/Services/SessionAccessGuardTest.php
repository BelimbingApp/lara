<?php

use App\Modules\Core\AI\Services\MessageManager;
use App\Modules\Core\AI\Services\SessionManager;
use App\Modules\Core\Company\Models\Company;
use App\Modules\Core\Employee\Models\Employee;
use App\Modules\Core\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    config()->set('ai.workspace_path', storage_path('framework/testing/ai-workspace-'.Str::random(16)));
});

afterEach(function (): void {
    $workspacePath = config('ai.workspace_path');

    if (is_string($workspacePath)) {
        File::deleteDirectory($workspacePath);
    }
});

/**
 * @return array{user: User, supervised: Employee, unsupervised: Employee}
 */
function createSessionGuardFixture(): array
{
    $company = Company::factory()->create();

    $supervisor = Employee::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
    ]);

    $otherSupervisor = Employee::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
    ]);

    $user = User::factory()->create([
        'company_id' => $company->id,
        'employee_id' => $supervisor->id,
    ]);

    $supervised = Employee::factory()->create([
        'company_id' => $company->id,
        'employee_type' => 'digital_worker',
        'supervisor_id' => $supervisor->id,
        'status' => 'active',
    ]);

    $unsupervised = Employee::factory()->create([
        'company_id' => $company->id,
        'employee_type' => 'digital_worker',
        'supervisor_id' => $otherSupervisor->id,
        'status' => 'active',
    ]);

    return [
        'user' => $user,
        'supervised' => $supervised,
        'unsupervised' => $unsupervised,
    ];
}

it('denies session access when user is not authenticated', function (): void {
    $fixture = createSessionGuardFixture();
    $sessionManager = new SessionManager;

    expect(fn () => $sessionManager->list($fixture['supervised']->id))
        ->toThrow(AuthorizationException::class);
});

it('denies listing sessions for unsupervised digital worker', function (): void {
    $fixture = createSessionGuardFixture();
    $this->actingAs($fixture['user']);

    $sessionManager = new SessionManager;

    expect(fn () => $sessionManager->list($fixture['unsupervised']->id))
        ->toThrow(AuthorizationException::class);
});

it('allows creating and listing sessions for supervised digital worker', function (): void {
    $fixture = createSessionGuardFixture();
    $this->actingAs($fixture['user']);

    $sessionManager = new SessionManager;
    $session = $sessionManager->create($fixture['supervised']->id);
    $sessions = $sessionManager->list($fixture['supervised']->id);

    expect($sessions)->toHaveCount(1)
        ->and($sessions[0]->id)->toBe($session->id);
});

it('denies message append for unsupervised digital worker', function (): void {
    $fixture = createSessionGuardFixture();
    $this->actingAs($fixture['user']);

    $messageManager = new MessageManager(new SessionManager);

    expect(fn () => $messageManager->appendUserMessage($fixture['unsupervised']->id, 'session-1', 'Hello'))
        ->toThrow(AuthorizationException::class);
});

it('allows message append and read for supervised digital worker sessions', function (): void {
    $fixture = createSessionGuardFixture();
    $this->actingAs($fixture['user']);

    $sessionManager = new SessionManager;
    $messageManager = new MessageManager($sessionManager);
    $session = $sessionManager->create($fixture['supervised']->id);

    $messageManager->appendUserMessage($fixture['supervised']->id, $session->id, 'Hello');
    $messageManager->appendAssistantMessage($fixture['supervised']->id, $session->id, 'Hi there');

    $messages = $messageManager->read($fixture['supervised']->id, $session->id);

    expect($messages)->toHaveCount(2)
        ->and($messages[0]->role)->toBe('user')
        ->and($messages[1]->role)->toBe('assistant');
});
