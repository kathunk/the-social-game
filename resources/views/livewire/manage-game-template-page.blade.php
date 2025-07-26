<div>
    <div class="mb-4">
        <flux:link :href="route('game-modes.index')" variant="ghost">
            Back to game modes
        </flux:link>
    </div>

    <x-card>
        <div class="flex flex-col gap-6">
            <flux:heading>Template for <flux:link :href="route('game-modes.show', $this->game_mode->id)">{{ $this->game_mode->name }}</flux:link></flux:heading>
            <flux:input wire:model="name" label="Name" />

            <flux:input wire:model="team_names" label="Team Names" placeholder="Comma separated. Leave blank for no teams."/>
            <flux:field variant="inline">
                <flux:checkbox wire:model="is_public" />
                <flux:label>Visible to all paying users</flux:label>
                <flux:error name="is_public" />
            </flux:field>

            <div class="flex flex-row gap-2 items-center">
                <flux:heading size="sm">Modifiers</flux:heading>
                <flux:modal.trigger name="show-modifiers">
                    <flux:link class="text-sm">show all</flux:link>
                </flux:modal.trigger>
            </div>
            <div x-data="{ editingModifiers: false }">
                <div x-show="! editingModifiers" class="flex flex-row gap-2 items-center">
                    <flux:text>
                        @if (count($this->modifiers) > 0)
                            {{ collect($this->modifiers)->map(fn($m) => App\Modifiers\ModifierRegistry::retrieveFromKey($m)::NAME)->join(', ') }}
                        @else
                            No modifiers selected
                        @endif
                    </flux:text>
                    <x-button size="xs" icon="pencil" variant="subtle" @click="editingModifiers = true"></x-button>
                </div>
                <div x-show="editingModifiers">
                    <flux:select variant="listbox" multiple searchable wire:model="modifiers">
                        @foreach ($this->allModifiers as $modifier)
                            <flux:select.option value="{{ $modifier::key() }}">{{ $modifier::NAME }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <x-button size="xs" icon="check" class="mt-2 w-full" variant="filled" @click="editingModifiers = false" wire:click="saveTemplate">done editing</x-button>
                </div>
            </div>


            <flux:table>
                <flux:table.columns>
                    <flux:table.column>
                        <div class="flex flex-col gap-2">
                            <div class="flex flex-row gap-2">
                                <flux:heading size="sm">Challenges</flux:heading>
                                <flux:modal.trigger name="show-challenges">
                                    <flux:link>show all</flux:link>
                                </flux:modal.trigger>
                            </div>
                            <flux:text class="text-xs">If multiple are selected for a row, one will be chosen at random.</flux:text>
                        </div>
                    </flux:table.column>
                    <flux:table.column>
                        <div class="flex flex-col gap-2">
                            <flux:heading size="sm">Duration</flux:heading>
                            <flux:text class="text-xs">(minutes)</flux:text>
                        </div>
                    </flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->challenges as $i => $challenge)
                        <flux:table.row x-data="{ editing: false }" wire:key="challenge-{{ $i }}">
                            <flux:table.cell>
                                <template x-if="!editing">
                                    <div class="flex flex-row space-x-2 items-center">
                                        <flux:text class="whitespace-normal break-words">{{ collect($challenge['challenge_keys'])->map(fn($key) => App\Challenges\ChallengeRegistry::retrieveFromKey($key)::NAME)->join(', ') }}</flux:text>
                                        <x-button size="xs" icon="pencil" variant="subtle" @click="editing = true"></x-button>
                                    </div>
                                </template>

                                <template x-if="editing">
                                    <div class="flex flex-col space-y-2">
                                        <flux:select variant="listbox" multiple searchable wire:model="challenges.{{ $i }}.challenge_keys">
                                            @foreach ($this->allChallenges as $challenge)
                                                <flux:select.option value="{{ $challenge::key() }}">{{ $challenge::NAME }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <x-button size="xs" icon="check" variant="filled" @click="editing = false" wire:click="saveTemplate">done editing</x-button>
                                    </div>
                                </template>
                            </flux:table.cell>
                            <flux:table.cell class="max-w-4">
                                <flux:input wire:model="challenges.{{ $i }}.duration"/>
                            </flux:table.cell>
                            <flux:table.cell>
                                <x-button size="xs" icon="trash" variant="subtle" wire:click="removeChallenge({{ $i }})"></x-button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                    <flux:table.row>
                        <flux:table.cell>
                            <x-button variant="filled" size="sm" icon="plus" wire:click="addChallenge">Add Challenge</x-button>
                        </flux:table.cell>
                    </flux:table.row>
                </flux:table.rows>
            </flux:table>

            <flux:error name="challenges" />
            <flux:error name="error" />

            <x-button variant="primary" wire:click="saveTemplate">Save</x-button>
        </div>
    </x-card>

    @if ($this->game_template !== null)
        <div class="mt-4 flex flex-row space-x-4 justify-end">
            <x-button variant="filled" wire:click="duplicateTemplate">Create duplicate</x-button>
            @if ($this->game_template->is_archived)
                <x-button variant="filled" wire:click="unarchiveTemplate">Unarchive</x-button>
            @else
                <flux:modal.trigger name="archive-template">
                    <x-button variant="filled">Archive</x-button>
                </flux:modal.trigger>
            @endif
        </div>
    @endif

    <flux:modal name="show-challenges" class="w-full">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Challenges</flux:heading>
                <flux:text class="mt-2">This is filtered by the game type selected.</flux:text>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Description</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->allChallenges as $challenge)
                        <flux:table.row>
                            <flux:table.cell>{{ $challenge::NAME }}</flux:table.cell>
                            <flux:table.cell class="whitespace-normal break-words">{{ $challenge::DESCRIPTION }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:modal>

    <flux:modal name="show-modifiers" class="w-full">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Modifiers</flux:heading>
                <flux:text class="mt-2">This is filtered by the game type selected.</flux:text>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Description</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->allModifiers as $modifier)
                        <flux:table.row>
                            <flux:table.cell>{{ $modifier::NAME }}</flux:table.cell>
                            <flux:table.cell class="whitespace-normal break-words">{{ $modifier::DESCRIPTION }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </div>
    </flux:modal>

    <flux:modal name="archive-template" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Archive this template</flux:heading>
                <flux:text class="mt-2">Are you sure you want to archive this template? This cannot be undone.</flux:text>
            </div>
            <x-button variant="danger" wire:click="archiveTemplate">Archive</x-button>
        </div>
    </flux:modal>
</div>
