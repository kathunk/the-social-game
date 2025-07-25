<div class="{{ $this->show ? 'block' : 'hidden' }}">
    <flux:button
        wire:click="nextChallenge"
        class="!bg-gradient-to-tr !from-indigo-300 !to-green-300 !w-fit"
        icon="forward"
        size="sm"
    >
        Next Challenge
    </flux:button>
</div>
