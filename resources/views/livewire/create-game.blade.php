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
            <x-datetime
                label="Start time"
                name="game_start_timecode"
                id="game_start_timecode"
                wire:model="game_start_timecode"
                min="{{ now()->addMinute()->second(0)->toIsoString() }}"
                :default="now()->addHour()->toISOString()" 
                required
            />
        </div>
        <div class="flex justify-end">
            <flux:button variant="primary" wire:click="createGame" class="mt-4">Create Game</flux:button>
        </div>
    </flux:card>
</div>
