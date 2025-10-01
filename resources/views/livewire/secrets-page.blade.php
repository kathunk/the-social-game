<div>
    <div class="mb-4">
        <flux:link :href="route('game-dashboard', $this->game)" variant="ghost">
            Back to game
        </flux:link>
    </div>

    <x-game-components.form :form="$this->modifier->handler()->frontendComponentForDedicatedPage($this->player)" type="modifier" class_key="{{ $this->modifier->class_key }}" />
    <flux:error name="error" />
</div>
