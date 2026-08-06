@props(['element'])

<div class="flex flex-col items-center gap-4 py-4 text-center">
    <flux:heading size="lg">{{ $element['result_text'] }}</flux:heading>

    @if ($element['rematch_url'])
        {{-- The rematch exists — forward this player. The auto-redirect fires
             when the card re-renders (broadcast reload or poll); the button
             is the no-JS fallback. --}}
        <div
            x-data
            x-init="window.location.href = @js($element['rematch_url'])"
            class="flex flex-col items-center gap-2"
        >
            <flux:subheading>Rematch is on!</flux:subheading>
            <x-button variant="primary" href="{{ $element['rematch_url'] }}">Join the rematch</x-button>
        </div>
    @elseif ($element['i_voted'])
        <flux:subheading class="animate-pulse">
            Waiting for {{ $element['waiting_on'] !== '' ? $element['waiting_on'] : 'your opponent' }} to accept…
        </flux:subheading>
    @else
        @if (count($element['requester_names']) > 0)
            <flux:subheading>{{ implode(' and ', $element['requester_names']) }} wants a rematch!</flux:subheading>
        @endif
        <span @class(['animate-pulse' => count($element['requester_names']) > 0])>
            <x-button
                variant="primary"
                wire:loading.attr="disabled"
                wire:click="callClassAction('requestRematch', 'modifier', '{{ $element['class_key'] }}', null)"
            >
                {{ $element['is_bot_game'] ? 'Play again' : 'Rematch' }}
            </x-button>
        </span>
    @endif
</div>
