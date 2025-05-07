<div>
    @if (! $this->modifier->handler()::IS_SECRET)
        <flux:card>
            <flux:heading>You think you're so clever, don't you?</flux:heading>
        </flux:card>
    @else
        <x-game-components.modifier :modifier="$this->modifier" :modifierComponent="$this->modifier->handler()->frontendComponent($this->player)" />
    @endif
</div>
