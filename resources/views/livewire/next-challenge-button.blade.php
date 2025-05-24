<div>
    @if($this->show)
        <flux:button
            wire:click="nextChallenge"
            class="!bg-gradient-to-tr !from-indigo-300 !to-green-300 !w-fit !text-gray-700"
            icon="forward"
            size="sm"
        >
            Next Challenge
        </flux:button>
    @endif
</div>
