@props([
    'label' => null,
    'error' => null,
    'placeholder' => '',
    'required' => false,
    'options' => [],
])

@php
    use Illuminate\Support\Str;
    use Illuminate\Support\Js;

    $id = $attributes->get('id') ?? 'cbx-' . Str::random(8);
    $optionsJs = Js::from(collect($options)->map(fn ($o) => [
        'value' => (string) ($o['value'] ?? ''),
        'label' => $o['label'] ?? '',
    ])->values()->all());
@endphp

<div {{ $attributes->whereDoesntStartWith('wire:model')->except(['label', 'error', 'placeholder', 'required', 'options', 'id'])->class(['space-y-1']) }}>
    @if($label)
        <label for="{{ $id }}" class="block text-[11px] uppercase tracking-wider font-semibold text-muted">
            {{ $label }}
            @if($required)
                <span class="text-status-danger">*</span>
            @endif
        </label>
    @endif

    <div
        x-data="{
            open: false,
            query: '',
            activeIndex: -1,
            options: {{ $optionsJs }},
            selectedValue: @entangle($attributes->wire('model')),

            get selectedOption() {
                if (this.selectedValue === null || this.selectedValue === '') return null
                return this.options.find(o => o.value === String(this.selectedValue)) ?? null
            },

            get filtered() {
                const q = this.query.trim().toLowerCase()
                return q ? this.options.filter(o => o.label.toLowerCase().includes(q)) : this.options
            },

            init() {
                this.syncQuery()
                this.$watch('selectedValue', () => this.syncQuery())
            },

            syncQuery() {
                this.query = this.selectedOption?.label ?? ''
            },

            openList() {
                this.open = true
                this.activeIndex = this.filtered.length ? 0 : -1
                this.$nextTick(() => this.scrollActive())
            },

            closeList() {
                this.open = false
                this.activeIndex = -1
                this.syncQuery()
            },

            clear() {
                this.selectedValue = null
                this.query = ''
                this.open = false
                this.$nextTick(() => this.$refs.input.focus())
            },

            select(opt) {
                this.selectedValue = opt.value
                this.query = opt.label
                this.open = false
            },

            onKeydown(e) {
                if (e.key === 'ArrowDown') {
                    e.preventDefault()
                    if (!this.open) { this.openList(); return }
                    this.activeIndex = this.filtered.length ? (this.activeIndex + 1) % this.filtered.length : -1
                    this.$nextTick(() => this.scrollActive())
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault()
                    if (!this.open) { this.openList(); return }
                    this.activeIndex = this.activeIndex <= 0 ? this.filtered.length - 1 : this.activeIndex - 1
                    this.$nextTick(() => this.scrollActive())
                } else if (e.key === 'Enter') {
                    e.preventDefault()
                    if (this.open && this.activeIndex >= 0 && this.filtered[this.activeIndex]) {
                        this.select(this.filtered[this.activeIndex])
                    }
                } else if (e.key === 'Escape') {
                    if (this.open) { e.preventDefault(); this.closeList() }
                }
            },

            scrollActive() {
                this.$refs.listbox?.querySelector('[data-index=\'' + this.activeIndex + '\']')?.scrollIntoView({ block: 'nearest' })
            },
        }"
        @click.outside="closeList()"
        @focusout="setTimeout(() => { if (!$el.contains(document.activeElement)) closeList() }, 0)"
        class="relative"
    >
        <div class="relative">
            <input
                id="{{ $id }}"
                x-ref="input"
                type="text"
                autocomplete="off"
                role="combobox"
                aria-autocomplete="list"
                :aria-expanded="open"
                @focus="openList()"
                @input="openList()"
                @keydown="onKeydown($event)"
                x-model="query"
                placeholder="{{ $placeholder }}"
                @if($required) aria-required="true" @endif
                @class([
                    'w-full px-input-x py-input-y pr-8 text-sm border rounded-2xl transition-colors',
                    'border-border-input bg-surface-card text-ink placeholder:text-muted',
                    'focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent',
                    'disabled:opacity-50 disabled:cursor-not-allowed',
                    'border-status-danger focus:ring-status-danger' => $error,
                ])
            >

            <button
                type="button"
                x-cloak
                x-show="selectedValue != null && selectedValue !== ''"
                @click="clear()"
                class="absolute inset-y-0 right-2 flex items-center text-muted hover:text-ink"
                tabindex="-1"
                aria-label="{{ __('Clear') }}"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <div
            x-cloak
            x-show="open"
            x-transition.opacity.duration.150ms
            class="absolute z-50 mt-1 w-full rounded-2xl border border-border-input bg-surface-card shadow-sm"
        >
            <ul
                x-ref="listbox"
                x-show="filtered.length > 0"
                role="listbox"
                class="max-h-60 overflow-auto py-1"
            >
                <template x-for="(opt, index) in filtered" :key="opt.value">
                    <li
                        role="option"
                        :data-index="index"
                        :aria-selected="opt.value === String(selectedValue)"
                        @mouseenter="activeIndex = index"
                        @mousedown.prevent="select(opt)"
                        class="px-input-x py-1.5 text-sm cursor-pointer select-none"
                        :class="index === activeIndex ? 'bg-accent text-accent-on' : 'text-ink hover:bg-surface-subtle'"
                    >
                        <span x-text="opt.label"></span>
                    </li>
                </template>
            </ul>
            <p x-show="filtered.length === 0" class="px-input-x py-2 text-sm text-muted">{{ __('No results found.') }}</p>
        </div>
    </div>

    @if($error)
        <p class="text-sm text-status-danger">{{ $error }}</p>
    @endif
</div>
