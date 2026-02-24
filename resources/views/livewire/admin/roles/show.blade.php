<?php

use App\Base\Authz\Capability\CapabilityRegistry;
use App\Base\Authz\Contracts\AuthorizationService;
use App\Base\Authz\DTO\Actor;
use App\Base\Authz\Enums\PrincipalType;
use App\Base\Authz\Models\PrincipalRole;
use App\Base\Authz\Models\Role;
use App\Base\Authz\Models\RoleCapability;
use App\Modules\Core\User\Models\User;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;

new class extends Component
{
    public Role $role;

    public array $selectedCapabilities = [];

    public function mount(Role $role): void
    {
        $this->role = $role->load('capabilities');
    }

    /**
     * Assign selected capabilities to this role.
     */
    public function assignCapabilities(): void
    {
        if (! $this->checkCapability('admin.role.update')) {
            return;
        }

        if (empty($this->selectedCapabilities)) {
            return;
        }

        foreach ($this->selectedCapabilities as $capabilityKey) {
            RoleCapability::query()->firstOrCreate([
                'role_id' => $this->role->id,
                'capability_key' => $capabilityKey,
            ]);
        }

        $this->selectedCapabilities = [];
        $this->role->load('capabilities');
    }

    /**
     * Remove a capability from this role.
     */
    public function removeCapability(int $roleCapabilityId): void
    {
        if (! $this->checkCapability('admin.role.update')) {
            return;
        }

        RoleCapability::query()
            ->where('id', $roleCapabilityId)
            ->where('role_id', $this->role->id)
            ->delete();

        $this->role->load('capabilities');
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

        $canEdit = app(AuthorizationService::class)
            ->can($authActor, 'admin.role.update')
            ->allowed;

        $allCapabilities = app(CapabilityRegistry::class)->all();
        sort($allCapabilities);

        $assignedKeys = $this->role->capabilities->pluck('capability_key')->all();

        $availableCapabilities = [];
        foreach ($allCapabilities as $capability) {
            if (in_array($capability, $assignedKeys, true)) {
                continue;
            }
            $domain = explode('.', $capability, 2)[0];
            $availableCapabilities[$domain][] = $capability;
        }

        $assignedCapabilities = [];
        foreach ($this->role->capabilities as $cap) {
            $domain = explode('.', $cap->capability_key, 2)[0];
            $assignedCapabilities[$domain][] = $cap;
        }
        ksort($assignedCapabilities);

        $assignedUsers = User::query()
            ->whereIn('id', function ($query): void {
                $query->select('principal_id')
                    ->from('base_authz_principal_roles')
                    ->where('role_id', $this->role->id)
                    ->where('principal_type', PrincipalType::HUMAN_USER->value);
            })
            ->with('company')
            ->orderBy('name')
            ->get();

        return [
            'canEdit' => $canEdit,
            'availableCapabilities' => $availableCapabilities,
            'assignedCapabilities' => $assignedCapabilities,
            'assignedCount' => $this->role->capabilities->count(),
            'assignedUsers' => $assignedUsers,
        ];
    }
}; ?>

