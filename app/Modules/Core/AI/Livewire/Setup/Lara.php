<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\AI\Livewire\Setup;

use App\Modules\Core\AI\Models\AiProvider;
use App\Modules\Core\AI\Models\AiProviderModel;
use App\Modules\Core\AI\Services\ConfigResolver;
use App\Modules\Core\Company\Models\Company;
use App\Modules\Core\Employee\Models\Employee;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class Lara extends Component
{
    public ?int $selectedProviderId = null;

    public ?string $selectedModelId = null;

    public function mount(): void
    {
        $laraExists = Employee::query()->where('id', Employee::LARA_ID)->exists();
        $licenseeExists = Company::query()->where('id', Company::LICENSEE_ID)->exists();

        if ($laraExists && $licenseeExists) {
            $resolver = app(ConfigResolver::class);
            $configs = $resolver->resolve(Employee::LARA_ID);
            $activated = count($configs) > 0;

            if (! $activated) {
                $default = $resolver->resolveDefault(Company::LICENSEE_ID);
                $activated = $default !== null;
            }

            if ($activated) {
                $this->redirect(route('admin.ai.playground'), navigate: true);
            }
        }
    }

    /**
     * Provision the Lara employee record.
     *
     * Delegates to Employee::provisionLara() — the single source of truth.
     */
    public function provisionLara(): void
    {
        if (Employee::provisionLara()) {
            Session::flash('success', __('Lara has been provisioned.'));
        }
    }

    /**
     * Activate Lara by writing workspace config with selected provider and model.
     */
    public function activateLara(): void
    {
        $this->validate([
            'selectedProviderId' => ['required', 'integer', 'exists:ai_providers,id'],
            'selectedModelId' => ['required', 'string'],
        ]);

        $provider = AiProvider::query()->findOrFail($this->selectedProviderId);

        $config = [
            'llm' => [
                'models' => [
                    [
                        'provider' => $provider->name,
                        'model' => $this->selectedModelId,
                    ],
                ],
            ],
        ];

        $resolver = app(ConfigResolver::class);
        $resolver->writeWorkspaceConfig(Employee::LARA_ID, $config);

        Session::flash('success', __('Lara has been activated.'));
        $this->redirect(route('admin.ai.playground'), navigate: true);
    }

    /**
     * Provide data to the Blade template.
     */
    public function render(): \Illuminate\Contracts\View\View
    {
        $laraExists = Employee::query()->where('id', Employee::LARA_ID)->exists();
        $licenseeExists = Company::query()->where('id', Company::LICENSEE_ID)->exists();

        $laraActivated = false;
        if ($laraExists && $licenseeExists) {
            $resolver = app(ConfigResolver::class);
            $configs = $resolver->resolve(Employee::LARA_ID);
            if (count($configs) > 0) {
                $laraActivated = true;
            } else {
                $default = $resolver->resolveDefault(Company::LICENSEE_ID);
                $laraActivated = $default !== null;
            }
        }

        $providers = collect();
        $models = collect();

        if ($licenseeExists) {
            $providers = AiProvider::query()
                ->forCompany(Company::LICENSEE_ID)
                ->active()
                ->orderBy('display_name')
                ->get(['id', 'display_name', 'name']);
        }

        if ($this->selectedProviderId) {
            $models = AiProviderModel::query()
                ->where('ai_provider_id', $this->selectedProviderId)
                ->active()
                ->orderBy('model_id')
                ->get(['id', 'model_id']);
        }

        return view('livewire.admin.setup.lara', [
            'laraExists' => $laraExists,
            'licenseeExists' => $licenseeExists,
            'laraActivated' => $laraActivated,
            'providers' => $providers,
            'models' => $models,
        ]);
    }
}
