<div>
    <flux:card>
        <div class="flex flex-col space-y-6">
            <flux:heading size="lg">Create Game</flux:heading>
            <flux:radio.group label="Select Game Variant" wire:model="game_template_id">
                @foreach ($this->game_templates as $game_template)
                    <flux:radio
                        name="game_template_id"
                        value="{{ $game_template->id }}"
                        label="{{ $game_template->name }}"
                        description="{{ $game_template->description }}"
                        checked
                    />
                @endforeach
            </flux:radio.group>
            <flux:checkbox label="Open to all" wire:model="is_public" />
            <flux:checkbox label="Requires your approval to join" wire:model="requires_admin_approval_to_join" />
            <x-datetime
                label="Start time"
                name="game_start_timecode"
                wire:model="game_start_timecode"
                description="You can change this any time before the game starts."
                min="{{ now()->addMinute()->second(0)->toIsoString() }}"
                required
            />
        </div>
        <div class="flex justify-end">
            <flux:button variant="primary" wire:click="createGame" class="mt-4">Create Game</flux:button>
        </div>
    </flux:card>
</div>
