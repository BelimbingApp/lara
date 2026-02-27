@props([
    'label' => null,
    'error' => null,
    'placeholder' => '',
    'required' => false,
    'options' => [],
    'editable' => false,
    'searchMethod' => null,
    'searchUrl' => null,
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

<div {{ $attributes->whereDoesntStartWith('wire:model')->except(['label', 'error', 'placeholder', 'required', 'options', 'editable', 'searchMethod', 'searchUrl', 'id'])->class(['space-y-1']) }}>
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
            activeValue: null,
            searchTimeout: null,
            editable: {{ $editable ? 'true' : 'false' }},
            searchMethod: {{ $searchMethod ? "'".addslashes($searchMethod)."'" : 'null' }},
            searchUrl: {{ $searchUrl ? "'".addslashes($searchUrl)."'" : 'null' }},
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
                if (this.searchUrl) {
                    this.$watch('query', () => {
                        clearTimeout(this.searchTimeout)
                        this.searchTimeout = setTimeout(() => {
                            const url = new URL(this.searchUrl)
                            url.searchParams.set('q', this.query)
                            fetch(url, { credentials: 'same-origin' })
                                .then(r => r.json())
                                .then(data => { this.options = Array.isArray(data) ? data : [] })
                                .catch(() => { this.options = [] })
                        }, 300)
                    })
                } else if (this.searchMethod) {
                    this.$watch('query', () => {
                        clearTimeout(this.searchTimeout)
                        this.searchTimeout = setTimeout(() => {
                            this.$wire.call(this.searchMethod, this.query)
                        }, 300)
                    })
                }
            },

            syncQuery() {
                if (this.editable) {
                    const opt = this.selectedOption
                    this.query = opt ? opt.label : (this.selectedValue ?? '')
                } else {
                    this.query = this.selectedOption?.label ?? ''
                }
            },

            openList() {
                this.open = true
                this.activeValue = this.filtered.length ? this.filtered[0].value : null
                this.$nextTick(() => this.scrollActive())
            },

            closeList() {
                if (this.editable) {
                    const trimmed = this.query.trim()
                    if (trimmed) {
                        const match = this.options.find(o => o.label === trimmed || o.value === trimmed)
                        this.selectedValue = match ? match.value : trimmed
                    } else {
                        this.selectedValue = null
                    }
                }
                this.open = false
                this.activeValue = null
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

            selectByValue(val, label) {
                this.selectedValue = val
                this.query = label
                this.open = false
            },

            matchesFilter(label) {
                if (!label) return true
                const q = this.query.trim().toLowerCase()
                return !q || label.toLowerCase().includes(q)
            },

            setActiveByValue(val) {
                this.activeValue = val
                this.$nextTick(() => this.scrollActive())
            },

            cycleActive(direction) {
                const f = this.filtered
                if (!f.length) return
                const idx = f.findIndex(o => o.value === this.activeValue)
                const next = direction === 1
                    ? (idx < 0 ? 0 : (idx + 1) % f.length)
                    : (idx <= 0 ? f.length - 1 : idx - 1)
                this.activeValue = f[next].value
                this.$nextTick(() => this.scrollActive())
            },

            onKeydown(e) {
                if (e.key === 'ArrowDown') {
                    e.preventDefault()
                    if (!this.open) { this.openList(); return }
                    this.cycleActive(1)
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault()
                    if (!this.open) { this.openList(); return }
                    this.cycleActive(-1)
                } else if (e.key === 'Enter') {
                    e.preventDefault()
                    if (this.open) {
                        const opt = this.filtered.find(o => o.value === this.activeValue)
                        if (opt) {
                            this.select(opt)
                        } else if (this.editable) {
                            this.closeList()
                        }
                    }
                } else if (e.key === 'Escape') {
                    if (this.open) { e.preventDefault(); this.closeList() }
                }
            },

            scrollActive() {
                if (this.activeValue && this.$refs.listbox) {
                    const el = Array.from(this.$refs.listbox.querySelectorAll('[data-value]')).find(li => li.getAttribute('data-value') === String(this.activeValue))
                    el?.scrollIntoView({ block: 'nearest' })
                }
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
                @if($searchUrl)
                <template x-for="option in filtered" :key="option.value">
                    <li
                        role="option"
                        :data-value="option.value"
                        :aria-selected="option.value === String(selectedValue)"
                        @mouseenter="setActiveByValue(option.value)"
                        @mousedown.prevent="select(option)"
                        class="px-input-x py-1.5 text-sm cursor-pointer select-none"
                        :class="option.value === activeValue ? 'bg-accent text-accent-on' : 'text-ink hover:bg-surface-subtle'"
                    >
                        <span x-text="option.label"></span>
                    </li>
                </template>
                @else
                @foreach($options as $o)
                <li
                    x-show="matchesFilter($el.dataset.label)"
                    role="option"
                    data-value="{{ e($o['value'] ?? '') }}"
                    data-label="{{ e($o['label'] ?? '') }}"
                    :aria-selected="$el.dataset.value === String(selectedValue)"
                    @mouseenter="setActiveByValue($el.dataset.value)"
                    @mousedown.prevent="selectByValue($el.dataset.value, $el.dataset.label)"
                    class="px-input-x py-1.5 text-sm cursor-pointer select-none"
                    :class="$el.dataset.value === activeValue ? 'bg-accent text-accent-on' : 'text-ink hover:bg-surface-subtle'"
                >
                    <span>{{ $o['label'] ?? '' }}</span>
                </li>
                @endforeach
                @endif
            </ul>
            <p x-show="filtered.length === 0" class="px-input-x py-2 text-sm text-muted">{{ __('No results found.') }}</p>
        </div>
    </div>

    @if($error)
        <p class="text-sm text-status-danger">{{ $error }}</p>
    @endif
</div>
