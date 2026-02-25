<?php

use App\Modules\Core\Company\Models\Company;
use App\Modules\Core\Company\Models\Department;
use App\Modules\Core\Employee\Models\Employee;
use App\Modules\Core\User\Models\User;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public ?int $company_id = null;

    public ?int $department_id = null;

    public ?int $supervisor_id = null;

    public string $employee_number = '';

    public string $full_name = '';

    public ?string $short_name = null;

    public ?string $designation = null;

    public string $employee_type = 'full_time';

    public string $status = 'active';

    public ?string $email = null;

    public ?string $mobile_number = null;

    public ?string $employment_start = null;

    public ?string $employment_end = null;

    public ?int $user_id = null;

    public string $metadata_json = '';

    public function with(): array
    {
        return [
            'companies' => Company::query()
                ->orderBy('name')
                ->get(['id', 'name']),
            'departments' => Department::query()
                ->with('type')
                ->orderBy('department_type_id')
                ->get(['id', 'company_id', 'department_type_id']),
            'supervisors' => Employee::query()
                ->orderBy('full_name')
                ->get(['id', 'full_name', 'company_id']),
            'users' => User::query()
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }

    public function store(): void
    {
        $validated = $this->validate($this->rules());

        $validated['metadata'] = $this->decodeJsonField($validated['metadata_json']);

        unset($validated['metadata_json']);

        Employee::query()->create($validated);

        Session::flash('success', __('Employee created successfully.'));

        $this->redirect(route('admin.employees.index'), navigate: true);
    }

    protected function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', Rule::exists(Company::class, 'id')],
            'department_id' => ['nullable', 'integer', 'exists:company_departments,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'supervisor_id' => ['nullable', 'integer', Rule::exists(Employee::class, 'id')],
            'employee_number' => ['required', 'string', 'max:255', Rule::unique('employees')->where('company_id', $this->company_id)],
            'full_name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'employee_type' => ['required', 'in:full_time,part_time,contractor,intern'],
            'email' => ['nullable', 'email', 'max:255'],
            'mobile_number' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:pending,probation,active,inactive,terminated'],
            'employment_start' => ['nullable', 'date'],
            'employment_end' => ['nullable', 'date'],
            'metadata_json' => ['nullable', 'json'],
        ];
    }

    protected function decodeJsonField(?string $value): ?array
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }
}; ?>

<div>
    <x-slot name="title">{{ __('Add Employee') }}</x-slot>

    <div class="space-y-section-gap">
        <x-ui.page-header :title="__('Add Employee')" :subtitle="__('Create a new employment record')">
            <x-slot name="actions">
                <a href="{{ route('admin.employees.index') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl hover:bg-surface-subtle text-link transition-colors">
                    <x-icon name="heroicon-o-arrow-left" class="w-5 h-5" />
                    {{ __('Back') }}
                </a>
            </x-slot>
        </x-ui.page-header>

        <x-ui.card>
            <form wire:submit="store" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui.select wire:model="company_id" label="{{ __('Company') }}" :error="$errors->first('company_id')">
                        <option value="">{{ __('Select company...') }}</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </x-ui.select>

                    <x-ui.select wire:model="department_id" label="{{ __('Department') }}" :error="$errors->first('department_id')">
                        <option value="">{{ __('None') }}</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->type->name }}</option>
                        @endforeach
                    </x-ui.select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui.input
                        wire:model="employee_number"
                        label="{{ __('Employee Number') }}"
                        type="text"
                        required
                        placeholder="{{ __('Employee ID or number') }}"
                        :error="$errors->first('employee_number')"
                    />

                    <x-ui.input
                        wire:model="full_name"
                        label="{{ __('Full Name') }}"
                        type="text"
                        required
                        placeholder="{{ __('Full legal name') }}"
                        :error="$errors->first('full_name')"
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui.input
                        wire:model="short_name"
                        label="{{ __('Short Name') }}"
                        type="text"
                        placeholder="{{ __('Preferred or display name') }}"
                        :error="$errors->first('short_name')"
                    />

                    <x-ui.input
                        wire:model="designation"
                        label="{{ __('Designation') }}"
                        type="text"
                        placeholder="{{ __('Job title or designation') }}"
                        :error="$errors->first('designation')"
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui.select wire:model="employee_type" label="{{ __('Employee Type') }}" :error="$errors->first('employee_type')">
                        <option value="full_time">{{ __('Full Time') }}</option>
                        <option value="part_time">{{ __('Part Time') }}</option>
                        <option value="contractor">{{ __('Contractor') }}</option>
                        <option value="intern">{{ __('Intern') }}</option>
                    </x-ui.select>

                    <x-ui.select wire:model="status" label="{{ __('Status') }}" :error="$errors->first('status')">
                        <option value="pending">{{ __('Pending') }}</option>
                        <option value="probation">{{ __('Probation') }}</option>
                        <option value="active">{{ __('Active') }}</option>
                        <option value="inactive">{{ __('Inactive') }}</option>
                        <option value="terminated">{{ __('Terminated') }}</option>
                    </x-ui.select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui.input
                        wire:model="email"
                        label="{{ __('Email') }}"
                        type="email"
                        placeholder="{{ __('Work email address') }}"
                        :error="$errors->first('email')"
                    />

                    <x-ui.input
                        wire:model="mobile_number"
                        label="{{ __('Mobile Number') }}"
                        type="text"
                        placeholder="{{ __('Contact number') }}"
                        :error="$errors->first('mobile_number')"
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui.input
                        wire:model="employment_start"
                        label="{{ __('Employment Start') }}"
                        type="date"
                        :error="$errors->first('employment_start')"
                    />

                    <x-ui.input
                        wire:model="employment_end"
                        label="{{ __('Employment End') }}"
                        type="date"
                        :error="$errors->first('employment_end')"
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui.select wire:model="supervisor_id" label="{{ __('Supervisor') }}" :error="$errors->first('supervisor_id')">
                        <option value="">{{ __('None') }}</option>
                        @foreach($supervisors as $supervisor)
                            <option value="{{ $supervisor->id }}">{{ $supervisor->full_name }}</option>
                        @endforeach
                    </x-ui.select>

                    <x-ui.select wire:model="user_id" label="{{ __('User Account') }}" :error="$errors->first('user_id')">
                        <option value="">{{ __('None') }}</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </x-ui.select>
                </div>

                <x-ui.textarea
                    wire:model="metadata_json"
                    label="{{ __('Metadata (JSON)') }}"
                    rows="6"
                    placeholder="{{ __('{\"notes\":\"Additional employee information\"}') }}"
                    :error="$errors->first('metadata_json')"
                />

                <div class="flex items-center gap-4">
                    <x-ui.button type="submit" variant="primary">
                        {{ __('Add Employee') }}
                    </x-ui.button>
                    <a href="{{ route('admin.employees.index') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl hover:bg-surface-subtle text-link transition-colors">
                        {{ __('Cancel') }}
                    </a>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>
