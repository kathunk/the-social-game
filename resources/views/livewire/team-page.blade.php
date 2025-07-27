<div class="flex flex-col gap-4">
    <flux:link :href="route('game-dashboard', $this->game)" variant="ghost">
        Back to dashboard
    </flux:link>

    <flux:tab.group>
        <flux:tabs wire:model="tab">
            <flux:tab name="score">Score</flux:tab>
            <flux:tab name="members">Members</flux:tab>
        </flux:tabs>

        <flux:tab.panel name="members">
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
                                            <div data-flux-avatar="data-flux-avatar" data-slot="avatar" data-size="sm" class="[:where(&amp;)]:size-8 [:where(&amp;)]:text-sm [--avatar-radius:var(--radius-md)] relative flex-none isolate flex items-center justify-center [:where(&amp;)]:font-medium rounded-[var(--avatar-radius)] [:where(&amp;)]:bg-zinc-200 [:where(&amp;)]:dark:bg-zinc-600 [:where(&amp;)]:text-zinc-800 [:where(&amp;)]:dark:text-white  after:absolute after:inset-0 after:inset-ring-[1px] after:inset-ring-black/7 dark:after:inset-ring-white/10 after:rounded-md">
                                                <img src="{{$player->user->gravatar}}"  alt="" onerror="this.onerror=null; this.src='http://the-social-game.test/build/images/default-avatar.png';" loading="lazy" class="rounded-[var(--avatar-radius)]">
                                            </div>

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
        </flux:tab.panel>

        <flux:tab.panel name="score">
            <x-card>
                <flux:heading size="lg">
                    @if ($this->game->status === 'ended')
                        <div class="flex items-center gap-2">
                            Score: {{ $this->team->hidden_score }}
                            @if ($this->team->hidden_score > $this->team->score)
                                <flux:text size="lg" class="text-purple-500 dark:text-purple-300">({{ $this->team->hidden_score - $this->team->score }} hidden)</flux:text>
                            @endif
                        </div>
                    @else
                        <div class="flex items-center gap-2">
                            Score: {{ $this->team->score }}
                            @if ($this->showHiddenPoints && $this->team->hidden_score !== $this->team->score)
                                <flux:text size="lg" class="text-purple-500 dark:text-purple-300">+{{ $this->team->hidden_score - $this->team->score }}</flux:text>
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
                                    <flux:table.cell class="flex items-start !px-0">
                                        {{ $entry['icon'] ?? '🐛' }}
                                    </flux:table.cell>
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
        </flux:tab.panel>
    </flux:tab.group>
</div>
