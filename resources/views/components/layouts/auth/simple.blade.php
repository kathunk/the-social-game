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
                        <x-slot name="logo" class="size-6 bg-[var(--color-accent)] text-[var(--color-accent-foreground)]">
                            <x-icons.gossip/>
                        </x-slot>
                    </flux:brand>

                    <flux:brand name="The Social Game" href="{{ route('dashboard') }}" wire:navigate>
                        <x-slot name="logo">
                            <div class="w-6 h-6 flex items-center justify-center rounded overflow-hidden relative">
                                <div class="absolute inset-0 bg-[var(--color-accent)] text-[var(--color-accent-foreground)] flex items-center justify-center">
                                    <x-icons.gossip class="size-10 mt-3 pl-1 shrink-0" />
                                </div>
                            </div>
                        </x-slot>
                    </flux:brand>

                    <main class="relative flex flex-col size-32 rounded bg-[var(--color-accent)] text-[var(--color-accent-foreground)]">
                        <p class="text-center text-xs font-medium mt-0.5">The Social Game</p>
                        <div class="absolute overflow-hidden w-full h-full flex justify-center items-center">
                            <x-icons.gossip class="size-42 ml-4 mt-20 mx-auto shrink-0" />
                        </div>
                    </main>

                </div>
                <div class="flex flex-col gap-6">
                    {{ $slot }}
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
