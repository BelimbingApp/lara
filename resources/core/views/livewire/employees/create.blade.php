<div>
    <x-slot name="title">{{ __('Add Employee') }}</x-slot>

    <div class="space-y-section-gap">
        <x-ui.page-header :title="__('Add Employee')" :subtitle="__('Create a new employment record')">
            <x-slot name="actions">
                <a href="{{ route('admin.employees.index') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl text-accent hover:bg-surface-subtle transition-colors">
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
                    <x-ui.select wire:model.live="employee_type" label="{{ __('Employee Type') }}" :error="$errors->first('employee_type')">
                        <optgroup label="{{ __('Human') }}">
                            @foreach($employeeTypes->where('code', '!=', 'digital_worker') as $type)
                                <option value="{{ $type->code }}">{{ $type->label }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="{{ __('Digital Worker') }}">
                            @foreach($employeeTypes->where('code', 'digital_worker') as $type)
                                <option value="{{ $type->code }}">{{ $type->label }}</option>
                            @endforeach
                        </optgroup>
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

                @if($employee_type === 'digital_worker')
                <x-ui.textarea
                    wire:model="job_description"
                    label="{{ __('Job Description') }}"
                    rows="3"
                    placeholder="{{ __('Short role label, e.g. Customer support Digital Worker') }}"
                    :error="$errors->first('job_description')"
                />
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui.select wire:model="supervisor_id" label="{{ __('Supervisor') }}" :error="$errors->first('supervisor_id')">
                        <option value="">{{ $employee_type === 'digital_worker' ? __('Select supervisor (required)') : __('None') }}</option>
                        @foreach($supervisors as $supervisor)
                            <option value="{{ $supervisor->id }}">{{ $supervisor->full_name }}</option>
                        @endforeach
                    </x-ui.select>

                    @if($employee_type !== 'digital_worker')
                    <x-ui.select wire:model="user_id" label="{{ __('User Account') }}" :error="$errors->first('user_id')">
                        <option value="">{{ __('None') }}</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </x-ui.select>
                    @endif
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
                    <a href="{{ route('admin.employees.index') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl text-accent hover:bg-surface-subtle transition-colors">
                        {{ __('Cancel') }}
                    </a>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>
