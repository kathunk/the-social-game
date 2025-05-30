<div class="flex flex-col gap-4">
    <flux:link :href="route('game-dashboard', $this->game)" variant="ghost">
        Back to dashboard
    </flux:link>

    <x-card>
        <flux:heading size="lg">{{ $player->name }}</flux:heading>
    </x-card>

    <x-card>
        <flux:heading size="lg">
            @if ($this->game->status === 'ended')
                <div class="flex items-center gap-2">
                    Score: {{ $this->player->hidden_score }}
                    @if ($this->player->hidden_score > $this->player->score)
                        <flux:text size="lg" class="text-purple-500 dark:text-purple-300">({{ $this->player->hidden_score - $this->player->score }} hidden)</flux:text>
                    @endif
                </div>
            @else
                <div class="flex items-center gap-2">
                    Score: {{ $this->player->score }}
                    @if ($this->showHiddenPoints && $this->player->hidden_score !== $this->player->score)
                        <flux:text size="lg" class="text-purple-500 dark:text-purple-300">
                            @if ($this->player->hidden_score > $this->player->score)
                                +{{ $this->player->hidden_score - $this->player->score }}
                            @else
                                -{{ $this->player->hidden_score - $this->player->score }}
                            @endif
                        </flux:text>
                    @endif
                </div>
            @endif
        </flux:heading>

        @if (count($this->scoreHistoryEntries) > 0)
            <flux:table>
                <flux:table.columns>
                    <flux:table.column></flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->scoreHistoryEntries as $entry)
                        <flux:table.row>
                            <flux:table.cell>
                                <div class="flex flex-col">
                                    @if ($entry['is_hidden'])
                                        <flux:heading class="text-sm whitespace-normal break-words text-purple-500 dark:text-purple-300">{{ $entry['description'] }}</flux:heading>
                                    @elseif ($entry['points'] > -1)
                                        <flux:heading class="text-sm whitespace-normal break-words text-green-500">{{ $entry['description'] }}</flux:heading>
                                    @else
                                        <flux:heading class="text-sm whitespace-normal break-words text-red-500">{{ $entry['description'] }}</flux:heading>
                                    @endif
                                    <flux:text class="text-xs">{{ Carbon\Carbon::parse($entry['timestamp'])->diffForHumans() }}</flux:text>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell class="flex items-start">
                                <div>
                                    @if ($entry['is_hidden'])
                                        <flux:heading size="sm" class="text-purple-500 dark:text-purple-300">
                                            {{ $entry['points'] > 0 ? '+' : '' }}{{ $entry['points'] }}
                                        </flux:heading>
                                    @elseif ($entry['points'] > -1)
                                        <flux:heading size="sm" class="text-green-500">+{{ $entry['points'] }}</flux:heading>
                                    @else
                                        <flux:heading size="sm" class="text-red-500">{{ $entry['points'] }}</flux:heading>
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @else
            <flux:subheading>No score history yet</flux:subheading>
        @endif
    </x-card>
</div>
