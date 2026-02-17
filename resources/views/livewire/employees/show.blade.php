<?php

use App\Modules\Core\Company\Models\Department;
use App\Modules\Core\Employee\Models\Employee;
use Livewire\Volt\Component;

new class extends Component
{
    public Employee $employee;

    public function mount(Employee $employee): void
    {
        $this->employee = $employee->load([
            'company',
            'department.type',
            'supervisor',
            'user',
            'subordinates',
        ]);
    }

    public function saveField(string $field, mixed $value): void
    {
        $rules = [
            'full_name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'mobile_number' => ['nullable', 'string', 'max:255'],
            'employee_number' => ['required', 'string', 'max:255'],
        ];

        if (! isset($rules[$field])) {
            return;
        }

        $validated = validator([$field => $value], [$field => $rules[$field]])->validate();

        $this->employee->$field = $validated[$field];
        $this->employee->save();
    }

    public function saveStatus(string $status): void
    {
        if (! in_array($status, ['pending', 'probation', 'active', 'inactive', 'terminated'])) {
            return;
        }

        $this->employee->status = $status;
        $this->employee->save();
    }

    public function saveEmployeeType(string $type): void
    {
        if (! in_array($type, ['full_time', 'part_time', 'contractor', 'intern'])) {
            return;
        }

        $this->employee->employee_type = $type;
        $this->employee->save();
    }

    public function saveDepartment(?int $departmentId): void
    {
        $this->employee->department_id = $departmentId ?: null;
        $this->employee->save();
        $this->employee->load('department.type');
    }

    public function saveSupervisor(?int $supervisorId): void
    {
        $this->employee->supervisor_id = $supervisorId ?: null;
        $this->employee->save();
        $this->employee->load('supervisor');
    }

    public function with(): array
    {
        return [
            'departments' => Department::query()
                ->where('company_id', $this->employee->company_id)
                ->with('type')
                ->get(['id', 'company_id', 'department_type_id']),
            'supervisors' => Employee::query()
                ->where('company_id', $this->employee->company_id)
                ->where('id', '!=', $this->employee->id)
                ->orderBy('full_name')
                ->get(['id', 'full_name']),
        ];
    }
}; ?>

