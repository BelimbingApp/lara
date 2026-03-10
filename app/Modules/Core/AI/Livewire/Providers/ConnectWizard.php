<?php

// SPDX-License-Identifier: AGPL-3.0-only
// (c) Ng Kiat Siong <kiatsiong.ng@gmail.com>

namespace App\Modules\Core\AI\Livewire\Providers;

use App\Modules\Core\AI\Models\AiProvider;
use App\Modules\Core\AI\Services\ModelDiscoveryService;
use App\Modules\Core\AI\Services\ProviderAuthFlowService;
use Livewire\Component;

class ConnectWizard extends Component
{
    /** @var list<array{key: string, display_name: string, base_url: string, api_key: string, api_key_url: string|null, auth_type: string}> */
    public array $connectForms = [];

    /** @var array<int, array{status: string, user_code: string|null, verification_uri: string|null, error: string|null}> */
    public array $deviceFlows = [];

    /** @var array<int, string> */
    public array $connectErrors = [];

    /**
     * Initialise connect forms from parent-supplied prop and auto-start device flows.
     *
     * @param  array  $initialForms  Connect form entries built by the orchestrator
     */
    public function mount(array $initialForms = []): void
    {
        $this->connectForms = $initialForms;

        // Auto-start device flows (e.g. GitHub Copilot)
        foreach ($this->connectForms as $index => $form) {
            if (($form['auth_type'] ?? 'api_key') === 'device_flow') {
                $this->startDeviceFlow($index);
            }
        }
    }

    /**
     * Start an interactive auth flow for a connect form entry.
     *
     * Delegates to ProviderAuthFlowService which handles provider-specific
     * logic (e.g., GitHub device flow). Sensitive data stays in server cache.
     *
     * @param  int  $index  Connect form index
     */
    public function startDeviceFlow(int $index): void
    {
        $companyId = $this->getCompanyId();

        if ($companyId === null) {
            return;
        }

        $service = app(ProviderAuthFlowService::class);
        $this->deviceFlows[$index] = $service->startFlow(
            $this->connectForms[$index]['key'],
            $companyId,
            $index,
        );
    }

    /**
     * Poll an active auth flow for completion (called via wire:poll).
     *
     * On success, updates connectForms with the obtained credentials.
     *
     * @param  int  $index  Connect form index
     */
    public function pollDeviceFlow(int $index): void
    {
        $companyId = $this->getCompanyId();

        if ($companyId === null) {
            return;
        }

        $service = app(ProviderAuthFlowService::class);
        $result = $service->pollFlow(
            $this->connectForms[$index]['key'],
            $companyId,
            $index,
        );

        if ($result['status'] === 'pending') {
            return;
        }

        if ($result['status'] === 'success') {
            $this->connectForms[$index]['api_key'] = $result['api_key'] ?? '';
            $this->connectForms[$index]['base_url'] = $result['base_url'] ?? $this->connectForms[$index]['base_url'];
        }

        $this->deviceFlows[$index]['status'] = $result['status'];
        $this->deviceFlows[$index]['error'] = $result['error'] ?? null;
    }

    /**
     * Remove a single provider from the connect forms.
     *
     * If no forms remain, dispatches back to catalog. Cleans up any active
     * device flow for the removed form and re-indexes arrays.
     *
     * @param  int  $index  Connect form index to remove
     */
    public function removeConnectForm(int $index): void
    {
        if (! isset($this->connectForms[$index])) {
            return;
        }

        $companyId = $this->getCompanyId();

        // Clean up device flow cache if active
        if ($companyId !== null && isset($this->deviceFlows[$index])) {
            app(ProviderAuthFlowService::class)->cleanupFlows($companyId, [$index]);
        }

        // Remove form and re-index
        array_splice($this->connectForms, $index, 1);
        unset($this->deviceFlows[$index], $this->connectErrors[$index]);
        $this->deviceFlows = array_values($this->deviceFlows);
        $this->connectErrors = array_values($this->connectErrors);

        // If no forms left, go back to catalog
        if ($this->connectForms === []) {
            $this->dispatch('wizard-back-to-catalog');
        }
    }

