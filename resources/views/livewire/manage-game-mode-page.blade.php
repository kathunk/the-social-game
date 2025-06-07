<div x-data="{ game_type: $wire.entangle('game_type') }">
    <flux:card>
        <div class="flex flex-col gap-6">
            <flux:input wire:model="name" label="Name" />
            <flux:textarea wire:model="description" label="Description" />
            <flux:editor wire:model="pre_game_lobby_message" label="Pre Game Lobby Message" description="This message will be sent to players when they join the game lobby." />
            <div class="flex flex-row gap-4">
                <flux:input wire:model="min_players" label="Minimum Players" placeholder="Can be left blank"/>
                <flux:input wire:model="max_players" label="Maximum Players" placeholder="Can be left blank"/>
            </div>

            <flux:input wire:model="team_names" label="Team Names" placeholder="Comma separated. Leave blank for no teams."/>
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

            <flux:button variant="primary" wire:click="saveGameMode">Save</flux:button>
        </div>
    </flux:card>

    <flux:modal name="archive-game-mode" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Archive this game mode</flux:heading>
                <flux:text class="mt-2">Are you sure you want to archive this game mode?</flux:text>
            </div>
            <flux:button variant="danger" wire:click="archiveGameMode">Archive</flux:button>
        </div>
    </flux:modal>
</div>

