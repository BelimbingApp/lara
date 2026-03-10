<?php

use App\Modules\Core\Employee\Models\EmployeeType;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public string $code = '';

    public string $label = '';

    public function createType(): void
    {
        $this->validate([
            'code' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('employee_types', 'code')->whereNull('company_id'),
            ],
            'label' => ['required', 'string', 'max:255'],
        ]);

        EmployeeType::query()->create([
            'code' => $this->code,
            'label' => $this->label,
            'is_system' => false,
        ]);

        session()->flash('success', __('Employee type created.'));
        $this->redirect(route('admin.employee-types.index'), navigate: true);
    }
}; ?>

<div>
    <x-slot name="title">{{ __('Add Employee Type') }}</x-slot>

    <div class="space-y-section-gap">
        <x-ui.page-header :title="__('Add Employee Type')" :subtitle="__('Create a custom employee type')">
            <x-slot name="actions">
                <x-ui.button variant="ghost" as="a" href="{{ route('admin.employee-types.index') }}" wire:navigate>
                    <x-icon name="heroicon-o-arrow-left" class="w-5 h-5" />
                    {{ __('Back') }}
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>

        <x-ui.card>
            <form wire:submit="createType" class="space-y-4 max-w-lg">
                <x-ui.input
                    wire:model="code"
                    label="{{ __('Code') }}"
                    required
                    placeholder="{{ __('e.g. consultant') }}"
                    :error="$errors->first('code')"
                />
                <p class="text-xs text-muted -mt-2">{{ __('Lowercase letters, numbers, and underscores. Must start with a letter.') }}</p>

                <x-ui.input
                    wire:model="label"
                    label="{{ __('Label') }}"
                    required
                    placeholder="{{ __('e.g. Consultant') }}"
                    :error="$errors->first('label')"
                />

                <div class="pt-2">
                    <x-ui.button type="submit" variant="primary">
                        {{ __('Create') }}
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>
