<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\Company\Livewire\Companies;

use App\Modules\Core\Company\Models\Company;
use App\Modules\Core\Company\Models\CompanyRelationship;
use App\Modules\Core\Company\Models\RelationshipType;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use Livewire\WithPagination;

class Relationships extends Component
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

    public function render(): \Illuminate\Contracts\View\View
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

        return view('livewire.companies.relationships', [
            'allRelationships' => $outgoing->merge($incoming),
            'availableCompanies' => Company::query()
                ->where('id', '!=', $this->company->id)
                ->orderBy('name')
                ->get(['id', 'name']),
            'relationshipTypes' => RelationshipType::query()
                ->active()
                ->orderBy('name')
                ->get(),
        ]);
    }
}
