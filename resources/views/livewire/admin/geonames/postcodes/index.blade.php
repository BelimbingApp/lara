<?php

use App\Modules\Core\Geonames\Models\Postcode;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $query = Postcode::query()->orderBy('country_iso')->orderBy('postcode');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('postcode', 'like', '%'.$this->search.'%')
                    ->orWhere('place_name', 'like', '%'.$this->search.'%')
                    ->orWhere('country_iso', 'like', '%'.$this->search.'%');
            });
        }

        return [
            'postcodes' => $query->paginate(20),
        ];
    }

    public function import(): void
    {
        Session::flash('info', __('Postcode import is not yet implemented.'));
    }

    public function update(): void
    {
        Session::flash('info', __('Postcode update is not yet implemented.'));
    }
}; ?>

<div>
    <x-slot name="title">{{ __('Geonames Postcodes') }}</x-slot>

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-ink">{{ __('Geonames Postcodes') }}</h1>
            <div class="flex items-center gap-2">
                <button wire:click="import" wire:loading.attr="disabled" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 rounded-lg font-medium transition-colors disabled:opacity-50">
                    <x-icon name="heroicon-o-arrow-down-tray" class="w-5 h-5" />
                    <span wire:loading.remove wire:target="import">{{ __('Import') }}</span>
                    <span wire:loading wire:target="import">{{ __('Importing...') }}</span>
                </button>
                <button wire:click="update" wire:loading.attr="disabled" class="inline-flex items-center gap-2 px-4 py-2 bg-accent hover:bg-accent-hover text-accent-on rounded-lg font-medium transition-colors disabled:opacity-50">
                    <x-icon name="heroicon-o-arrow-path" class="w-5 h-5" />
                    <span wire:loading.remove wire:target="update">{{ __('Update') }}</span>
                    <span wire:loading wire:target="update">{{ __('Updating...') }}</span>
                </button>
            </div>
        </div>

        @if (session('success'))
            <div class="flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-green-800 dark:text-green-200">
                <x-icon name="heroicon-o-check-circle" class="w-6 h-6 shrink-0" />
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="flex items-center gap-3 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-red-800 dark:text-red-200">
                <x-icon name="heroicon-o-exclamation-circle" class="w-6 h-6 shrink-0" />
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if (session('info'))
            <div class="flex items-center gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg text-blue-800 dark:text-blue-200">
                <x-icon name="heroicon-o-information-circle" class="w-6 h-6 shrink-0" />
                <span>{{ session('info') }}</span>
            </div>
        @endif

        <div class="bg-surface-card border border-border-default shadow-sm rounded-lg">
            <div class="p-6">
                <div class="mb-4">
                    <input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="{{ __('Search by postcode, place name, or country...') }}"
                        class="w-full sm:w-80 px-4 py-2 border border-border-input rounded-lg bg-surface-card text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                    />
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border-default">
                        <thead class="bg-surface-subtle/80">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">{{ __('Country') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">{{ __('Postcode') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">{{ __('Place Name') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">{{ __('Admin1 Code') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">{{ __('Latitude') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">{{ __('Longitude') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-muted uppercase tracking-wider">{{ __('Updated') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-surface-card divide-y divide-border-default">
                            @forelse($postcodes as $postcode)
                                <tr class="hover:bg-surface-subtle/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-ink">{{ $postcode->country_iso }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-muted">{{ $postcode->postcode }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-muted">{{ $postcode->place_name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-muted">{{ $postcode->admin1_code }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-muted">{{ $postcode->latitude }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-muted">{{ $postcode->longitude }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-muted">{{ $postcode->updated_at ? $postcode->updated_at->format('Y-m-d') : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-sm text-muted">{{ __('No postcodes found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $postcodes->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
