<?php

use App\Modules\Core\Address\Models\Address;
use Illuminate\Support\Facades\DB;
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
        return [
            'addresses' => Address::query()
                ->when($this->search, function ($query, $search): void {
                    $query
                        ->where('label', 'like', '%'.$search.'%')
                        ->orWhere('line1', 'like', '%'.$search.'%')
                        ->orWhere('locality', 'like', '%'.$search.'%')
                        ->orWhere('postcode', 'like', '%'.$search.'%')
                        ->orWhere('country_iso', 'like', '%'.$search.'%');
                })
                ->latest()
                ->paginate(15),
        ];
    }

    public function statusVariant(string $status): string
    {
        return match ($status) {
            'verified' => 'success',
            'suggested' => 'warning',
            default => 'default',
        };
    }

    public function delete(int $addressId): void
    {
        $address = Address::query()->findOrFail($addressId);

        $linkedCount = DB::table('addressables')
            ->where('address_id', $address->id)
            ->count();

        if ($linkedCount > 0) {
            Session::flash('error', __('Cannot delete an address linked to :count entity(ies). Unlink it first.', ['count' => $linkedCount]));

            return;
        }

        $address->delete();

        Session::flash('success', __('Address deleted successfully.'));
    }
}; ?>

<div>
    <x-slot name="title">{{ __('Address Management') }}</x-slot>

    <div class="space-y-section-gap">
        <x-ui.page-header :title="__('Address Management')">
            <x-slot name="actions">
                <x-ui.button
                    variant="primary"
                    as="a"
                    href="{{ route('admin.addresses.create') }}"
                    wire:navigate
                >
                    <x-icon name="heroicon-o-plus" class="w-4 h-4" />
                    {{ __('Create Address') }}
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
            <div class="mb-2">
                <x-ui.search-input
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Search by label, address line, city, postcode, or country code...') }}"
                />
            </div>

            <div class="overflow-x-auto -mx-card-inner px-card-inner">
                <table class="min-w-full divide-y divide-border-default text-sm">
                    <thead class="bg-surface-subtle/80">
                        <tr>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Label') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Address') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Locality') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Country') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-left text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Status') }}</th>
                            <th class="px-table-cell-x py-table-header-y text-right text-[11px] font-semibold text-muted uppercase tracking-wider">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface-card divide-y divide-border-default">
                        @forelse($addresses as $address)
                            <tr wire:key="address-{{ $address->id }}" class="hover:bg-surface-subtle/50 transition-colors">
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap">
                                    <a href="{{ route('admin.addresses.show', $address) }}" wire:navigate class="text-sm font-medium text-link hover:underline">{{ $address->label ?: __('Unlabeled') }}</a>
                                </td>
                                <td class="px-table-cell-x py-table-cell-y text-sm text-muted">
                                    <div class="max-w-xl truncate">{{ $address->line1 ?: __('No line 1') }}</div>
                                    @if($address->line2)
                                        <div class="max-w-xl truncate">{{ $address->line2 }}</div>
                                    @endif
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">
                                    <div>{{ $address->locality ?: '-' }}</div>
                                    <div class="tabular-nums">{{ $address->postcode ?: '-' }}</div>
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted tabular-nums">{{ $address->country_iso ?: '-' }}</td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-sm text-muted">
                                    <x-ui.badge :variant="$this->statusVariant($address->verification_status)">{{ ucfirst($address->verification_status) }}</x-ui.badge>
                                </td>
                                <td class="px-table-cell-x py-table-cell-y whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            wire:click="delete({{ $address->id }})"
                                            wire:confirm="{{ __('Are you sure you want to delete this address?') }}"
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
                                <td colspan="6" class="px-table-cell-x py-8 text-center text-sm text-muted">{{ __('No addresses found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-2">
                {{ $addresses->links() }}
            </div>
        </x-ui.card>
    </div>
</div>
