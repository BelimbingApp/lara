<?php

use App\Modules\Core\Company\Models\Company;
use App\Modules\Core\Company\Models\CompanyRelationship;
use App\Modules\Core\Company\Models\RelationshipType;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public Company $company;

    public bool $showCreateModal = false;

    public int $create_related_company_id = 0;

    public int $create_relationship_type_id = 0;

    public ?string $create_effective_from = null;

    public ?string $create_effective_to = null;

    public bool $showEditModal = false;

    public ?int $edit_relationship_id = null;

    public ?string $edit_effective_from = null;

    public ?string $edit_effective_to = null;

    public function mount(Company $company): void
    {
        $this->company = $company;
    }

    public function with(): array
    {
        $outgoing = CompanyRelationship::query()
            ->where('company_id', $this->company->id)
            ->with(['relatedCompany', 'type'])
            ->get()
            ->map(fn ($r) => (object) ['relationship' => $r, 'direction' => 'outgoing', 'other' => $r->relatedCompany]);

        $incoming = CompanyRelationship::query()
            ->where('related_company_id', $this->company->id)
            ->with(['company', 'type'])
            ->get()
            ->map(fn ($r) => (object) ['relationship' => $r, 'direction' => 'incoming', 'other' => $r->company]);

        return [
            'allRelationships' => $outgoing->merge($incoming),
            'availableCompanies' => Company::query()
                ->where('id', '!=', $this->company->id)
                ->orderBy('name')
                ->get(['id', 'name']),
            'relationshipTypes' => RelationshipType::query()
                ->active()
                ->orderBy('name')
                ->get(),
        ];
    }

    public function createRelationship(): void
    {
        if ($this->create_related_company_id === 0 || $this->create_relationship_type_id === 0) {
            return;
        }

        CompanyRelationship::query()->create([
            'company_id' => $this->company->id,
            'related_company_id' => $this->create_related_company_id,
            'relationship_type_id' => $this->create_relationship_type_id,
            'effective_from' => $this->create_effective_from,
            'effective_to' => $this->create_effective_to,
        ]);

        $this->showCreateModal = false;
        $this->reset(['create_related_company_id', 'create_relationship_type_id', 'create_effective_from', 'create_effective_to']);
        Session::flash('success', __('Relationship created.'));
    }

    public function editRelationship(int $relationshipId): void
    {
        $rel = CompanyRelationship::query()->findOrFail($relationshipId);
        $this->edit_relationship_id = $rel->id;
        $this->edit_effective_from = $rel->effective_from?->format('Y-m-d');
        $this->edit_effective_to = $rel->effective_to?->format('Y-m-d');
        $this->showEditModal = true;
    }

    public function updateRelationship(): void
    {
        if (! $this->edit_relationship_id) {
            return;
        }

        $rel = CompanyRelationship::query()->findOrFail($this->edit_relationship_id);
        $rel->effective_from = $this->edit_effective_from;
        $rel->effective_to = $this->edit_effective_to;
        $rel->save();

        $this->showEditModal = false;
        $this->reset(['edit_relationship_id', 'edit_effective_from', 'edit_effective_to']);
        Session::flash('success', __('Relationship updated.'));
    }

    public function deleteRelationship(int $relationshipId): void
    {
        CompanyRelationship::query()->findOrFail($relationshipId)->delete();
        Session::flash('success', __('Relationship deleted.'));
    }
}; ?>

