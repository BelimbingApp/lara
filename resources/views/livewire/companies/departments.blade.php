<?php

use App\Modules\Core\Company\Models\Company;
use App\Modules\Core\Company\Models\Department;
use App\Modules\Core\Company\Models\DepartmentType;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public Company $company;

    public bool $showCreateModal = false;

    public int $create_department_type_id = 0;

    public string $create_status = 'active';

    public function mount(Company $company): void
    {
        $this->company = $company;
    }

    public function with(): array
    {
        $existingTypeIds = Department::query()
            ->where('company_id', $this->company->id)
            ->pluck('department_type_id')
            ->toArray();

        return [
            'departments' => Department::query()
                ->where('company_id', $this->company->id)
                ->with('type')
                ->paginate(15),
            'availableTypes' => DepartmentType::query()
                ->active()
                ->whereNotIn('id', $existingTypeIds)
                ->orderBy('name')
                ->get(),
        ];
    }

    public function createDepartment(): void
    {
        if ($this->create_department_type_id === 0) {
            return;
        }

        Department::query()->create([
            'company_id' => $this->company->id,
            'department_type_id' => $this->create_department_type_id,
            'status' => $this->create_status,
        ]);

        $this->showCreateModal = false;
        $this->reset(['create_department_type_id', 'create_status']);
        Session::flash('success', __('Department created.'));
    }

    public function saveStatus(int $departmentId, string $status): void
    {
        if (! in_array($status, ['active', 'inactive', 'suspended'])) {
            return;
        }

        $dept = Department::query()->findOrFail($departmentId);
        $dept->status = $status;
        $dept->save();

        Session::flash('success', __('Department status updated.'));
    }

    public function deleteDepartment(int $departmentId): void
    {
        Department::query()->findOrFail($departmentId)->delete();
        Session::flash('success', __('Department deleted.'));
    }
}; ?>

<div>
    <x-slot name="title">{{ __('Departments') }} — {{ $company->name }}</x-slot>

    <div class="space-y-section-gap">
        <x-ui.page-header :title="__('Departments') . ' — ' . $company->name">
            <x-slot name="actions">
                <a href="{{ route('admin.companies.show', $company) }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl hover:bg-surface-subtle text-link transition-colors">
                    <x-icon name="heroicon-o-arrow-left" class="w-5 h-5" />
                    {{ __('Back to Company') }}
                </a>
                <x-ui.button variant="primary" wire:click="$set('showCreateModal', true)">
                    <x-icon name="heroicon-o-plus" class="w-4 h-4" />
                    {{ __('Add Department') }}
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>

        @if (session('success'))
            <x-ui.alert variant="success">{{ session('success') }}</x-ui.alert>
        @endif

        @if (session('error'))
            <x-ui.alert variant="error">{{ session('error') }}</x-ui.alert>
        @endif

        <x-ui.card>
            <div class="overflow-x-auto -mx-card-inner px-card-inner">
                <table class="min-w-full divide-y divide-border-default text-sm">
                    <thead class="bg-surface-subtle/80">
                        <tr>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Department Type') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Category') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Status') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-right text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface-card divide-y divide-border-default">
                        @forelse($departments as $department)
                            <tr wire:key="department-{{ $department->id }}" class="hover:bg-surface-subtle/50 transition-colors">
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-ink">
                                    @if($department->type?->code)
                                        <span class="font-mono text-xs text-muted">{{ $department->type->code }}</span>
                                        <span class="ml-1.5">{{ $department->type->name }}</span>
                                    @else
                                        {{ $department->type?->name ?? '-' }}
                                    @endif
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">
                                    {{ $department->type?->category ?? '-' }}
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap"
                                    x-data="{ editing: false, val: '{{ $department->status }}' }"
                                >
                                    <div x-show="!editing" @click="editing = true" class="group flex items-center gap-1.5 cursor-pointer">
                                        <x-ui.badge :variant="match($department->status) { 'active' => 'success', 'suspended' => 'danger', default => 'default' }">
                                            {{ ucfirst($department->status) }}
                                        </x-ui.badge>
                                        <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                                    </div>
                                    <select
                                        x-show="editing"
                                        x-model="val"
                                        @change="editing = false; $wire.saveStatus({{ $department->id }}, val)"
                                        @keydown.escape="editing = false; val = '{{ $department->status }}'"
                                        @blur="editing = false"
                                        class="px-2 py-1 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                                    >
                                        <option value="active">{{ __('Active') }}</option>
                                        <option value="inactive">{{ __('Inactive') }}</option>
                                        <option value="suspended">{{ __('Suspended') }}</option>
                                    </select>
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            wire:click="deleteDepartment({{ $department->id }})"
                                            wire:confirm="{{ __('Are you sure you want to delete this department?') }}"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-sm rounded-lg hover:bg-status-danger-subtle text-status-danger transition-colors"
                                        >
                                            <x-icon name="heroicon-o-trash" class="w-4 h-4" />
                                            {{ __('Delete') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-table-cell-x py-8 text-center text-sm text-muted">{{ __('No departments found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-2">
                {{ $departments->links() }}
            </div>
        </x-ui.card>
    </div>

    <x-ui.modal wire:model="showCreateModal" class="max-w-lg">
        <form wire:submit="createDepartment" class="p-6 space-y-6">
            <h2 class="text-lg font-medium tracking-tight text-ink">{{ __('Add Department') }}</h2>

            <div class="space-y-4">
                <div class="space-y-1">
                    <label class="block text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Department Type') }}</label>
                    <x-ui.select wire:model="create_department_type_id">
                        <option value="0">{{ __('Select a department type...') }}</option>
                        @foreach($availableTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->code ? $type->code . ' — ' : '' }}{{ $type->name }}</option>
                        @endforeach
                    </x-ui.select>
                </div>

                <div class="space-y-1">
                    <label class="block text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Status') }}</label>
                    <x-ui.select wire:model="create_status">
                        <option value="active">{{ __('Active') }}</option>
                        <option value="inactive">{{ __('Inactive') }}</option>
                        <option value="suspended">{{ __('Suspended') }}</option>
                    </x-ui.select>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <x-ui.button type="submit" variant="primary">
                    {{ __('Create') }}
                </x-ui.button>
                <button type="button" wire:click="$set('showCreateModal', false)" class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl hover:bg-surface-subtle text-link transition-colors">
                    {{ __('Cancel') }}
                </button>
            </div>
        </form>
    </x-ui.modal>

</div>
