<x-layouts.app>
    <div class="flex flex-col gap-4">
        <x-card>
            @if($error === 'payment_error')
                <flux:heading class="text-red-600 dark:text-red-400">Payment Error</flux:heading>
                <flux:subheading>{{ $message }}</flux:subheading>
            @elseif($error === 'general_error')
                <flux:heading class="text-orange-600 dark:text-orange-400">Something Went Wrong</flux:heading>
                <flux:subheading>{{ $message }}</flux:subheading>
            @else
                <flux:heading class="text-gray-600 dark:text-red-400">Subscription Cancelled</flux:heading>
                <flux:subheading>{{ $message ?? 'Your subscription process was cancelled. No charges were made.' }}</flux:subheading>
            @endif

            <div class="mt-4 flex justify-end">
                <flux:button variant="primary" href="{{ route('marketing-page') }}">
                    Try Again
                </flux:button>
            </div>
        </x-card>
    </div>
</x-layouts.app>