<div>
    <x-slot name="title">{{ __('Relationships') }} — {{ $company->name }}</x-slot>

    <div class="space-y-section-gap">
        <x-ui.page-header :title="__('Relationships') . ' — ' . $company->name">
            <x-slot name="actions">
                <a href="{{ route('admin.companies.show', $company) }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl hover:bg-surface-subtle text-link transition-colors">
                    <x-icon name="heroicon-o-arrow-left" class="w-5 h-5" />
                    {{ __('Back to Company') }}
                </a>
                <x-ui.button variant="primary" wire:click="$set('showCreateModal', true)">
                    <x-icon name="heroicon-o-plus" class="w-4 h-4" />
                    {{ __('Add Relationship') }}
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
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Related Company') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Type') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Direction') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Effective From') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Effective To') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Active?') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-right text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface-card divide-y divide-border-default">
                        @forelse($allRelationships as $item)
                            <tr wire:key="rel-{{ $item->relationship->id }}-{{ $item->direction }}" class="hover:bg-surface-subtle/50 transition-colors">
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap">
                                    <a href="{{ route('admin.companies.show', $item->other) }}" wire:navigate class="text-sm font-medium text-link hover:underline">{{ $item->other->name }}</a>
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-ink">
                                    {{ $item->relationship->type->name }}
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap">
                                    <x-ui.badge :variant="$item->direction === 'outgoing' ? 'info' : 'default'">{{ ucfirst($item->direction) }}</x-ui.badge>
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted tabular-nums">
                                    {{ $item->relationship->effective_from?->format('Y-m-d') ?? '-' }}
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted tabular-nums">
                                    {{ $item->relationship->effective_to?->format('Y-m-d') ?? '-' }}
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap">
                                    <x-ui.badge :variant="$item->relationship->isActive() ? 'success' : 'danger'">
                                        {{ $item->relationship->isActive() ? __('Yes') : __('No') }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            wire:click="editRelationship({{ $item->relationship->id }})"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-sm rounded-lg hover:bg-surface-subtle text-link transition-colors"
                                        >
                                            <x-icon name="heroicon-o-pencil" class="w-4 h-4" />
                                            {{ __('Edit') }}
                                        </button>
                                        <button
                                            wire:click="deleteRelationship({{ $item->relationship->id }})"
                                            wire:confirm="{{ __('Are you sure you want to delete this relationship?') }}"
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
                                <td colspan="7" class="px-table-cell-x py-8 text-center text-sm text-muted">{{ __('No relationships found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>

    <x-ui.modal wire:model="showCreateModal" class="max-w-lg">
        <form wire:submit="createRelationship" class="space-y-6 p-6">
            <h3 class="text-xl font-medium tracking-tight text-ink">{{ __('Add Relationship') }}</h3>

            <div class="space-y-1">
                <label class="block text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Related Company') }}</label>
                <x-ui.select wire:model="create_related_company_id">
                    <option value="0">{{ __('— Select Company —') }}</option>
                    @foreach($availableCompanies as $availableCompany)
                        <option value="{{ $availableCompany->id }}">{{ $availableCompany->name }}</option>
                    @endforeach
                </x-ui.select>
            </div>

            <div class="space-y-1">
                <label class="block text-[11px] uppercase tracking-wider font-semibold text-muted">{{ __('Relationship Type') }}</label>
                <x-ui.select wire:model="create_relationship_type_id">
                    <option value="0">{{ __('— Select Type —') }}</option>
                    @foreach($relationshipTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </x-ui.select>
            </div>

            <x-ui.input
                wire:model="create_effective_from"
                label="{{ __('Effective From') }}"
                type="date"
            />

            <x-ui.input
                wire:model="create_effective_to"
                label="{{ __('Effective To') }}"
                type="date"
            />

            <div class="flex justify-end gap-2">
                <x-ui.button wire:click="$set('showCreateModal', false)" variant="ghost">
                    {{ __('Cancel') }}
                </x-ui.button>
                <x-ui.button type="submit" variant="primary">
                    {{ __('Create') }}
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    <x-ui.modal wire:model="showEditModal" class="max-w-lg">
        <form wire:submit="updateRelationship" class="space-y-6 p-6">
            <h3 class="text-xl font-medium tracking-tight text-ink">{{ __('Edit Relationship Dates') }}</h3>

            <x-ui.input
                wire:model="edit_effective_from"
                label="{{ __('Effective From') }}"
                type="date"
            />

            <x-ui.input
                wire:model="edit_effective_to"
                label="{{ __('Effective To') }}"
                type="date"
            />

            <div class="flex justify-end gap-2">
                <x-ui.button wire:click="$set('showEditModal', false)" variant="ghost">
                    {{ __('Cancel') }}
                </x-ui.button>
                <x-ui.button type="submit" variant="primary">
                    {{ __('Update') }}
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>
