@props(['teams', 'players', 'type'])

<div>
    <flux:card>
        <flux:heading size="lg">Scoreboard</flux:heading>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>
                    @if ($type === 'team')
                        Team
                    @else
                        Player
                    @endif
                </flux:table.column>
                <flux:table.column>Score</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @if ($type === 'team')
                    @foreach ($teams as $team)
                        <flux:table.row>
                            <flux:table.cell>
                                <flux:button :variant="((string) $team->id === (string) $this->player->team_id) ? 'filled' : 'ghost'" size="sm" class="p-0" :href="route('teams.show', [$team->game_id, $team->id])">
                                    {{ $team->name }}
                                    @if ((string) $team->id === (string) $this->player->team_id)
                                        (your team)
                                    @endif
                                </flux:button>
                            </flux:table.cell>
                            <flux:table.cell>{{ $team->score }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                @else
                    @foreach ($players as $player)
                        <flux:table.row>
                            <flux:table.cell>
                                <flux:button :variant="((string) $player->id === (string) $this->player->id) ? 'filled' : 'ghost'" size="sm" class="p-0" :href="route('players.show', [$player->game_id, $player->id])">
                                    {{ $player->name }}
                                </flux:button>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    {{ $player->score }}
                                    @if ($player->id === $this->player->id && $player->hidden_score > $player->score)
                                        <flux:text class="text-purple-500 dark:text-purple-300">
                                            +{{ $player->hidden_score - $player->score }}
                                        </flux:text>
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                @endif
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
