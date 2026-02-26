<?php

use App\Modules\Core\Employee\Models\EmployeeType;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public EmployeeType $employeeType;

    public string $label = '';

    public function mount(EmployeeType $employeeType): void
    {
        $this->employeeType = $employeeType;
        if ($employeeType->is_system) {
            abort(403, __('System employee types cannot be edited.'));
        }
        $this->label = $employeeType->label;
    }

    public function save(): void
    {
        $this->validate([
            'label' => ['required', 'string', 'max:255'],
        ]);

        $this->employeeType->update(['label' => $this->label]);

        session()->flash('success', __('Employee type updated.'));
        $this->redirect(route('admin.employee-types.index'), navigate: true);
    }
}; ?>

<div>
    <x-slot name="title">{{ __('Edit Employee Type') }}</x-slot>

    <div class="space-y-section-gap">
        <x-ui.page-header :title="__('Edit Employee Type')" :subtitle="$employeeType->code">
            <x-slot name="actions">
                <x-ui.button variant="ghost" as="a" href="{{ route('admin.employee-types.index') }}" wire:navigate>
                    <x-icon name="heroicon-o-arrow-left" class="w-5 h-5" />
                    {{ __('Back') }}
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>

        <x-ui.card>
            <form wire:submit="save" class="space-y-4 max-w-lg">
                <div>
                    <label class="block text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Code') }}</label>
                    <p class="text-sm text-ink font-mono mt-0.5">{{ $employeeType->code }}</p>
                    <p class="text-xs text-muted mt-1">{{ __('Code cannot be changed.') }}</p>
                </div>

                <x-ui.input
                    wire:model="label"
                    label="{{ __('Label') }}"
                    required
                    placeholder="{{ __('e.g. Consultant') }}"
                    :error="$errors->first('label')"
                />

                <div class="pt-2">
                    <x-ui.button type="submit" variant="primary">
                        {{ __('Save') }}
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>
