@props(['element'])

@once
    <style>
        @keyframes elephant-rematch-glow {
            0%, 100% { box-shadow: 0 0 4px 2px rgba(250, 204, 21, 0.7); }
            50% { box-shadow: 0 0 12px 5px rgba(250, 204, 21, 0.95); }
        }
        .elephant-rematch-glow { animation: elephant-rematch-glow 1.2s ease-in-out infinite; }
        @media (prefers-reduced-motion: reduce) {
            .elephant-rematch-glow { animation: none; box-shadow: 0 0 8px 3px rgba(250, 204, 21, 0.8); }
        }
    </style>
@endonce

<div class="flex flex-col items-center gap-4 py-4 text-center">
    {{-- Bounty celebration: only on the game that earned this player their
         offer code --}}
    @if ($element['reward_win'] ?? null)
        <div class="w-full max-w-[340px] rounded-2xl bg-slate-900 text-white px-5 py-6 space-y-2">
            <div class="text-4xl">🎁</div>
            <p class="font-bold text-lg">You beat the impossible bot!</p>
            <p class="text-sm opacity-90">
                Your one-time code for
                <span class="font-bold text-amber-300">{{ $element['reward_win']['offer'] }}</span>
                is on its way to your inbox.
            </p>
            <a
                href="{{ $element['reward_win']['offer_url'] }}"
                target="_blank"
                rel="noopener"
                class="text-xs text-amber-300/80 hover:text-amber-300 underline"
            >What's Colossi?</a>
        </div>
    @endif

    <flux:heading size="lg">{{ $element['result_text'] }}</flux:heading>

    {{-- The final board, frozen: seat colors, winning shape glowing, elephant
         where it ended up --}}
    @if ($element['board_cells'])
        <div class="grid grid-cols-4 gap-1 p-2 bg-slate-100 rounded-xl" style="width: max-content;">
            @foreach ($element['board_cells'] as $cell)
                <div
                    class="relative w-9 h-9 rounded-md {{ $cell['is_winning'] ? 'elephant-rematch-glow' : '' }}"
                    style="background-color: {{ $cell['color'] ?? '#e2e8f0' }};"
                >
                    @if ($cell['has_elephant'])
                        <span class="absolute inset-0 flex items-center justify-center text-2xl">🐘</span>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

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
