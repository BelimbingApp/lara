<?php

use App\Modules\Core\Employee\Models\Employee;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $type_filter = 'all'; // all | human | digital_worker

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'employees' => Employee::query()
                ->with('company', 'department.type', 'employeeType')
                ->when($this->search, function ($query, $search): void {
                    $query
                        ->where('full_name', 'like', '%'.$search.'%')
                        ->orWhere('short_name', 'like', '%'.$search.'%')
                        ->orWhere('employee_number', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('designation', 'like', '%'.$search.'%')
                        ->orWhere('job_description', 'like', '%'.$search.'%');
                })
                ->when($this->type_filter === 'human', fn ($q) => $q->human())
                ->when($this->type_filter === 'digital_worker', fn ($q) => $q->digitalWorker())
                ->latest()
                ->paginate(15),
        ];
    }

    public function statusVariant(string $status): string
    {
        return match ($status) {
            'active' => 'success',
            'terminated' => 'danger',
            'probation' => 'warning',
            'inactive', 'pending' => 'default',
            default => 'default',
        };
    }

    public function employeeTypeLabel(Employee $employee): string
    {
        return $employee->employeeType?->label ?? ucfirst(str_replace('_', ' ', $employee->employee_type));
    }

    public function delete(int $employeeId): void
    {
        $employee = Employee::query()->findOrFail($employeeId);

        $employee->delete();

        Session::flash('success', __('Employee deleted successfully.'));
    }
}; ?>

<div>
    <x-slot name="title">{{ __('Employee Management') }}</x-slot>

    <div class="space-y-section-gap">
        <x-ui.page-header :title="__('Employee Management')">
            <x-slot name="actions">
                <x-ui.button
                    variant="primary"
                    as="a"
                    href="{{ route('admin.employees.create') }}"
                    wire:navigate
                >
                    <x-icon name="heroicon-o-plus" class="w-4 h-4" />
                    {{ __('Create Employee') }}
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
            <div class="mb-4 flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <x-ui.search-input
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Search by name, employee number, email, designation, or job description...') }}"
                    />
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Filter') }}</label>
                    <select
                        wire:model.live="type_filter"
                        class="px-input-x py-input-y text-sm border border-border-input rounded-lg bg-surface-card text-ink focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-0"
                    >
                        <option value="all">{{ __('All') }}</option>
                        <option value="human">{{ __('Human only') }}</option>
                        <option value="digital_worker">{{ __('Digital Worker only') }}</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto -mx-card-inner px-card-inner">
                <table class="min-w-full divide-y divide-border-default text-sm">
                    <thead class="bg-surface-subtle/80">
                        <tr>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Employee') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Company') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Department') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Designation') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Type') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Status') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-right text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface-card divide-y divide-border-default">
                        @forelse($employees as $employee)
                            <tr wire:key="employee-{{ $employee->id }}" class="hover:bg-surface-subtle/50 transition-colors">
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap">
                                    <a href="{{ route('admin.employees.show', $employee) }}" wire:navigate class="text-sm font-medium text-link hover:underline">{{ $employee->full_name }}</a>
                                    <div class="text-xs text-muted tabular-nums">{{ $employee->employee_number }}</div>
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">
                                    {{ $employee->company?->name ?? '-' }}
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">
                                    {{ $employee->department?->type?->name ?? '-' }}
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">
                                    {{ $employee->designation ?? '-' }}
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap">
                                    @if($employee->isDigitalWorker())
                                        <x-ui.badge variant="info">{{ $this->employeeTypeLabel($employee) }}</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="default">{{ $this->employeeTypeLabel($employee) }}</x-ui.badge>
                                    @endif
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap">
                                    <x-ui.badge :variant="$this->statusVariant($employee->status)">{{ ucfirst($employee->status) }}</x-ui.badge>
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            wire:click="delete({{ $employee->id }})"
                                            wire:confirm="{{ __('Are you sure you want to delete this employee?') }}"
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
                                <td colspan="7" class="px-table-cell-x py-8 text-center text-sm text-muted">{{ __('No employees found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-2">
                {{ $employees->links() }}
            </div>
        </x-ui.card>
    </div>
</div>