    /**
     * Create providers and import models for all connected forms.
     *
     * Processes each provider independently — failures on one provider do not
     * block others. Errors are captured per-card in $connectErrors and shown
     * inline. Successfully connected providers are removed from the form list.
     * The wizard only completes when all providers succeed.
     */
    public function connectAll(): void
    {
        $companyId = $this->getCompanyId();

        if ($companyId === null) {
            return;
        }

        $rules = [];

        foreach ($this->connectForms as $index => $form) {
            $authType = $form['auth_type'] ?? 'api_key';

            if ($form['key'] === 'cloudflare-ai-gateway') {
                $rules["connectForms.{$index}.cloudflare_account_id"] = ['required', 'string', 'max:255'];
                $rules["connectForms.{$index}.cloudflare_gateway_id"] = ['required', 'string', 'max:255'];
            } else {
                $rules["connectForms.{$index}.base_url"] = ['required', 'string', 'max:2048'];
            }

            if (in_array($authType, ['api_key', 'custom', 'device_flow'], true)) {
                $rules["connectForms.{$index}.api_key"] = ['required', 'string', 'max:2048'];
            } else {
                $rules["connectForms.{$index}.api_key"] = ['nullable', 'string', 'max:2048'];
            }
        }

        $this->validate($rules, [
            'connectForms.*.base_url.required' => __('Base URL is required.'),
            'connectForms.*.api_key.required' => __('API key is required.'),
            'connectForms.*.cloudflare_account_id.required' => __('Account ID is required.'),
            'connectForms.*.cloudflare_gateway_id.required' => __('Gateway ID is required.'),
        ]);

        $this->connectErrors = [];
        $discovery = app(ModelDiscoveryService::class);
        $succeeded = [];

        foreach ($this->connectForms as $index => $form) {
            try {
                $this->connectProvider($companyId, $form, $discovery);
                $succeeded[] = $index;
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $this->connectErrors[$index] = __('Could not connect to :url — is the server running?', [
                    'url' => $form['base_url'],
                ]);

                \Illuminate\Support\Facades\Log::warning('Provider connect failed', [
                    'provider' => $form['key'],
                    'base_url' => $form['base_url'],
                    'error' => $e->getMessage(),
                ]);
            } catch (\Exception $e) {
                $this->connectErrors[$index] = __('Failed to connect: :message', [
                    'message' => $e->getMessage(),
                ]);

                \Illuminate\Support\Facades\Log::warning('Provider connect failed', [
                    'provider' => $form['key'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->removeSucceededForms($succeeded);

        if ($this->connectForms === []) {
            $this->cleanupAuthFlows();
            $this->deviceFlows = [];
            $this->connectErrors = [];
            $this->dispatch('wizard-completed');
        }
    }

    /**
     * Go back from connect (step 2) to catalog (step 1).
     */
    public function backToCatalog(): void
    {
        $this->cleanupAuthFlows();
        $this->deviceFlows = [];
        $this->connectErrors = [];
        $this->dispatch('wizard-back-to-catalog');
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $hasIncompleteDeviceFlow = false;

        foreach ($this->connectForms as $i => $f) {
            if (($f['auth_type'] ?? 'api_key') === 'device_flow') {
                $flowStatus = $this->deviceFlows[$i]['status'] ?? 'idle';
                if ($flowStatus !== 'success') {
                    $hasIncompleteDeviceFlow = true;
                }
            }
        }

        return view('livewire.ai.providers.connect-wizard', [
            'hasIncompleteDeviceFlow' => $hasIncompleteDeviceFlow,
        ]);
    }

    /**
     * Connect or re-sync a single provider for the given company.
     *
     * If the provider already exists, triggers model re-import when none are
     * present. Otherwise creates the provider, assigns priority, and runs
     * initial model discovery.
     *
     * @param  array{key: string, display_name: string, base_url: string, api_key: string, auth_type: string, cloudflare_account_id?: string, cloudflare_gateway_id?: string}  $form
     */
    private function connectProvider(int $companyId, array $form, ModelDiscoveryService $discovery): void
    {
        $key = $form['key'];

        $existing = AiProvider::query()
            ->forCompany($companyId)
            ->where('name', $key)
            ->first();

        if ($existing) {
            if (! $existing->models()->exists()) {
                $discovery->syncModels($existing);
            }

            return;
        }

        $provider = AiProvider::query()->create([
            'company_id' => $companyId,
            'name' => $key,
            'display_name' => $form['display_name'],
            'base_url' => $this->resolveBaseUrl($form),
            'api_key' => $form['api_key'] !== '' ? $form['api_key'] : 'not-required',
            'is_active' => true,
            'created_by' => auth()->user()->employee?->id,
        ]);

        $provider->assignNextPriority();
        $discovery->syncModels($provider);
    }

    /**
     * Build the provider base URL, handling Cloudflare's Account+Gateway ID pattern.
     *
     * @param  array{key: string, base_url: string, cloudflare_account_id?: string, cloudflare_gateway_id?: string}  $form
     */
    private function resolveBaseUrl(array $form): string
    {
        if ($form['key'] !== 'cloudflare-ai-gateway') {
            return $form['base_url'];
        }

        $accountId = trim($form['cloudflare_account_id'] ?? '');
        $gatewayId = trim($form['cloudflare_gateway_id'] ?? '');

        return "https://gateway.ai.cloudflare.com/v1/{$accountId}/{$gatewayId}/openai";
    }

    /**
     * Remove succeeded forms and re-index errors in a single forward pass.
     *
     * @param  list<int>  $succeeded  Indices of forms that connected successfully
     */
    private function removeSucceededForms(array $succeeded): void
    {
        $remaining = [];
        $reindexedErrors = [];

        foreach ($this->connectForms as $index => $form) {
            if (in_array($index, $succeeded, true)) {
                continue;
            }

            if (isset($this->connectErrors[$index])) {
                $reindexedErrors[count($remaining)] = $this->connectErrors[$index];
            }

            $remaining[] = $form;
        }

        $this->connectForms = $remaining;
        $this->connectErrors = $reindexedErrors;
    }

    private function getCompanyId(): ?int
    {
        $user = auth()->user();

        return $user?->employee?->company_id ? (int) $user->employee->company_id : null;
    }

    /**
     * Clean up all cached auth flow data for this company.
     */
    private function cleanupAuthFlows(): void
    {
        $companyId = $this->getCompanyId();

        if ($companyId === null || $this->deviceFlows === []) {
            return;
        }

        $service = app(ProviderAuthFlowService::class);
        $service->cleanupFlows($companyId, array_keys($this->deviceFlows));
    }
}
