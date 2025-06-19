<div>
    <flux:card>
        <div class="flex flex-col space-y-6">
            <flux:heading size="lg">Create Game</flux:heading>
            <flux:radio.group label="Select Game Mode" variant="cards" wire:model="game_mode_id" class="flex-col">
                @foreach ($this->game_modes as $game_mode)
                    <flux:radio
                        name="game_mode_id"
                        value="{{ $game_mode->id }}"
                        label="{{ $game_mode->name }}"
                        description="{{ $game_mode->description }}"
                        checked
                    />
                @endforeach
            </flux:radio.group>
            <flux:checkbox label="Requires your approval to join" wire:model="requires_admin_approval_to_join" />
            <x-datetime
                label="Start time"
                name="game_start_timecode"
                wire:model="game_start_timecode"
                description="You can change this any time before the game starts."
                min="{{ now()->addMinute()->second(0)->toIsoString() }}"
                required
            />
            <div class="flex flex-col space-y-2">
                <flux:heading size="sm">Chat Link (optional)</flux:heading>
                <flux:text>Tell players where game chat will be held. Discord, Slack, Telegram all work well.</flux:text>
                <flux:input.group>
                    <flux:input.group.prefix>https://</flux:input.group.prefix>
                    <flux:input wire:model="social_link_url" placeholder="discord.gg/your-server" />
                </flux:input.group>
                <flux:error name="social_link_url" />
            </div>
        </div>
        <div class="flex justify-end">
            <flux:button variant="primary" wire:click="createGame" class="mt-4">Create Game</flux:button>
        </div>
    </flux:card>
</div>