<div>
    <x-slot name="title">{{ $employee->displayName() }}</x-slot>

    <div class="space-y-section-gap">
        <x-ui.page-header :title="$employee->displayName()" :subtitle="$employee->designation">
            <x-slot name="actions">
                <a href="{{ route('admin.employees.index') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl hover:bg-surface-subtle text-link transition-colors">
                    <x-icon name="heroicon-o-arrow-left" class="w-5 h-5" />
                    {{ __('Back to List') }}
                </a>
            </x-slot>
        </x-ui.page-header>

        @if (session('success'))
            <x-ui.alert variant="success">{{ session('success') }}</x-ui.alert>
        @endif

        <x-ui.card>
            <h3 class="text-[11px] uppercase tracking-wider font-semibold text-muted mb-4">{{ __('Employee Details') }}</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div x-data="{ editing: false, val: '{{ addslashes($employee->full_name) }}' }">
                        <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Full Name') }}</dt>
                        <dd class="text-sm text-ink">
                            <div x-show="!editing" @click="editing = true; $nextTick(() => $refs.input.select())" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                                <span x-text="val || '-'"></span>
                                <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                            </div>
                            <input
                                x-show="editing"
                                x-ref="input"
                                x-model="val"
                                @keydown.enter="editing = false; $wire.saveField('full_name', val)"
                                @keydown.escape="editing = false; val = '{{ addslashes($employee->full_name) }}'"
                                @blur="editing = false; $wire.saveField('full_name', val)"
                                type="text"
                                class="w-full px-1 -mx-1 py-0.5 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                            />
                        </dd>
                    </div>
                    <div x-data="{ editing: false, val: '{{ addslashes($employee->short_name ?? '') }}' }">
                        <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Short Name') }}</dt>
                        <dd class="text-sm text-ink">
                            <div x-show="!editing" @click="editing = true; $nextTick(() => $refs.input.select())" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                                <span x-text="val || '-'"></span>
                                <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                            </div>
                            <input
                                x-show="editing"
                                x-ref="input"
                                x-model="val"
                                @keydown.enter="editing = false; $wire.saveField('short_name', val)"
                                @keydown.escape="editing = false; val = '{{ addslashes($employee->short_name ?? '') }}'"
                                @blur="editing = false; $wire.saveField('short_name', val)"
                                type="text"
                                class="w-full px-1 -mx-1 py-0.5 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                            />
                        </dd>
                    </div>
                    <div x-data="{ editing: false, val: '{{ addslashes($employee->employee_number) }}' }">
                        <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Employee Number') }}</dt>
                        <dd class="text-sm text-ink">
                            <div x-show="!editing" @click="editing = true; $nextTick(() => $refs.input.select())" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                                <span class="font-mono" x-text="val || '-'"></span>
                                <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                            </div>
                            <input
                                x-show="editing"
                                x-ref="input"
                                x-model="val"
                                @keydown.enter="editing = false; $wire.saveField('employee_number', val)"
                                @keydown.escape="editing = false; val = '{{ addslashes($employee->employee_number) }}'"
                                @blur="editing = false; $wire.saveField('employee_number', val)"
                                type="text"
                                class="w-full px-1 -mx-1 py-0.5 text-sm font-mono border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                            />
                        </dd>
                    </div>
                    <div x-data="{ editing: false, val: '{{ addslashes($employee->designation ?? '') }}' }">
                        <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Designation') }}</dt>
                        <dd class="text-sm text-ink">
                            <div x-show="!editing" @click="editing = true; $nextTick(() => $refs.input.select())" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                                <span x-text="val || '-'"></span>
                                <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                            </div>
                            <input
                                x-show="editing"
                                x-ref="input"
                                x-model="val"
                                @keydown.enter="editing = false; $wire.saveField('designation', val)"
                                @keydown.escape="editing = false; val = '{{ addslashes($employee->designation ?? '') }}'"
                                @blur="editing = false; $wire.saveField('designation', val)"
                                type="text"
                                class="w-full px-1 -mx-1 py-0.5 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                            />
                        </dd>
                    </div>
                    <div x-data="{ editing: false, val: '{{ addslashes($employee->email ?? '') }}' }">
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
                                @keydown.escape="editing = false; val = '{{ addslashes($employee->email ?? '') }}'"
                                @blur="editing = false; $wire.saveField('email', val)"
                                type="text"
                                class="w-full px-1 -mx-1 py-0.5 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                            />
                        </dd>
                    </div>
                    <div x-data="{ editing: false, val: '{{ addslashes($employee->mobile_number ?? '') }}' }">
                        <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Mobile Number') }}</dt>
                        <dd class="text-sm text-ink">
                            <div x-show="!editing" @click="editing = true; $nextTick(() => $refs.input.select())" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                                <span x-text="val || '-'"></span>
                                <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                            </div>
                            <input
                                x-show="editing"
                                x-ref="input"
                                x-model="val"
                                @keydown.enter="editing = false; $wire.saveField('mobile_number', val)"
                                @keydown.escape="editing = false; val = '{{ addslashes($employee->mobile_number ?? '') }}'"
                                @blur="editing = false; $wire.saveField('mobile_number', val)"
                                type="text"
                                class="w-full px-1 -mx-1 py-0.5 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                            />
                        </dd>
                    </div>
                </div>
        </x-ui.card>

        <x-ui.card>
            <h3 class="text-[11px] uppercase tracking-wider font-semibold text-muted mb-4">{{ __('Employment Information') }}</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Company') }}</dt>
                        <dd class="text-sm text-ink px-1 -mx-1 py-0.5">{{ $employee->company?->name ?? '-' }}</dd>
                    </div>
                    <div x-data="{ editing: false, val: '{{ $employee->department_id ?? '' }}' }">
                        <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Department') }}</dt>
                        <dd class="text-sm text-ink">
                            <div x-show="!editing" @click="editing = true" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                                <span>
                                    @if($employee->department)
                                        {{ $employee->department->type->name ?? '-' }}
                                    @else
                                        <span class="text-muted">{{ __('None') }}</span>
                                    @endif
                                </span>
                                <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                            </div>
                            <select
                                x-show="editing"
                                x-model="val"
                                @change="editing = false; $wire.saveDepartment(val ? parseInt(val) : null)"
                                @keydown.escape="editing = false; val = '{{ $employee->department_id ?? '' }}'"
                                @blur="editing = false"
                                class="px-2 py-1 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                            >
                                <option value="">{{ __('None') }}</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->type->name }}</option>
                                @endforeach
                            </select>
                        </dd>
                    </div>
                    <div x-data="{ editing: false, val: '{{ $employee->supervisor_id ?? '' }}' }">
                        <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Supervisor') }}</dt>
                        <dd class="text-sm text-ink">
                            <div x-show="!editing" @click="editing = true" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                                <span>
                                    @if($employee->supervisor)
                                        {{ $employee->supervisor->full_name }}
                                    @else
                                        <span class="text-muted">{{ __('None') }}</span>
                                    @endif
                                </span>
                                <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                            </div>
                            <select
                                x-show="editing"
                                x-model="val"
                                @change="editing = false; $wire.saveSupervisor(val ? parseInt(val) : null)"
                                @keydown.escape="editing = false; val = '{{ $employee->supervisor_id ?? '' }}'"
                                @blur="editing = false"
                                class="px-2 py-1 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                            >
                                <option value="">{{ __('None') }}</option>
                                @foreach($supervisors as $sup)
                                    <option value="{{ $sup->id }}">{{ $sup->full_name }}</option>
                                @endforeach
                            </select>
                        </dd>
                    </div>
                    <div x-data="{ editing: false, val: '{{ $employee->employee_type ?? '' }}' }">
                        <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Employee Type') }}</dt>
                        <dd class="mt-0.5">
                            <div x-show="!editing" @click="editing = true" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                                <span class="text-sm text-ink">{{ $employee->employee_type ? ucwords(str_replace('_', ' ', $employee->employee_type)) : '-' }}</span>
                                <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                            </div>
                            <select
                                x-show="editing"
                                x-model="val"
                                @change="editing = false; $wire.saveEmployeeType(val)"
                                @keydown.escape="editing = false; val = '{{ $employee->employee_type ?? '' }}'"
                                @blur="editing = false"
                                class="px-2 py-1 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                            >
                                <option value="full_time">{{ __('Full Time') }}</option>
                                <option value="part_time">{{ __('Part Time') }}</option>
                                <option value="contractor">{{ __('Contractor') }}</option>
                                <option value="intern">{{ __('Intern') }}</option>
                            </select>
                        </dd>
                    </div>
                    <div x-data="{ editing: false, val: '{{ $employee->status }}' }">
                        <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Status') }}</dt>
                        <dd class="mt-0.5">
                            <div x-show="!editing" @click="editing = true" class="group flex items-center gap-1.5 cursor-pointer">
                                <x-ui.badge :variant="match($employee->status) {
                                    'active' => 'success',
                                    'terminated' => 'danger',
                                    'probation' => 'warning',
                                    default => 'default',
                                }">{{ ucfirst($employee->status) }}</x-ui.badge>
                                <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                            </div>
                            <select
                                x-show="editing"
                                x-model="val"
                                @change="editing = false; $wire.saveStatus(val)"
                                @keydown.escape="editing = false; val = '{{ $employee->status }}'"
                                @blur="editing = false"
                                class="px-2 py-1 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                            >
                                <option value="pending">{{ __('Pending') }}</option>
                                <option value="probation">{{ __('Probation') }}</option>
                                <option value="active">{{ __('Active') }}</option>
                                <option value="inactive">{{ __('Inactive') }}</option>
                                <option value="terminated">{{ __('Terminated') }}</option>
                            </select>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('User') }}</dt>
                        <dd class="text-sm text-ink px-1 -mx-1 py-0.5">{{ $employee->user?->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Employment Start') }}</dt>
                        <dd class="text-sm text-ink px-1 -mx-1 py-0.5 tabular-nums">{{ $employee->employment_start?->format('Y-m-d') ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Employment End') }}</dt>
                        <dd class="text-sm text-ink px-1 -mx-1 py-0.5 tabular-nums">{{ $employee->employment_end?->format('Y-m-d') ?? '-' }}</dd>
                    </div>
                </div>
        </x-ui.card>

        @if($employee->subordinates->isNotEmpty())
            <x-ui.card>
                <h3 class="text-[11px] uppercase tracking-wider font-semibold text-muted mb-4">
                    {{ __('Subordinates') }}
                    <x-ui.badge>{{ $employee->subordinates->count() }}</x-ui.badge>
                </h3>

                <div class="overflow-x-auto -mx-card-inner px-card-inner">
                    <table class="min-w-full divide-y divide-border-default text-sm">
                        <thead class="bg-surface-subtle/80">
                            <tr>
                                <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Name') }}</th>
                                <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Designation') }}</th>
                                <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Status') }}</th>
                                <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Department') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-surface-card divide-y divide-border-default">
                            @foreach($employee->subordinates as $sub)
                                <tr wire:key="sub-{{ $sub->id }}" class="hover:bg-surface-subtle/50 transition-colors">
                                    <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-ink font-medium">
                                        <a href="{{ route('admin.employees.show', $sub) }}" wire:navigate class="text-link hover:underline">{{ $sub->displayName() }}</a>
                                    </td>
                                    <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">{{ $sub->designation ?? '-' }}</td>
                                    <td class="px-table-cell-x py-table-cell-y whitespace-nowrap">
                                        <x-ui.badge :variant="match($sub->status) {
                                            'active' => 'success',
                                            'terminated' => 'danger',
                                            'probation' => 'warning',
                                            default => 'default',
                                        }">{{ ucfirst($sub->status) }}</x-ui.badge>
                                    </td>
                                    <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">{{ $sub->department?->type?->name ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        @endif
    </div>
</div>
