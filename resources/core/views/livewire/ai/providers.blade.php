<div>
    <x-slot name="title">{{ __('LLM Providers') }}</x-slot>

    @if($wizardStep === 'catalog')
        <livewire:ai.providers.catalog />
    @elseif($wizardStep === 'connect')
        <livewire:ai.providers.connect-wizard :initial-forms="$connectForms" />
    @else
        <livewire:ai.providers.manager />
    @endif
</div>
