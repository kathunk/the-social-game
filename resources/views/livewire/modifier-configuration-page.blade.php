<div>
    <div class="mb-4">
        <flux:link variant="ghost" href="{{ route('pre-game-lobby', ['game' => $this->game]) }}">
            Back to game
        </flux:link>
    </div>

    @if ($this->secretCodeConfiguration)
        <x-card>
            <div class="flex flex-col gap-4">
                <flux:heading>Secret Codes</flux:heading>

                <flux:textarea wire:model="secretCodes" />
                <flux:error name="secretCodes" />

                <flux:button variant="primary" wire:click="saveSecretCodes">Save</flux:button>
            </div>
        </x-card>
    @endif

    <flux:toast />
</div>
