@props(['teams', 'players', 'type'])
@if ($type === 'hide_until_end' && $this->game->status !== 'ended')

@else
<div>
    <x-card>
        <x-forms.heading class="!text-lg">Scoreboard</x-forms.heading>

        <flux:table class="**:!text-xs *:!border-0">
            <flux:table.columns class="**:!pb-0 **:!font-semibold">
                <flux:table.column>
                    @if ($type === 'team')
                        TEAM
                    @else
                        PLAYERS
                    @endif
                </flux:table.column>
                @if ($type === 'team')
                    <flux:table.column>PLAYERS</flux:table.column>
                @endif
                <flux:table.column width="15%">SCORE</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @if ($type === 'team')
                    @foreach ($teams as $team)
                        <flux:table.row>
                            <flux:table.cell>
                                <div class="w-fit p-0 whitespace-normal break-words">
                                    <flux:link variant="ghost" :href="route('teams.show', [$team->game_id, $team->id])">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold">{{ $team->name }}</span>
                                            @if ((string) $team->id === (string) $this->player->team_id)
                                                <flux:icon variant="outline" name="user" class="size-2.5 text-light-blue stroke-3" />
                                            @endif
                                        </div>
                                    </flux:link>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <span class="text-black font-bold">{{ $this->players->where('team_id', $team->id)->count() }}</span>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if ($this->game->status === 'ended')
                                    <div class="flex items-center gap-2">
                                        <span class="text-black font-bold">{{ $team->hidden_score }}</span>
                                        @if ($team->hidden_score !== $team->score)
                                            <flux:text class="text-faded-gray font-light">
                                                ({{ $team->hidden_score - $team->score }} hidden)
                                            </flux:text>
                                        @endif
                                    </div>
                                @else
                                    <div class="flex items-center gap-2">
                                        <span class="text-black font-bold">{{ $team->score }}</span>
                                        @if ($team->id === $this->player->team_id && $team->hidden_score !== $team->score)
                                            <flux:text class="text-faded-gray">
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

                @if (
                    $type === 'individual' 
                    || ($type === 'blood_oath' && $this->game->status === 'active') 
                    || ($type === 'hide_until_end' && $this->game->status === 'ended' && $this->game->challenges->first()->handler()::TYPE === 'individual')
                )
                    @foreach ($players as $player)
                        <flux:table.row>
                            <flux:table.cell>
                                <div class="w-fit p-0 whitespace-normal break-words">
                                    <flux:link variant="ghost" :href="route('players.show', [$player->game_id, $player->id])">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold">{{ $player->name }}</span>
                                            @if ((string) $player->id === (string) $this->player->id)
                                                <flux:icon variant="outline" name="user" class="size-2.5 text-light-blue stroke-3" />
                                            @endif
                                        </div>
                                    </flux:link>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if ($this->game->status === 'ended')
                                    <div class="flex items-center gap-2">
                                        <span class="text-black font-bold">{{ $player->hidden_score }}</span>
                                        @if ($player->hidden_score > $player->score)
                                            <flux:text class="text-faded-gray font-light">
                                                ({{ $player->hidden_score - $player->score }} hidden)
                                            </flux:text>
                                        @endif
                                    </div>
                                @else
                                    <div class="flex items-center gap-2">
                                        <span class="text-black font-bold">{{ $player->score }}</span>
                                        @if ($player->id === $this->player->id && $player->hidden_score !== $player->score)
                                            <flux:text class="text-faded-gray font-light">
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
                                    <flux:link variant="ghost" :href="route('players.show', [$this->game->id, $row['players'][0]->id])" class="whitespace-normal break-words font-bold">
                                        {{ $row['players'][0]->name }}
                                    </flux:link>
                                    @if (collect($row['players'])->count() > 1)
                                        &
                                        <flux:link variant="ghost" :href="route('players.show', [$this->game->id, $row['players'][1]->id])" class="font-bold">
                                            {{ $row['players'][1]->name }}
                                        </flux:link>
                                    @endif
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center gap-2">
                                    <span class="text-black font-bold">{{ $row['final_score'] }}</span>
                                    @if ($row['hidden_points'] > 0)
                                        <flux:text class="text-faded-gray font-light">
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
    </x-card>
</div>
@endif