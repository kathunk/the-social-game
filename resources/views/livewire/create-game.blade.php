<div>
    <flux:card>
        <div class="flex flex-col space-y-4">
            <flux:heading>Create Game</flux:heading>
            <flux:radio.group label="Select Game Variant">
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
                required
            />
        </div>
    </flux:card>
</div>
