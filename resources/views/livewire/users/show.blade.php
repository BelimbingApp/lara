<?php

use App\Base\Authz\Contracts\AuthorizationService;
use App\Base\Authz\DTO\Actor;
use App\Base\Authz\Enums\PrincipalType;
use App\Base\Authz\Models\PrincipalRole;
use App\Base\Authz\Models\Role;
use App\Base\Authz\Services\EffectivePermissions;
use App\Modules\Core\Company\Models\Company;
use App\Modules\Core\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Livewire\Volt\Component;

new class extends Component
{
    public User $user;

    public string $password = '';

    public string $password_confirmation = '';

    public array $selectedRoleIds = [];

    public function mount(User $user): void
    {
        $this->user = $user->load([
            'company',
            'externalAccesses.company',
            'employees.company',
            'employees.department',
        ]);
    }

    /**
     * Save a field value via inline editing.
     */
    public function saveField(string $field, mixed $value): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user->id),
            ],
        ];

        if (! isset($rules[$field])) {
            return;
        }

        $validated = validator([$field => $value], [$field => $rules[$field]])->validate();

        $this->user->$field = $validated[$field];

        if ($field === 'email' && $this->user->isDirty('email')) {
            $this->user->email_verified_at = null;
        }

        $this->user->save();
    }

    /**
     * Save the company assignment via inline select.
     */
    public function saveCompany(?int $companyId): void
    {
        $this->user->company_id = $companyId ?: null;
        $this->user->save();
        $this->user->load('company');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(): void
    {
        $validated = $this->validate([
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $this->user->password = Hash::make($validated['password']);
        $this->user->save();

        $this->reset(['password', 'password_confirmation']);

        Session::flash('success', __('Password updated successfully.'));
    }

    /**
     * Assign selected roles to this user.
     */
    public function assignRoles(): void
    {
        if (! $this->checkCapability('core.user.update')) {
            return;
        }

        if (empty($this->selectedRoleIds) || $this->user->company_id === null) {
            return;
        }

        foreach ($this->selectedRoleIds as $roleId) {
            PrincipalRole::query()->firstOrCreate([
                'company_id' => $this->user->company_id,
                'principal_type' => PrincipalType::HUMAN_USER->value,
                'principal_id' => $this->user->id,
                'role_id' => (int) $roleId,
            ]);
        }

        $this->selectedRoleIds = [];
    }

    /**
     * Remove a role assignment from this user.
     */
    public function removeRole(int $principalRoleId): void
    {
        if (! $this->checkCapability('core.user.update')) {
            return;
        }

        PrincipalRole::query()
            ->where('id', $principalRoleId)
            ->where('principal_id', $this->user->id)
            ->where('principal_type', PrincipalType::HUMAN_USER->value)
            ->delete();
    }

    /**
     * Check if the current user has the given capability.
     *
     * Flashes a friendly error if denied.
     */
    private function checkCapability(string $capability): bool
    {
        $authUser = auth()->user();

        $actor = new Actor(
            type: PrincipalType::HUMAN_USER,
            id: (int) $authUser->getAuthIdentifier(),
            companyId: $authUser->company_id !== null ? (int) $authUser->company_id : null,
        );

        $decision = app(AuthorizationService::class)->can($actor, $capability);

        if (! $decision->allowed) {
            Session::flash('error', __('You do not have permission to perform this action.'));

            return false;
        }

        return true;
    }

    public function with(): array
    {
        $authUser = auth()->user();

        $authActor = new Actor(
            type: PrincipalType::HUMAN_USER,
            id: (int) $authUser->getAuthIdentifier(),
            companyId: $authUser->company_id !== null ? (int) $authUser->company_id : null,
        );

        $canManageRoles = app(AuthorizationService::class)
            ->can($authActor, 'core.user.update')
            ->allowed;

        $assignedRoles = PrincipalRole::query()
            ->with('role')
            ->where('principal_type', PrincipalType::HUMAN_USER->value)
            ->where('principal_id', $this->user->id)
            ->get();

        $assignedRoleIds = $assignedRoles->pluck('role_id')->all();

        $availableRoles = Role::query()
            ->whereNull('company_id')
            ->where('is_system', true)
            ->whereNotIn('id', $assignedRoleIds)
            ->orderBy('name')
            ->get();

        $effectivePermissions = [];

        if ($this->user->company_id !== null) {
            $actor = new Actor(
                type: PrincipalType::HUMAN_USER,
                id: $this->user->id,
                companyId: (int) $this->user->company_id,
            );

            $permissions = EffectivePermissions::forActor($actor);
            $allowed = $permissions->allowed();
            sort($allowed);

            foreach ($allowed as $capability) {
                $domain = explode('.', $capability, 2)[0];
                $effectivePermissions[$domain][] = $capability;
            }
        }

        return [
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
            'assignedRoles' => $assignedRoles,
            'availableRoles' => $availableRoles,
            'canManageRoles' => $canManageRoles,
            'effectivePermissions' => $effectivePermissions,
        ];
    }
}; ?>

<div>
    <x-slot name="title">{{ $user->name }}</x-slot>

    <div class="space-y-section-gap">
        <x-ui.page-header :title="$user->name" :subtitle="__('User details')">
            <x-slot name="actions">
                @if($user->id !== auth()->id() && !session('impersonation.original_user_id'))
                    <form method="POST" action="{{ route('admin.impersonate.start', $user) }}">
                        @csrf
                        <x-ui.button type="submit" variant="ghost" title="{{ __('View as this user') }}">
                            <x-icon name="heroicon-o-eye" class="w-4 h-4" />
                            {{ __('Impersonate') }}
                        </x-ui.button>
                    </form>
                @endif
                <x-ui.button variant="ghost" as="a" href="{{ route('admin.users.index') }}" wire:navigate>
                    <x-icon name="heroicon-o-arrow-left" class="w-5 h-5" />
                    {{ __('Back') }}
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>

        @if (session('success'))
            <x-ui.alert variant="success">{{ session('success') }}</x-ui.alert>
        @endif

        <x-ui.card>
            <h3 class="text-[11px] uppercase tracking-wider font-semibold text-muted mb-4">{{ __('User Details') }}</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div x-data="{ editing: false, val: '{{ addslashes($user->name) }}' }">
                    <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Name') }}</dt>
                    <dd class="text-sm text-ink">
                        <div x-show="!editing" @click="editing = true; $nextTick(() => $refs.input.select())" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                            <span x-text="val || '-'"></span>
                            <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                        </div>
                        <input
                            x-show="editing"
                            x-ref="input"
                            x-model="val"
                            @keydown.enter="editing = false; $wire.saveField('name', val)"
                            @keydown.escape="editing = false; val = '{{ addslashes($user->name) }}'"
                            @blur="editing = false; $wire.saveField('name', val)"
                            type="text"
                            class="w-full px-1 -mx-1 py-0.5 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                        />
                    </dd>
                </div>
                <div x-data="{ editing: false, val: '{{ addslashes($user->email) }}' }">
                    <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Email') }}</dt>
                    <dd class="text-sm text-ink">
                        <div x-show="!editing" @click="editing = true; $nextTick(() => $refs.input.select())" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                            <span x-text="val || '-'"></span>
                            <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                        </div>
                        <input
                            x-show="editing"
                            x-ref="input"
                            x-model="val"
                            @keydown.enter="editing = false; $wire.saveField('email', val)"
                            @keydown.escape="editing = false; val = '{{ addslashes($user->email) }}'"
                            @blur="editing = false; $wire.saveField('email', val)"
                            type="email"
                            class="w-full px-1 -mx-1 py-0.5 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                        />
                    </dd>
                </div>
                <div x-data="{ editing: false, val: '{{ $user->company_id ?? '' }}' }">
                    <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Company') }}</dt>
                    <dd class="text-sm text-ink">
                        <div x-show="!editing" @click="editing = true" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                            <span>
                                @if($user->company)
                                    {{ $user->company->name }}
                                @else
                                    <span class="text-muted">{{ __('None') }}</span>
                                @endif
                            </span>
                            <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                        </div>
                        <select
                            x-show="editing"
                            x-model="val"
                            @change="editing = false; $wire.saveCompany(val ? parseInt(val) : null)"
                            @keydown.escape="editing = false; val = '{{ $user->company_id ?? '' }}'"
                            @blur="editing = false"
                            class="px-2 py-1 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                        >
                            <option value="">{{ __('None') }}</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </dd>
                </div>
                <div>
                    <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Email Verified') }}</dt>
                    <dd class="mt-0.5 text-sm text-ink">
                        @if ($user->email_verified_at)
                            <x-ui.badge variant="success">{{ $user->email_verified_at->format('Y-m-d H:i') }}</x-ui.badge>
                        @else
                            <x-ui.badge variant="warning">{{ __('Unverified') }}</x-ui.badge>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Created') }}</dt>
                    <dd class="mt-0.5 text-sm text-muted tabular-nums">{{ $user->created_at->format('Y-m-d H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Updated') }}</dt>
                    <dd class="mt-0.5 text-sm text-muted tabular-nums">{{ $user->updated_at->format('Y-m-d H:i') }}</dd>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <h3 class="text-[11px] uppercase tracking-wider font-semibold text-muted">
                {{ __('Roles & Permissions') }}
                <x-ui.badge>{{ $assignedRoles->count() }}</x-ui.badge>
            </h3>
            <p class="text-xs text-muted mt-0.5 mb-4">{{ __('Roles determine what this user can do. Each role grants a set of capabilities. Effective permissions show the combined result of all assigned roles.') }}</p>

            {{-- Roles --}}
            <div class="mb-4">
                <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted mb-2">{{ __('Roles') }}</dt>
                <dd>
                    @if($assignedRoles->isEmpty())
                        <span class="text-sm text-muted">{{ __('No roles assigned.') }}</span>
                    @else
                        <div class="flex flex-wrap gap-2">
                            @foreach($assignedRoles as $assignment)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-surface-subtle text-ink">
                                    {{ $assignment->role->name }}
                                    @if($canManageRoles)
                                        <button
                                            type="button"
                                            wire:click="removeRole({{ $assignment->id }})"
                                            wire:confirm="{{ __('Remove :role from this user?', ['role' => $assignment->role->name]) }}"
                                            class="ml-0.5 text-muted hover:text-status-danger transition-colors"
                                            title="{{ __('Remove role') }}"
                                        >
                                            <x-icon name="heroicon-o-x-mark" class="w-3 h-3" />
                                        </button>
                                    @endif
                                </span>
                            @endforeach
                        </div>
                    @endif
                </dd>
            </div>

            {{-- Assign Roles --}}
            @if($canManageRoles && $availableRoles->isNotEmpty())
                <div
                    x-data="{ roleFilter: '', selected: @entangle('selectedRoleIds') }"
                    class="mb-6"
                >
                    <div>
                        <x-ui.search-input
                            x-model="roleFilter"
                            placeholder="{{ __('Search roles...') }}"
                        />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-1 mt-2 max-h-48 overflow-y-auto">
                        @foreach($availableRoles as $role)
                            <label
                                x-show="!roleFilter || @js(strtolower($role->name)).includes(roleFilter.toLowerCase()) || @js(strtolower($role->code)).includes(roleFilter.toLowerCase())"
                                class="flex items-center gap-2 px-2 py-1 rounded text-sm hover:bg-surface-subtle cursor-pointer"
                            >
                                <input
                                    type="checkbox"
                                    value="{{ $role->id }}"
                                    x-model="selected"
                                    class="rounded border-border-input text-accent focus:ring-accent"
                                >
                                <span class="text-ink truncate" title="{{ $role->description ?? $role->name }}">{{ $role->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div x-show="selected.length > 0" x-cloak class="mt-2">
                        <x-ui.button variant="primary" size="sm" wire:click="assignRoles">
                            {{ __('Assign') }} (<span x-text="selected.length"></span>)
                        </x-ui.button>
                    </div>
                </div>
            @endif

            {{-- Effective Permissions --}}
            <div x-data="{ open: false }">
                <button
                    @click="open = !open"
                    class="flex items-center gap-2 w-full text-left"
                    type="button"
                >
                    <span class="text-[12px] shrink-0 text-accent w-3.5 text-center" aria-hidden="true">
                        <span x-show="!open">⮞</span>
                        <span x-show="open">⮟</span>
                    </span>
                    <h3 class="text-[11px] uppercase tracking-wider font-semibold text-muted">
                        {{ __('Effective Permissions') }}
                        <x-ui.badge>{{ collect($effectivePermissions)->flatten()->count() }}</x-ui.badge>
                    </h3>
                </button>

                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="mt-3"
                    style="display: none;"
                >
                    @forelse($effectivePermissions as $domain => $capabilities)
                        <div class="mb-3">
                            <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted mb-1">{{ $domain }}</dt>
                            <dd class="flex flex-wrap gap-1">
                                @foreach($capabilities as $capability)
                                    <x-ui.badge variant="success">{{ $capability }}</x-ui.badge>
                                @endforeach
                            </dd>
                        </div>
                    @empty
                        <p class="text-sm text-muted">{{ __('No permissions. Assign a role or company first.') }}</p>
                    @endforelse
                </div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <div x-data="{ open: false }">
                <button
                    @click="open = !open"
                    class="flex items-center gap-2 w-full text-left"
                    type="button"
                >
                    <span class="text-[12px] shrink-0 text-accent w-3.5 text-center" aria-hidden="true">
                        <span x-show="!open">⮞</span>
                        <span x-show="open">⮟</span>
                    </span>
                    <h3 class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Change Password') }}</h3>
                </button>

                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-1"
                    class="mt-4"
                    style="display: none;"
                >
                    <form wire:submit="updatePassword" class="space-y-4 max-w-md">
                        <x-ui.input
                            wire:model="password"
                            label="{{ __('New Password') }}"
                            type="password"
                            required
                            autocomplete="new-password"
                            placeholder="{{ __('Enter new password') }}"
                            :error="$errors->first('password')"
                        />

                        <x-ui.input
                            wire:model="password_confirmation"
                            label="{{ __('Confirm New Password') }}"
                            type="password"
                            required
                            autocomplete="new-password"
                            placeholder="{{ __('Confirm new password') }}"
                            :error="$errors->first('password_confirmation')"
                        />

                        <x-ui.button type="submit" variant="primary">
                            {{ __('Update Password') }}
                        </x-ui.button>
                    </form>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <h3 class="text-[11px] uppercase tracking-wider font-semibold text-muted">
                {{ __('Employee Records') }}
                <x-ui.badge>{{ $user->employees->count() }}</x-ui.badge>
            </h3>
            <p class="text-xs text-muted mt-0.5 mb-4">{{ __('Employment records linking this user to companies. A user can have multiple records across different companies (e.g. contractors). Not all employees require a user account.') }}</p>

            <div class="overflow-x-auto -mx-card-inner px-card-inner">
                <table class="min-w-full divide-y divide-border-default text-sm">
                    <thead class="bg-surface-subtle/80">
                        <tr>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Employee No.') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Company') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Department') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Designation') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Status') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Employment Start') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface-card divide-y divide-border-default">
                        @forelse($user->employees as $employee)
                            <tr wire:key="employee-{{ $employee->id }}" class="hover:bg-surface-subtle/50 transition-colors">
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm font-medium text-ink">{{ $employee->employee_number ?? '—' }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">
                                    @if ($employee->company)
                                        <a href="{{ route('admin.companies.show', $employee->company) }}" wire:navigate class="text-link hover:underline">{{ $employee->company->name }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">{{ $employee->department?->name ?? '—' }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">{{ $employee->designation ?? '—' }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap">
                                    <x-ui.badge :variant="match($employee->status) {
                                        'active' => 'success',
                                        'inactive' => 'default',
                                        'terminated' => 'danger',
                                        'pending' => 'warning',
                                        default => 'default',
                                    }">{{ ucfirst($employee->status) }}</x-ui.badge>
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted tabular-nums">{{ $employee->employment_start?->format('Y-m-d') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-table-cell-x py-8 text-center text-sm text-muted">{{ __('No employee records.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        <x-ui.card>
            <h3 class="text-[11px] uppercase tracking-wider font-semibold text-muted">
                {{ __('External Accesses') }}
                <x-ui.badge>{{ $user->externalAccesses->count() }}</x-ui.badge>
            </h3>
            <p class="text-xs text-muted mt-0.5 mb-4">{{ __('Portal access granted to this user by other companies. Allows customers or suppliers to view orders, invoices, and other shared data.') }}</p>

            <div class="overflow-x-auto -mx-card-inner px-card-inner">
                <table class="min-w-full divide-y divide-border-default text-sm">
                    <thead class="bg-surface-subtle/80">
                        <tr>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Granting Company') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Permissions') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Status') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Granted At') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Expires At') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface-card divide-y divide-border-default">
                        @forelse($user->externalAccesses as $access)
                            <tr wire:key="access-{{ $access->id }}" class="hover:bg-surface-subtle/50 transition-colors">
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">
                                    @if ($access->company)
                                        <a href="{{ route('admin.companies.show', $access->company) }}" wire:navigate class="text-link hover:underline">{{ $access->company->name }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">
                                    @if ($access->permissions)
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($access->permissions as $permission)
                                                <x-ui.badge variant="default">{{ $permission }}</x-ui.badge>
                                            @endforeach
                                        </div>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap">
                                    @if ($access->isValid())
                                        <x-ui.badge variant="success">{{ __('Valid') }}</x-ui.badge>
                                    @elseif ($access->hasExpired())
                                        <x-ui.badge variant="danger">{{ __('Expired') }}</x-ui.badge>
                                    @elseif ($access->isPending())
                                        <x-ui.badge variant="warning">{{ __('Pending') }}</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="default">{{ __('Inactive') }}</x-ui.badge>
                                    @endif
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted tabular-nums">{{ $access->access_granted_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted tabular-nums">{{ $access->access_expires_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-table-cell-x py-8 text-center text-sm text-muted">{{ __('No external accesses.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>
</div>
