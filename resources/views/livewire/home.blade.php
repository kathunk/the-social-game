<div class="space-y-6">
    {{-- ELEPHANT BOUNTY MODAL: beat the impossible bot, earn a one-time
         Catacombian offer code. Dismissable per day; hidden entirely once
         this user has earned their code or the pool is empty. --}}
    @if ($bounty = $this->elephantBounty)
        <div
            x-data="{
                open: false,
                init() { this.open = localStorage.getItem(@js($bounty['dismiss_key'])) !== '1'; },
                dismiss() { localStorage.setItem(@js($bounty['dismiss_key']), '1'); this.open = false; },
            }"
            x-show="open"
            x-cloak
            style="display: none;"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70"
        >
            <div class="relative w-full max-w-sm rounded-2xl bg-slate-900 text-white p-6 text-center space-y-4 shadow-xl">
                <button
                    @click="dismiss()"
                    class="absolute top-3 right-3 text-white/60 hover:text-white text-xl leading-none"
                    aria-label="Dismiss"
                >&times;</button>

                <div class="text-5xl">🐘</div>

                <p class="font-bold text-xl">Beat the bot, earn the bounty</p>
                <p class="text-sm opacity-90">
                    Beat the bot on Impossible mode and earn a one-time code for
                    <span class="font-bold text-amber-300">{{ $bounty['offer'] }}</span>.
                    The bot is currently
                    <span class="font-bold">{{ $bounty['record']['bot'] }} &ndash; {{ $bounty['record']['humans'] }}</span>.
                </p>
                <a
                    href="{{ $bounty['offer_url'] }}"
                    target="_blank"
                    rel="noopener"
                    class="text-xs text-amber-300/80 hover:text-amber-300 underline"
                >What's Colossi?</a>
                <button
                    wire:click="startGameFromMode('{{ $bounty['bot_mode_id'] }}')"
                    wire:loading.attr="disabled"
                    class="w-full rounded-xl text-white font-bold py-2.5 px-4 text-sm hover:scale-[1.02] transition-transform"
                    style="background-color: #007393;"
                >Challenge the Bot</button>
                <button
                    @click="dismiss()"
                    class="text-xs text-white/60 hover:text-white"
                >Maybe later</button>
            </div>
        </div>
    @endif

    @if ($this->games->filter(fn ($game) => $game->status === 'active')->count() > 0)
        <x-menus.game-card :games="$this->games->filter(fn ($game) => $game->status === 'active')" status="active" />
    @endif

    @if ($this->games->filter(fn ($game) => $game->status === 'upcoming')->count() > 0)
        <x-menus.game-card :games="$this->games->filter(fn ($game) => $game->status === 'upcoming')" status="upcoming" />
    @endif

    {{-- GAME MODE CARDS - start a new game --}}
    @if ($this->gameModeCards->isNotEmpty())
        <section>
            <h2 class="text-xs font-bold uppercase tracking-wider text-faded-gray mb-3">Start a new game</h2>
            <div class="space-y-3">
                @foreach ($this->gameModeCards as $card)
                    <x-dynamic-component :component="$card['component']" :modes="$card['modes']" />
                @endforeach
            </div>
        </section>
    @endif

    @if ($this->games->filter(fn ($game) => $game->status === 'ended')->count() > 0)
        <x-menus.game-card :games="$this->games->filter(fn ($game) => $game->status === 'ended')" status="ended" />
    @endif
</div>
