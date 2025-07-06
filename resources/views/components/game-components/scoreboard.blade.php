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
                        Players
                    @endif
                </flux:table.column>
                @if ($type === 'team')
                    <flux:table.column>Players</flux:table.column>
                @endif
                <flux:table.column>Score</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @if ($type === 'team')
                    @foreach ($teams as $team)
                        <flux:table.row>
                            <flux:table.cell>
                                <flux:link :variant="((string) $team->id === (string) $this->player->team_id) ? 'filled' : 'ghost'" size="sm" class="p-0 whitespace-normal break-words" :href="route('teams.show', [$team->game_id, $team->id])">
                                    {{ $team->name }}
                                    @if ((string) $team->id === (string) $this->player->team_id)
                                        (your team)
                                    @endif
                                </flux:link>
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $this->players->where('team_id', $team->id)->count() }}
                            </flux:table.cell>
                            <flux:table.cell>
                                @if ($this->game->status === 'ended')
                                    <div class="flex items-center gap-2">
                                        {{ $team->hidden_score }} 
                                        @if ($team->hidden_score !== $team->score)    
                                            <flux:text class="text-purple-500 dark:text-purple-300">
                                                ({{ $team->hidden_score - $team->score }} hidden)
                                            </flux:text>
                                        @endif
                                    </div>
                                @else
                                    <div class="flex items-center gap-2">
                                        {{ $team->score }}
                                        @if ($team->id === $this->player->team_id && $team->hidden_score !== $team->score)
                                            <flux:text class="text-purple-500 dark:text-purple-300">
                                                @if ($team->hidden_score > $team->score)
                                                    +{{ $team->hidden_score - $team->score }}
                                                @else
                                                    {{ $team->hidden_score - $team->score }}
                                                @endif
                                            </flux:text>
                                        @endif
                                    </div>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                @endif

                @if ($type === 'individual' || ($type === 'blood_oath' && $this->game->status === 'active'))
                    @foreach ($players as $player)
                        <flux:table.row>
                            <flux:table.cell>
                                <flux:link :variant="((string) $player->id === (string) $this->player->id) ? 'filled' : 'ghost'" size="sm" class="p-0 whitespace-normal break-words" :href="route('players.show', [$player->game_id, $player->id])">
                                    {{ $player->name }}
                                </flux:link>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if ($this->game->status === 'ended')
                                    <div class="flex items-center gap-2">
                                        {{ $player->hidden_score }} 
                                        @if ($player->hidden_score > $player->score)
                                            <flux:text class="text-purple-500 dark:text-purple-300">
                                                ({{ $player->hidden_score - $player->score }} hidden)
                                            </flux:text>
                                        @endif
                                    </div>
                                @else
                                    <div class="flex items-center gap-2">
                                        {{ $player->score }}
                                        @if ($player->id === $this->player->id && $player->hidden_score !== $player->score)
                                            <flux:text class="text-purple-500 dark:text-purple-300">
                                                @if ($player->hidden_score > $player->score)
                                                    +{{ $player->hidden_score - $player->score }}
                                                @else
                                                    {{ $player->hidden_score - $player->score }}
                                                @endif
                                            </flux:text>
                                        @endif
                                    </div>
                                @endif
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                @endif

                @if ($type === 'blood_oath' && $this->game->status === 'ended')
                    @php
                        $modifier_data = $this->game->modifiers()->where('class_key', App\Modifiers\Classes\BloodOaths::key())->first()->modifier_data;
                        $pair_ids = collect($modifier_data['pairs']);
                        $loan_wolves = $players->reject(fn($player) => 
                            $pair_ids->has($player->id) || $pair_ids->contains($player->id)
                        );

                        $rows = $loan_wolves->map(fn($player) => [
                            'players' => [$player],
                            'final_score' => $player->score,
                            'hidden_points' => $player->hidden_score - $player->score,
                        ]);

                        $pair_ids->each(function($key, $pair) use ($rows, $players) {
                            $player_1 = $players->firstWhere('id', $pair);
                            $player_2 = $players->firstWhere('id', $key);

                            $pair_accounted_for = $rows->filter(fn($row) => 
                                collect($row['players'])->contains(fn($p) => $p->id === $player_1->id || $p->id === $player_2->id)
                            )->count() > 0;

                            if ($pair_accounted_for) {
                                return;
                            }

                            $final_score = $player_1->hidden_score + $player_2->hidden_score;
                            $hidden_points = $final_score - $player_1->score - $player_2->score;

                            $rows->push([
                                'players' => [$player_1, $player_2],
                                'final_score' => $final_score,
                                'hidden_points' => $hidden_points,
                            ]);
                        });

                        $rows = $rows->sortByDesc('final_score');
                    @endphp

                    @foreach ($rows as $row)
                        <flux:table.row>
                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    <flux:link :href="route('players.show', [$this->game->id, $row['players'][0]->id])" class="whitespace-normal break-words">
                                        {{ $row['players'][0]->name }}
                                    </flux:link>
                                    @if (collect($row['players'])->count() > 1)
                                        &
                                        <flux:link :href="route('players.show', [$this->game->id, $row['players'][1]->id])">
                                            {{ $row['players'][1]->name }}
                                        </flux:link>
                                    @endif
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    {{ $row['final_score'] }} 
                                    @if ($row['hidden_points'] > 0)
                                        <flux:text class="text-purple-500 dark:text-purple-300">
                                            ({{ $row['hidden_points'] }} hidden)
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
