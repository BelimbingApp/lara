<?php

use App\Base\Authz\DTO\Actor;
use App\Base\Authz\Enums\PrincipalType;
use App\Base\Workflow\DTO\GuardResult;
use App\Base\Workflow\Models\StatusConfig;
use App\Base\Workflow\Models\StatusHistory;
use App\Base\Workflow\Models\StatusTransition;
use App\Base\Workflow\Models\Workflow;
use App\Base\Workflow\Services\StatusManager;
use App\Base\Workflow\Services\TransitionManager;
use App\Base\Workflow\Services\TransitionValidator;
use App\Modules\Core\Company\Models\Company;
use App\Modules\Core\User\Models\User;
use Illuminate\Database\Eloquent\Model;

const WF_TEST_FLOW = 'test_ticket';

function seedTestWorkflow(): void
{
    Workflow::query()->create([
        'code' => WF_TEST_FLOW,
        'label' => 'Test Ticket',
        'module' => 'test',
    ]);

    $statuses = [
        ['flow' => WF_TEST_FLOW, 'code' => 'open', 'label' => 'Open', 'position' => 0],
        ['flow' => WF_TEST_FLOW, 'code' => 'in_progress', 'label' => 'In Progress', 'position' => 1],
        ['flow' => WF_TEST_FLOW, 'code' => 'resolved', 'label' => 'Resolved', 'position' => 2],
        ['flow' => WF_TEST_FLOW, 'code' => 'closed', 'label' => 'Closed', 'position' => 3],
    ];

    foreach ($statuses as $status) {
        StatusConfig::query()->create($status);
    }

    $transitions = [
        ['flow' => WF_TEST_FLOW, 'from_code' => 'open', 'to_code' => 'in_progress', 'label' => 'Start Work'],
        ['flow' => WF_TEST_FLOW, 'from_code' => 'in_progress', 'to_code' => 'resolved', 'label' => 'Resolve'],
        ['flow' => WF_TEST_FLOW, 'from_code' => 'resolved', 'to_code' => 'closed', 'label' => 'Close'],
        ['flow' => WF_TEST_FLOW, 'from_code' => 'resolved', 'to_code' => 'open', 'label' => 'Reopen', 'position' => 1],
    ];

    foreach ($transitions as $transition) {
        StatusTransition::query()->create($transition);
    }
}

function createTestActor(): Actor
{
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);

    return new Actor(
        type: PrincipalType::HUMAN_USER,
        id: $user->id,
        companyId: $company->id,
        attributes: ['role' => 'technician', 'department' => 'IT'],
    );
}

/**
 * Create a minimal Eloquent model stand-in for testing transitions.
 * Uses the base_workflow table as a convenient existing table with the right columns.
 */
function createTicketModel(): Model
{
    // Use a simple anonymous model backed by an actual DB table
    $model = new class extends Model
    {
        protected $table = 'base_workflow';

        protected $fillable = ['code', 'label', 'module', 'description', 'model_class', 'settings', 'is_active'];

        // Add a 'status' column via attribute — we'll store it in settings for testing
        public function getAttribute($key)
        {
            if ($key === 'status') {
                return $this->attributes['status'] ?? null;
            }

            return parent::getAttribute($key);
        }

        public function setAttribute($key, $value)
        {
            if ($key === 'status') {
                $this->attributes['status'] = $value;

                return $this;
            }

            return parent::setAttribute($key, $value);
        }
    };

    // We need a real table for testing. Use a dedicated test row.
    // Actually, let's just use the workflow table itself since it exists.
    // We'll create a temporary test table instead.
    return $model;
}

beforeEach(function (): void {
    seedTestWorkflow();
});

// -- StatusManager Tests --

test('status manager loads all active statuses for a flow', function (): void {
    $manager = app(StatusManager::class);
    $statuses = $manager->getStatuses(WF_TEST_FLOW);

    expect($statuses)->toHaveCount(4);
    expect($statuses->pluck('code')->all())->toBe(['open', 'in_progress', 'resolved', 'closed']);
});

test('status manager returns a specific status by code', function (): void {
    $manager = app(StatusManager::class);
    $status = $manager->getStatus(WF_TEST_FLOW, 'in_progress');

    expect($status)->not->toBeNull();
    expect($status->label)->toBe('In Progress');
});

test('status manager returns null for non-existent status', function (): void {
    $manager = app(StatusManager::class);
    expect($manager->getStatus(WF_TEST_FLOW, 'nonexistent'))->toBeNull();
});

// -- StatusConfig Model Tests --

test('status config computes next statuses from transitions table', function (): void {
    $status = StatusConfig::query()->where('flow', WF_TEST_FLOW)->where('code', 'resolved')->first();

    $nextStatuses = $status->nextStatuses;

    expect($nextStatuses->all())->toBe(['closed', 'open']);
});

test('terminal status has no next statuses', function (): void {
    $status = StatusConfig::query()->where('flow', WF_TEST_FLOW)->where('code', 'closed')->first();

    expect($status->nextStatuses)->toBeEmpty();
});

// -- TransitionManager Tests --

test('transition manager loads available transitions from a status', function (): void {
    $manager = app(TransitionManager::class);
    $transitions = $manager->getAvailableTransitions(WF_TEST_FLOW, 'resolved');

    expect($transitions)->toHaveCount(2);
    expect($transitions->pluck('to_code')->all())->toBe(['closed', 'open']);
});

