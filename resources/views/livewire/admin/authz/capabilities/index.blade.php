<?php

use App\Base\Authz\Capability\CapabilityKey;
use Livewire\Volt\Component;

new class extends Component
{
    public string $search = '';
    public string $filterDomain = '';

    /**
     * Build a capability-to-module mapping by scanning authz config files.
     *
     * @return array<string, string>
     */
    private function buildCapabilityModuleMap(): array
    {
        $map = [];
        $patterns = [
            app_path('Base/*/Config/authz.php'),
            app_path('Modules/*/*/Config/authz.php'),
        ];

        foreach ($patterns as $pattern) {
            foreach (glob($pattern) ?: [] as $file) {
                $moduleConfig = require $file;
                $moduleName = $this->extractModuleName($file);

                foreach ($moduleConfig['capabilities'] ?? [] as $capability) {
                    $map[strtolower($capability)] = $moduleName;
                }
            }
        }

        return $map;
    }

    /**
     * Extract a human-readable module name from a config file path.
     *
     * @param  string  $filePath  Absolute path to Config/authz.php
     */
    private function extractModuleName(string $filePath): string
    {
        // app/Base/Authz/Config/authz.php → Base / Authz
        if (preg_match('#app/Base/([^/]+)/Config/authz\.php$#', $filePath, $m)) {
            return 'Base / '.$m[1];
        }

        // app/Modules/Core/User/Config/authz.php → Core / User
        if (preg_match('#app/Modules/([^/]+)/([^/]+)/Config/authz\.php$#', $filePath, $m)) {
            return $m[1].' / '.$m[2];
        }

        return 'Unknown';
    }

    public function with(): array
    {
        $moduleMap = $this->buildCapabilityModuleMap();

        $capabilities = collect($moduleMap)
            ->map(function (string $module, string $key) {
                $parts = CapabilityKey::parse($key);

                return (object) [
                    'key' => $key,
                    'domain' => $parts['domain'],
                    'resource' => $parts['resource'],
                    'action' => $parts['action'],
                    'module' => $module,
                ];
            })
            ->when($this->search, function ($collection, $search) {
                $search = strtolower($search);

                return $collection->filter(fn ($cap) => str_contains($cap->key, $search)
                    || str_contains(strtolower($cap->module), $search));
            })
            ->when($this->filterDomain, function ($collection, $domain) {
                return $collection->filter(fn ($cap) => $cap->domain === $domain);
            })
            ->sortBy('key')
            ->values();

        $domains = collect($moduleMap)
            ->keys()
            ->map(fn (string $key) => CapabilityKey::parse($key)['domain'])
            ->unique()
            ->sort()
            ->values();

        return [
            'capabilities' => $capabilities,
            'domains' => $domains,
        ];
    }
}; ?>

<div>
    <x-slot name="title">{{ __('Capabilities') }}</x-slot>

    <div class="space-y-section-gap">
        <x-ui.page-header :title="__('Capabilities')" :subtitle="__('All registered capability keys and their source modules')" />

        <x-ui.card>
            <div class="mb-2 flex items-center gap-3">
                <div class="flex-1">
                    <x-ui.search-input
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Search by capability or module...') }}"
                    />
                </div>
                <x-ui.select wire:model.live="filterDomain">
                    <option value="">{{ __('All Domains') }}</option>
                    @foreach($domains as $domain)
                        <option value="{{ $domain }}">{{ $domain }}</option>
                    @endforeach
                </x-ui.select>
            </div>

            <div class="overflow-x-auto -mx-card-inner px-card-inner">
                <table class="min-w-full divide-y divide-border-default text-sm">
                    <thead class="bg-surface-subtle/80">
                        <tr>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Capability') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Domain') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Resource') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Action') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Module') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface-card divide-y divide-border-default">
                        @forelse($capabilities as $cap)
                            <tr wire:key="cap-{{ $cap->key }}" class="hover:bg-surface-subtle/50 transition-colors">
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap font-mono text-sm text-ink">{{ $cap->key }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">{{ $cap->domain }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">{{ $cap->resource }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">{{ $cap->action }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">{{ $cap->module }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-table-cell-x py-8 text-center text-sm text-muted">{{ __('No capabilities found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-2 text-xs text-muted">
                {{ trans_choice(':count capability|:count capabilities', $capabilities->count(), ['count' => $capabilities->count()]) }}
            </div>
        </x-ui.card>
    </div>
</div>
