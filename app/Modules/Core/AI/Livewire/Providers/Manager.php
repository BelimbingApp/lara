<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\AI\Livewire\Providers;

use App\Base\AI\Services\ModelCatalogService;
use App\Modules\Core\AI\Livewire\Concerns\ManagesModels;
use App\Modules\Core\AI\Livewire\Concerns\ManagesProviderHelp;
use App\Modules\Core\AI\Livewire\Concerns\ManagesProviders;
use App\Modules\Core\AI\Livewire\Concerns\ManagesSync;
use App\Modules\Core\AI\Models\AiProvider;
use App\Modules\Core\AI\Models\AiProviderModel;
use Livewire\Attributes\On;
use Livewire\Component;

class Manager extends Component
{
    use ManagesModels;
    use ManagesProviderHelp;
    use ManagesProviders;
    use ManagesSync;

    public string $search = '';

    public ?int $expandedProviderId = null;

    /**
     * Re-render after wizard completes to show newly connected providers.
     */
    #[On('wizard-completed')]
    public function onWizardCompleted(): void
    {
        // Triggers re-render
    }

    public function toggleProvider(int $providerId): void
    {
        $this->expandedProviderId = $this->expandedProviderId === $providerId ? null : $providerId;
    }

    /**
     * Navigate to the provider catalog (dispatches to parent orchestrator).
     */
    public function openCatalog(): void
    {
        $this->dispatch('wizard-open-catalog');
    }

    /**
     * Format a cost value for display (2 decimal places).
     */
    public function formatCost(?string $cost): string
    {
        if ($cost === null || $cost === '') {
            return '—';
        }

        return '$'.number_format((float) $cost, 2);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $companyId = $this->getCompanyId();
        $providers = collect();
        $expandedModels = collect();

        if ($companyId !== null) {
            $query = AiProvider::query()
                ->forCompany($companyId)
                ->withCount('models');

            if ($this->search !== '') {
                $search = $this->search;
                $query->where(function ($q) use ($search): void {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('display_name', 'like', '%'.$search.'%');
                });
            }

            $providers = $query->orderBy('priority')->orderBy('display_name')->get();

            if ($this->expandedProviderId !== null) {
                $expandedModels = AiProviderModel::query()
                    ->where('ai_provider_id', $this->expandedProviderId)
                    ->orderBy('model_id')
                    ->get();
            }
        }

        $templates = collect(app(ModelCatalogService::class)->getProviders())
            ->map(fn ($t, $key) => ['value' => $key, 'label' => $t['display_name'] ?? $key])
            ->values()
            ->all();

        return view('livewire.ai.providers.manager', [
            'providers' => $providers,
            'expandedModels' => $expandedModels,
            'templateOptions' => $templates,
        ]);
    }

    private function getCompanyId(): ?int
    {
        $user = auth()->user();

        return $user?->employee?->company_id ? (int) $user->employee->company_id : null;
    }
}