test('transition manager finds a specific transition', function (): void {
    $manager = app(TransitionManager::class);
    $transition = $manager->getTransition(WF_TEST_FLOW, 'open', 'in_progress');

    expect($transition)->not->toBeNull();
    expect($transition->label)->toBe('Start Work');
});

test('transition manager returns null for non-existent transition', function (): void {
    $manager = app(TransitionManager::class);
    expect($manager->getTransition(WF_TEST_FLOW, 'open', 'closed'))->toBeNull();
});

// -- TransitionValidator Tests --

test('validator allows transition without capability or guard', function (): void {
    $validator = app(TransitionValidator::class);
    $actor = createTestActor();
    $transition = StatusTransition::query()
        ->where('flow', WF_TEST_FLOW)->where('from_code', 'open')->where('to_code', 'in_progress')->first();

    $result = $validator->validate($transition, $actor);

    expect($result->allowed)->toBeTrue();
});

test('validator denies inactive transition', function (): void {
    $transition = StatusTransition::query()
        ->where('flow', WF_TEST_FLOW)->where('from_code', 'open')->where('to_code', 'in_progress')->first();
    $transition->update(['is_active' => false]);

    $validator = app(TransitionValidator::class);
    $actor = createTestActor();
    $result = $validator->validate($transition->fresh(), $actor);

    expect($result->allowed)->toBeFalse();
    expect($result->reason)->toContain('inactive');
});

test('validator denies transition when actor lacks required capability', function (): void {
    setupAuthzRoles();

    $transition = StatusTransition::query()
        ->where('flow', WF_TEST_FLOW)->where('from_code', 'open')->where('to_code', 'in_progress')->first();
    $transition->update(['capability' => 'workflow.test_ticket.start_work']);

    $validator = app(TransitionValidator::class);
    $actor = createTestActor();
    $result = $validator->validate($transition->fresh(), $actor);

    expect($result->allowed)->toBeFalse();
    expect($result->reason)->toContain('capability');
});

test('validator allows transition when actor has required capability', function (): void {
    $user = createAdminUser(); // core_admin has grant_all

    $transition = StatusTransition::query()
        ->where('flow', WF_TEST_FLOW)->where('from_code', 'open')->where('to_code', 'in_progress')->first();
    // Use a capability registered in Config/authz.php so KnownCapabilityPolicy passes
    $transition->update(['capability' => 'workflow.process.manage']);

    $actor = Actor::forUser($user);
    $validator = app(TransitionValidator::class);
    $result = $validator->validate($transition->fresh(), $actor);

    expect($result->allowed)->toBeTrue();
});

// -- StatusHistory Model Tests --

test('history timeline returns entries in chronological order', function (): void {
    $now = now();

    StatusHistory::query()->create([
        'flow' => WF_TEST_FLOW, 'flow_id' => 1, 'status' => 'open',
        'actor_id' => 1, 'transitioned_at' => $now->copy()->subMinutes(30),
    ]);
    StatusHistory::query()->create([
        'flow' => WF_TEST_FLOW, 'flow_id' => 1, 'status' => 'in_progress',
        'actor_id' => 1, 'tat' => 1800, 'transitioned_at' => $now,
    ]);

    $timeline = StatusHistory::timeline(WF_TEST_FLOW, 1);

    expect($timeline)->toHaveCount(2);
    expect($timeline->first()->status)->toBe('open');
    expect($timeline->last()->status)->toBe('in_progress');
    expect($timeline->last()->tat)->toBe(1800);
});

test('history latest returns the most recent entry', function (): void {
    $now = now();

    StatusHistory::query()->create([
        'flow' => WF_TEST_FLOW, 'flow_id' => 42, 'status' => 'open',
        'actor_id' => 1, 'transitioned_at' => $now->copy()->subHour(),
    ]);
    StatusHistory::query()->create([
        'flow' => WF_TEST_FLOW, 'flow_id' => 42, 'status' => 'in_progress',
        'actor_id' => 1, 'transitioned_at' => $now,
    ]);

    $latest = StatusHistory::latest(WF_TEST_FLOW, 42);

    expect($latest)->not->toBeNull();
    expect($latest->status)->toBe('in_progress');
});

// -- GuardResult & TransitionResult DTO Tests --

test('guard result allow creates an allowed result', function (): void {
    $result = GuardResult::allow();

    expect($result->allowed)->toBeTrue();
    expect($result->reason)->toBeNull();
});

test('guard result deny creates a denied result with reason', function (): void {
    $result = GuardResult::deny('Insufficient leave balance');

    expect($result->allowed)->toBeFalse();
    expect($result->reason)->toBe('Insufficient leave balance');
});

// -- StatusTransition Model Tests --

test('transition resolve label falls back to target status label', function (): void {
    $transition = StatusTransition::query()
        ->where('flow', WF_TEST_FLOW)->where('from_code', 'open')->where('to_code', 'in_progress')->first();

    // With explicit label
    expect($transition->resolveLabel())->toBe('Start Work');

    // Without explicit label
    $transition->label = null;
    expect($transition->resolveLabel())->toBe('In Progress');
});
