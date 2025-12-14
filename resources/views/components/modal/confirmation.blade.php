@props([
    'modal' => 'confirmation',
    'heading' => 'Are you sure?',
    'subheading' => null,
    'class_key' => null,
    'action' => null,
    'type' => null,
    'form' => null,
])

<x-modal.index>
    <x-card class="relative min-w-64">
        <div class="flex flex-col space-y-1">
            <x-forms.heading class="!text-lg">{{ $heading }}</x-forms.heading>

            <x-forms.subheading class="text-black">{{ $subheading ?? ucfirst($action) }}</x-forms.subheading>

            <div class="flex flex-wrap items-center gap-2 mt-4 justify-end">
                <x-button
                    variant="filled"
                    @click="{{ $modal }} = ! {{ $modal }}"
                >
                    No
                </x-button>
                <x-button
                    variant="primary"
                    x-show="{{ $modal }}"
                    wire:loading.attr="disabled"
                    wire:key="modal-{{ $class_key }}-{{ $action }}"
                    wire:click="callClassAction('{{ $action }}', '{{ $type }}', '{{ $class_key }}', {{ json_encode($form) }})"
                >
                    Yes
                </x-button>
            </div>
        </div>

        <x-modal.close-button :modal="$modal" />
    </x-card>
</x-modal.index>
