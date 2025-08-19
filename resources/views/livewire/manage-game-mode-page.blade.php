<div x-data="{ game_type: $wire.entangle('game_type') }">
    <div class="mb-4">
        <flux:link :href="route('game-modes.index')" variant="ghost">
            Back to game modes
        </flux:link>
    </div>

    <x-card>
        <div class="flex flex-col gap-6">
            <flux:input wire:model="name" label="Name" />
            <flux:textarea wire:model="description" label="Description" />
            <flux:editor wire:model="pre_game_lobby_message" label="Pre Game Lobby Message" description="This message will be sent to players when they join the game lobby." />
            <flux:editor wire:model="footer_message" label="Footer Message" description="This message will be shown at the bottom of the game dashboard while the game is active." />
            <flux:editor wire:model="post_game_message" label="Post Game Message" description="This message will be shown at the end of the game." />
            <div class="flex flex-row gap-4">
                <flux:input wire:model="min_players" label="Minimum Players" placeholder="Can be left blank"/>
                <flux:input wire:model="max_players" label="Maximum Players" placeholder="Can be left blank"/>
            </div>

            <flux:field variant="inline">
                <flux:checkbox wire:model="is_public" />
                <flux:label>Visible to all paying users</flux:label>
                <flux:error name="is_public" />
            </flux:field>
            <flux:field variant="inline">
                <flux:checkbox wire:model="players_can_join_late" />
                <flux:label>Players can join late</flux:label>
                <flux:error name="players_can_join_late" />
            </flux:field>
            <flux:radio.group wire:model="game_type" label="Victory Condition">
                <flux:radio value="individual" label="Individual" checked />
                <flux:radio value="team" label="Team" />
            </flux:radio.group>
            <flux:radio.group wire:model="scoreboard_type" label="Scoreboard Type">
                @foreach (App\Livewire\ManageGameModePage::SCOREBOARD_TYPES as $key => $label)
                    <flux:radio value="{{ $key }}" label="{{ $label }}" />
                @endforeach
            </flux:radio.group>

            <x-button variant="primary" wire:click="saveGameMode">Save</x-button>
        </div>
    </x-card>

    @if ($this->game_mode)
        <x-card class="mt-4">
            <div class="flex flex-row justify-between items-center mb-4">
                <flux:heading size="lg">Templates</flux:heading>
                <flux:link :href="route('game-templates.create', ['game_mode' => $this->game_mode->id])">
                    <x-button variant="filled" icon="plus">New</x-button>
                </flux:link>
            </div>

            @if ($this->gameTemplates->count() > 0)
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Is public</flux:table.column>
                    </flux:table.columns>
                    @foreach ($this->gameTemplates as $t)
                        <flux:table.rows>
                            <flux:table.row>
                                <flux:table.cell>
                                    <flux:link :href="route('game-templates.show', ['game_mode' => $this->game_mode->id, 'game_template' => $t->id])">{{ $t->name }}</flux:link></flux:table.cell>
                                <flux:table.cell><flux:badge :color="$t->is_public ? 'green' : 'red'" size="sm" inset="top bottom">{{ $t->is_public ? 'Yes' : 'No' }}</flux:badge></flux:table.cell>
                            </flux:table.row>
                        </flux:table.rows>
                    @endforeach
                </flux:table>
            @else
                <flux:callout variant="warning" icon="exclamation-circle" heading="This mode is not playable until it has at least one template." />
            @endif
        </x-card>
    @endif

    @if ($this->game_mode !== null)
        <div class="mt-4 flex flex-row space-x-4 justify-end">
            @if ($this->game_mode->is_archived)
                <x-button variant="filled" wire:click="unarchiveGameMode">Unarchive</x-button>
            @else
                <flux:modal.trigger name="archive-game-mode">
                    <x-button variant="filled">Archive</x-button>
                </flux:modal.trigger>
            @endif
        </div>
    @endif

    <flux:modal name="archive-game-mode" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Archive this game mode</flux:heading>
                <flux:text class="mt-2">Are you sure you want to archive this game mode?</flux:text>
            </div>
            <x-button variant="danger" wire:click="archiveGameMode">Archive</x-button>
        </div>
    </flux:modal>


    <flux:toast />
</div>
