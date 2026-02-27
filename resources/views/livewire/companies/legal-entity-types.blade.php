<?php

use App\Modules\Core\Company\Models\LegalEntityType;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public bool $showCreateModal = false;

    public string $create_code = '';

    public string $create_name = '';

    public ?string $create_description = null;

    public bool $create_is_active = true;

    public function with(): array
    {
        return [
            'types' => LegalEntityType::query()
                ->orderBy('name')
                ->paginate(15),
        ];
    }

    public function createType(): void
    {
        $validated = $this->validate([
            'create_code' => ['required', 'string', 'max:255', Rule::unique('company_legal_entity_types', 'code')],
            'create_name' => ['required', 'string', 'max:255'],
            'create_description' => ['nullable', 'string'],
            'create_is_active' => ['boolean'],
        ]);

        LegalEntityType::query()->create([
            'code' => $validated['create_code'],
            'name' => $validated['create_name'],
            'description' => $validated['create_description'],
            'is_active' => $validated['create_is_active'],
        ]);

        $this->showCreateModal = false;
        $this->reset(['create_code', 'create_name', 'create_description', 'create_is_active']);
        $this->create_is_active = true;
        Session::flash('success', __('Legal entity type created.'));
    }

    public function saveField(int $typeId, string $field, mixed $value): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];

        if (! isset($rules[$field])) {
            return;
        }

        $validated = validator([$field => $value], [$field => $rules[$field]])->validate();

        $type = LegalEntityType::query()->findOrFail($typeId);
        $type->$field = $validated[$field];
        $type->save();
    }

    public function toggleActive(int $typeId): void
    {
        $type = LegalEntityType::query()->findOrFail($typeId);
        $type->is_active = ! $type->is_active;
        $type->save();
    }

    public function deleteType(int $typeId): void
    {
        $type = LegalEntityType::query()->withCount('companies')->findOrFail($typeId);

        if ($type->companies_count > 0) {
            Session::flash('error', __('Cannot delete a legal entity type that is in use by companies.'));

            return;
        }

        $type->delete();
        Session::flash('success', __('Legal entity type deleted.'));
    }
}; ?>