<div>
    <x-slot name="title">{{ $role->name }}</x-slot>

    <div class="space-y-section-gap">
        <x-ui.page-header :title="$role->name" :subtitle="$role->description">
            <x-slot name="actions">
                <x-ui.button variant="ghost" as="a" href="{{ route('admin.roles.index') }}" wire:navigate>
                    <x-icon name="heroicon-o-arrow-left" class="w-5 h-5" />
                    {{ __('Back') }}
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>

        @if (session('success'))
            <x-ui.alert variant="success">{{ session('success') }}</x-ui.alert>
        @endif

        @if (session('error'))
            <x-ui.alert variant="danger">{{ session('error') }}</x-ui.alert>
        @endif

        {{-- Role Details --}}
        <x-ui.card>
            <h3 class="text-[11px] uppercase tracking-wider font-semibold text-muted mb-4">{{ __('Role Details') }}</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Name') }}</dt>
                    <dd class="mt-0.5 text-sm text-ink">{{ $role->name }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Code') }}</dt>
                    <dd class="mt-0.5 text-sm text-ink font-mono text-xs">{{ $role->code }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Type') }}</dt>
                    <dd class="mt-0.5">
                        @if ($role->is_system)
                            <x-ui.badge variant="default">{{ __('System') }}</x-ui.badge>
                        @else
                            <x-ui.badge variant="success">{{ __('Custom') }}</x-ui.badge>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Description') }}</dt>
                    <dd class="mt-0.5 text-sm text-ink">{{ $role->description ?? '—' }}</dd>
                </div>
            </div>
        </x-ui.card>

        {{-- Capabilities --}}
        <x-ui.card>
            <h3 class="text-[11px] uppercase tracking-wider font-semibold text-muted">
                {{ __('Capabilities') }}
                <x-ui.badge>{{ $assignedCount }}</x-ui.badge>
            </h3>
            <p class="text-xs text-muted mt-0.5 mb-4">{{ __('Capabilities define specific actions this role can perform. Changes affect all users assigned this role.') }}</p>

            {{-- Assigned Capabilities --}}
            <div class="mb-4">
                <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted mb-2">{{ __('Capabilities') }}</dt>
                <dd>
                    @if (empty($assignedCapabilities))
                        <span class="text-sm text-muted">{{ __('No capabilities assigned.') }}</span>
                    @else
                        @foreach ($assignedCapabilities as $domain => $caps)
                            <div class="mb-3">
                                <div class="text-[11px] uppercase tracking-wider font-semibold text-muted mb-1">{{ $domain }}</div>
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($caps as $cap)
                                        <span class="inline-flex items-center gap-1">
                                            <x-ui.badge variant="success">
                                                {{ $cap->capability_key }}
                                                @if ($canEdit)
                                                    <button
                                                        type="button"
                                                        wire:click="removeCapability({{ $cap->id }})"
                                                        class="ml-1 text-current opacity-60 hover:opacity-100 transition-opacity"
                                                        title="{{ __('Remove capability') }}"
                                                    >
                                                        <x-icon name="heroicon-o-x-mark" class="w-3.5 h-3.5 stroke-[2.5]" />
                                                    </button>
                                                @endif
                                            </x-ui.badge>
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @endif
                </dd>
            </div>

            {{-- Assign Capabilities --}}
            @if ($canEdit && ! empty($availableCapabilities))
                <div
                    x-data="{ capFilter: '', selected: @entangle('selectedCapabilities') }"
                    class="mb-6"
                >
                    <div>
                        <x-ui.search-input
                            x-model="capFilter"
                            placeholder="{{ __('Search capabilities...') }}"
                        />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-1 mt-2 max-h-48 overflow-y-auto">
                        @foreach ($availableCapabilities as $domain => $capabilities)
                            @foreach ($capabilities as $capability)
                                <label
                                    x-show="!capFilter || @js(strtolower($capability)).includes(capFilter.toLowerCase())"
                                    class="flex items-center gap-2 px-2 py-1 rounded text-sm hover:bg-surface-subtle cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        value="{{ $capability }}"
                                        x-model="selected"
                                        class="rounded border-border-input text-accent focus:ring-accent"
                                    >
                                    <span class="text-ink truncate" title="{{ $capability }}">{{ $capability }}</span>
                                </label>
                            @endforeach
                        @endforeach
                    </div>
                    <div x-show="selected.length > 0" x-cloak class="mt-2">
                        <x-ui.button variant="primary" size="sm" wire:click="assignCapabilities">
                            {{ __('Assign') }} <span x-text="'(' + selected.length + ')'"></span>
                        </x-ui.button>
                    </div>
                </div>
            @endif
        </x-ui.card>

        {{-- Assigned Users --}}
        <x-ui.card>
            <h3 class="text-[11px] uppercase tracking-wider font-semibold text-muted">
                {{ __('Assigned Users') }}
                <x-ui.badge>{{ $assignedUsers->count() }}</x-ui.badge>
            </h3>
            <p class="text-xs text-muted mt-0.5 mb-4">{{ __('Users who have been assigned this role.') }}</p>

            <div class="overflow-x-auto -mx-card-inner px-card-inner">
                <table class="min-w-full divide-y divide-border-default text-sm">
                    <thead class="bg-surface-subtle/80">
                        <tr>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Name') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Email') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Company') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface-card divide-y divide-border-default">
                        @forelse($assignedUsers as $assignedUser)
                            <tr wire:key="user-{{ $assignedUser->id }}" class="hover:bg-surface-subtle/50 transition-colors">
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap">
                                    <a href="{{ route('admin.users.show', $assignedUser) }}" wire:navigate class="text-sm font-medium text-link hover:underline">{{ $assignedUser->name }}</a>
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">{{ $assignedUser->email }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">{{ $assignedUser->company?->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-table-cell-x py-8 text-center text-sm text-muted">{{ __('No users assigned.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>
</div>
