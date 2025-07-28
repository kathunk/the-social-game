<div class="flex flex-col gap-4">
    <div class="text-blue-500 text-xs w-fit">
        <flux:link variant="ghost" :href="route('game-dashboard', $this->game)" class="cursor-pointer">
            <div class="flex flex-wrap items-center gap-1">
                <flux:icon class="size-3 stroke-2" name="chevron-left" />
                <div>Back to Dashboard</div>
            </div>
        </flux:link>
    </div>

    <x-card>
        <flux:heading size="lg">{{ $player->name }}</flux:heading>
    </x-card>

    <x-card>
        <div class="text-faded-gray text-tiny md:text-xxs font-bold">SCORE</div>
        <div class="flex items-center gap-2">
            @if ($this->game->status === 'ended')
                <flux:heading class="!text-lg">{{ $this->player->hidden_score }}</flux:heading>

                @if ($this->player->hidden_score > $this->player->score)
                    <flux:text class="text-faded-gray">
                        ({{ $this->player->hidden_score - $this->player->score }} hidden)
                    </flux:text>
                @endif
            @else
                <flux:heading class="!text-lg">{{ $this->player->score }}</flux:heading>

                @if ($this->showHiddenPoints && $this->player->hidden_score !== $this->player->score)
                    <flux:text class="text-faded-gray">
                        @if ($this->player->hidden_score > $this->player->score)
                            +{{ $this->player->hidden_score - $this->player->score }}
                        @else
                            {{ $this->player->hidden_score - $this->player->score }}
                        @endif
                    </flux:text>
                @endif
            @endif
        </div>

        <x-event-timeline :entries="$this->scoreHistoryEntries" />
    </x-card>
</div>