<div>
    <x-slot name="title">{{ __('Legal Entity Types') }}</x-slot>

    <div class="space-y-section-gap">
        <x-ui.page-header :title="__('Legal Entity Types')" :subtitle="__('Manage company legal structure categories')">
            <x-slot name="actions">
                <a href="{{ route('admin.companies.index') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl text-accent hover:bg-surface-subtle transition-colors">
                    <x-icon name="heroicon-o-arrow-left" class="w-5 h-5" />
                    {{ __('Back to Companies') }}
                </a>
                <x-ui.button variant="primary" wire:click="$set('showCreateModal', true)">
                    <x-icon name="heroicon-o-plus" class="w-4 h-4" />
                    {{ __('Add Type') }}
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
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Code') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Name') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Description') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Status') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-right text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface-card divide-y divide-border-default">
                        @forelse($types as $type)
                            <tr wire:key="type-{{ $type->id }}" class="hover:bg-surface-subtle/50 transition-colors">
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted font-mono">{{ $type->code }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-ink"
                                    x-data="{ editing: false, val: '{{ addslashes($type->name) }}' }"
                                >
                                    <div x-show="!editing" @click="editing = true; $nextTick(() => $refs.input.select())" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                                        <span x-text="val"></span>
                                        <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity" />
                                    </div>
                                    <input
                                        x-show="editing"
                                        x-ref="input"
                                        x-model="val"
                                        @keydown.enter="editing = false; $wire.saveField({{ $type->id }}, 'name', val)"
                                        @keydown.escape="editing = false; val = '{{ addslashes($type->name) }}'"
                                        @blur="editing = false; $wire.saveField({{ $type->id }}, 'name', val)"
                                        type="text"
                                        class="w-full px-1 -mx-1 py-0.5 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                                    />
                                </td>
                                <td class="px-table-cell-x py-table-cell-y text-sm text-muted max-w-xs"
                                    x-data="{ editing: false, val: '{{ addslashes($type->description ?? '') }}' }"
                                >
                                    <div x-show="!editing" @click="editing = true; $nextTick(() => $refs.input.select())" class="group flex items-center gap-1.5 cursor-pointer rounded px-1 -mx-1 py-0.5 hover:bg-surface-subtle">
                                        <span class="truncate" x-text="val || '-'"></span>
                                        <x-icon name="heroicon-o-pencil" class="w-3.5 h-3.5 text-muted opacity-0 group-hover:opacity-100 transition-opacity shrink-0" />
                                    </div>
                                    <input
                                        x-show="editing"
                                        x-ref="input"
                                        x-model="val"
                                        @keydown.enter="editing = false; $wire.saveField({{ $type->id }}, 'description', val)"
                                        @keydown.escape="editing = false; val = '{{ addslashes($type->description ?? '') }}'"
                                        @blur="editing = false; $wire.saveField({{ $type->id }}, 'description', val)"
                                        type="text"
                                        class="w-full px-1 -mx-1 py-0.5 text-sm border border-accent rounded bg-surface-card text-ink focus:outline-none focus:ring-1 focus:ring-accent"
                                    />
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap">
                                    <button
                                        wire:click="toggleActive({{ $type->id }})"
                                        class="cursor-pointer"
                                        title="{{ __('Toggle active status') }}"
                                    >
                                        @if($type->is_active)
                                            <x-ui.badge variant="success">{{ __('Active') }}</x-ui.badge>
                                        @else
                                            <x-ui.badge variant="default">{{ __('Inactive') }}</x-ui.badge>
                                        @endif
                                    </button>
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-right">
                                    <x-ui.button
                                        variant="danger-ghost"
                                        size="sm"
                                        wire:click="deleteType({{ $type->id }})"
                                        wire:confirm="{{ __('Are you sure you want to delete this legal entity type?') }}"
                                    >
                                        <x-icon name="heroicon-o-trash" class="w-4 h-4" />
                                        {{ __('Delete') }}
                                    </x-ui.button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-table-cell-x py-8 text-center text-sm text-muted">{{ __('No legal entity types found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-2">
                {{ $types->links() }}
            </div>
        </x-ui.card>
    </div>

    <x-ui.modal wire:model="showCreateModal" class="max-w-lg">
        <form wire:submit="createType" class="p-6 space-y-6">
            <h2 class="text-lg font-medium tracking-tight text-ink">{{ __('Add Legal Entity Type') }}</h2>

            <div class="space-y-4">
                <x-ui.input
                    wire:model="create_code"
                    label="{{ __('Code') }}"
                    type="text"
                    required
                    placeholder="{{ __('e.g. private_limited') }}"
                    :error="$errors->first('create_code')"
                />

                <x-ui.input
                    wire:model="create_name"
                    label="{{ __('Name') }}"
                    type="text"
                    required
                    placeholder="{{ __('e.g. Private Limited Company') }}"
                    :error="$errors->first('create_name')"
                />

                <x-ui.textarea
                    wire:model="create_description"
                    label="{{ __('Description') }}"
                    rows="3"
                    placeholder="{{ __('Brief description of this entity type') }}"
                    :error="$errors->first('create_description')"
                />

                <x-ui.checkbox wire:model="create_is_active" label="{{ __('Active') }}" />
            </div>

            <div class="flex items-center gap-4">
                <x-ui.button type="submit" variant="primary">
                    {{ __('Create') }}
                </x-ui.button>
                <button type="button" wire:click="$set('showCreateModal', false)" class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl text-accent hover:bg-surface-subtle transition-colors">
                    {{ __('Cancel') }}
                </button>
            </div>
        </form>
    </x-ui.modal>
</div>
