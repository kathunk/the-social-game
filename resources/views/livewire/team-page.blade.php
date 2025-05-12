<div class="flex flex-col gap-4">
    <flux:link :href="route('game-dashboard', $this->game)" variant="ghost">
        Back to dashboard
    </flux:link>

    <x-card>
        <flux:heading size="lg">{{ $team->name }}</flux:heading>

        @if ($this->players->count() > 0)
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Team Members</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->players as $player)
                        <flux:table.row>
                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    <flux:avatar :src="$player->user->gravatar" size="sm"/>
                                    {{ $player->user->name }}
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @else
            <flux:subheading>No players yet</flux:subheading>
        @endif
    </x-card>

    <x-card>
        <flux:heading size="lg">Score: {{ $team->score }}</flux:heading>

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
                                    <flux:heading size="sm">{{ $entry['description'] }}</flux:heading>
                                    <flux:text class="text-xs">{{ Carbon\Carbon::parse($entry['timestamp'])->diffForHumans() }}</flux:text>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if ($entry['points'] > -1)
                                    <flux:heading size="sm" class="text-green-500">+{{ $entry['points'] }}</flux:heading>
                                @else
                                    <flux:heading size="sm" class="text-red-500">{{ $entry['points'] }}</flux:heading>
                                @endif
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
