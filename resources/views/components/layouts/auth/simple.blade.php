<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-sm flex-col gap-2">
                <div class="flex items-center justify-center">
                    <flux:brand name="The Social Game" href="{{ route('dashboard') }}" wire:navigate>
                        <x-slot name="logo">
                            <div class="rounded w-12 bg-[var(--color-accent)] text-[var(--color-accent-foreground)]">
                                <x-app-logo />
                            </div>
                        </x-slot>
                    </flux:brand>
                </div>
                <div class="flex flex-col gap-6">
                    {{ $slot }}
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
