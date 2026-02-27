<?php

use App\Base\Authz\Contracts\AuthorizationService;
use App\Base\Authz\DTO\Actor;
use App\Base\Authz\Enums\PrincipalType;
use App\Modules\Core\Employee\Models\EmployeeType;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $authUser = auth()->user();
        $authActor = new Actor(
            type: PrincipalType::HUMAN_USER,
            id: (int) $authUser->getAuthIdentifier(),
            companyId: $authUser->company_id !== null ? (int) $authUser->company_id : null,
        );
        $canCreate = app(AuthorizationService::class)->can($authActor, 'core.employee_type.create')->allowed;

        return [
            'canCreate' => $canCreate,
            'employeeTypes' => EmployeeType::query()
                ->global()
                ->withCount('employees')
                ->when($this->search, fn ($q) => $q->where('code', 'like', '%'.$this->search.'%')
                    ->orWhere('label', 'like', '%'.$this->search.'%'))
                ->orderByDesc('is_system')
                ->orderBy('code')
                ->paginate(15),
        ];
    }

    public function delete(int $id): void
    {
        $type = EmployeeType::query()->findOrFail($id);
        if ($type->is_system) {
            return;
        }
        if ($type->employees_count > 0) {
            session()->flash('error', __('Cannot delete: employees are using this type.'));

            return;
        }
        $type->delete();
        session()->flash('success', __('Employee type deleted.'));
    }
}; ?>

<div>
    <x-slot name="title">{{ __('Employee Types') }}</x-slot>

    <div class="space-y-section-gap">
        <x-ui.page-header :title="__('Employee Types')" :subtitle="__('Manage employee type reference data')">
            @if ($canCreate)
                <x-slot name="actions">
                    <x-ui.button variant="primary" as="a" href="{{ route('admin.employee-types.create') }}" wire:navigate>
                        <x-icon name="heroicon-o-plus" class="w-4 h-4" />
                        {{ __('Add Type') }}
                    </x-ui.button>
                </x-slot>
            @endif
        </x-ui.page-header>

        @if (session('success'))
            <x-ui.alert variant="success">{{ session('success') }}</x-ui.alert>
        @endif
        @if (session('error'))
            <x-ui.alert variant="error">{{ session('error') }}</x-ui.alert>
        @endif

        <x-ui.card>
            <div class="mb-2">
                <x-ui.search-input
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Search by code or label...') }}"
                />
            </div>

            <div class="overflow-x-auto -mx-card-inner px-card-inner">
                <table class="min-w-full divide-y divide-border-default text-sm">
                    <thead class="bg-surface-subtle/80">
                        <tr>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Code') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Label') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Kind') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Employees') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-right text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface-card divide-y divide-border-default">
                        @forelse($employeeTypes as $type)
                            <tr wire:key="type-{{ $type->id }}" class="hover:bg-surface-subtle/50 transition-colors">
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap font-mono text-sm text-ink">{{ $type->code }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-ink">{{ $type->label }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap">
                                    @if($type->is_system)
                                        <x-ui.badge variant="default">{{ __('System') }}</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="success">{{ __('Custom') }}</x-ui.badge>
                                    @endif
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted tabular-nums">{{ $type->employees_count }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-right">
                                    @if(!$type->is_system)
                                        <a href="{{ route('admin.employee-types.edit', $type) }}" wire:navigate class="inline-flex items-center gap-1 px-3 py-1.5 text-sm rounded-lg text-accent hover:bg-surface-subtle transition-colors">
                                            <x-icon name="heroicon-o-pencil" class="w-4 h-4" />
                                            {{ __('Edit') }}
                                        </a>
                                        <x-ui.button
                                            variant="danger-ghost"
                                            size="sm"
                                            wire:click="delete({{ $type->id }})"
                                            wire:confirm="{{ __('Delete this employee type?') }}"
                                        >
                                            <x-icon name="heroicon-o-trash" class="w-4 h-4" />
                                            {{ __('Delete') }}
                                        </x-ui.button>
                                    @else
                                        <span class="text-muted text-xs">{{ __('System types cannot be edited') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-table-cell-x py-8 text-center text-sm text-muted">{{ __('No employee types found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-2">
                {{ $employeeTypes->links() }}
            </div>
        </x-ui.card>
    </div>
</div>
