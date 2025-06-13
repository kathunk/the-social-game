<x-layouts.app>
    <div class="flex flex-col gap-4">
        <flux:card>
            <flux:heading class="text-gray-600 dark:text-red-400">Subscription Cancelled</flux:heading>
            <flux:subheading>Your subscription process was cancelled. No charges were made.</flux:subheading>

            <div class="mt-4 flex justify-end">
                <flux:button variant="primary" href="{{ route('subscribe.index') }}">
                    Try Again
                </flux:button>
            </div>
        </flux:card>
    </div>
</x-layouts.app>
