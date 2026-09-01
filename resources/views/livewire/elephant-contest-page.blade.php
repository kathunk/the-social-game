<div class="space-y-6">
    <x-game-mode-cards.elephant :public-cta="true" />

    <div class="rounded-2xl bg-slate-900 text-white p-5 space-y-4">
        <div class="text-center space-y-1">
            <h2 class="font-bold text-xl">Beat the bot, earn the bounty</h2>
            @if ($promo_active)
                <p class="text-sm opacity-80">Take down the impossible bot and the reward is yours.</p>
            @else
                <p class="text-sm opacity-80">Think you can take down the impossible bot?</p>
            @endif
        </div>

        <div class="flex gap-2">
            @if ($promo_active)
                <div class="flex-1 rounded-xl bg-white/10 p-3 text-center">
                    <p class="text-lg font-bold text-amber-300 leading-tight">{{ $offer }}</p>
                    <p class="text-[11px] opacity-80 mt-1">one-time code, emailed when you win</p>
                    <a
                        href="{{ $offer_url }}"
                        target="_blank"
                        rel="noopener"
                        class="text-[11px] text-amber-300/80 hover:text-amber-300 underline"
                    >What's Colossi?</a>
                </div>
            @endif
            <div class="{{ $promo_active ? '' : 'flex-1 ' }}rounded-xl bg-white/10 p-3 text-center min-w-[110px]">
                <p class="text-2xl font-bold">{{ $record['bot'] }} &ndash; {{ $record['humans'] }}</p>
                <p class="text-[11px] opacity-80 mt-0.5">the bot's record on Impossible</p>
            </div>
        </div>

        <ul class="text-sm opacity-90 space-y-2">
            <li>🐘 Play Elephant in the Room solo against the bot and pick <span class="font-bold">Impossible</span> mode.</li>
            <li>🎁 Beat it outright — a draw doesn't count — and a single-use offer code lands in your inbox. One code per player.</li>
            <li>🔒 The bot's brain runs on our servers and every move is logged, so every win is verifiable.</li>
        </ul>
    </div>
</div>
