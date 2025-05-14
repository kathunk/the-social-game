<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>

    @php
        $user = auth()->user();
    @endphp

    <body @class([
        'dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900' => ! $user?->currentPlayer?->team,
        "{$user->currentPlayer?->team?->theme()} bg-[var(--color-background)]" => $user->currentPlayer?->team?->theme(),
        'min-h-screen',
    ])>
        @if ($user)
            <flux:sidebar sticky stashable class="border-r border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">

            <div class="flex items-center">
                <flux:brand name="The Social Game" href="{{ route('dashboard') }}" wire:navigate>
                    <x-slot name="logo">
                        <div class="rounded w-12 bg-[var(--color-accent)] text-[var(--color-accent-foreground)]">
                            <x-app-logo />
                        </div>
                    </x-slot>
                </flux:brand>
                <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />
            </div>

            <flux:navlist variant="outline">
                <flux:navlist.group :heading="__('Games')" class="grid">
                    <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>{{ __('Home') }}</flux:navlist.item>
                    @if ($user?->is_member)
                        <flux:navlist.item icon="plus" :href="route('create-game')" :current="request()->routeIs('create-game')" wire:navigate>{{ __('Create Game') }}</flux:navlist.item>
                    @endif
                    @if ($user?->is_super_admin)
                        <flux:navlist.item icon="cog" :href="route('game-templates.index')" :current="request()->routeIs('game-templates.index')" wire:navigate>{{ __('Manage Game Templates') }}</flux:navlist.item>
                    @endif
                </flux:navlist.group>
            </flux:navlist>

            <flux:spacer />

            <!-- Desktop User Menu -->
            <flux:dropdown position="bottom" align="start">
                <flux:profile
                    :name="$user?->name"
                    :initials="$user?->initials()"
                    icon-trailing="chevrons-up-down"
                />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-600 dark:text-white"
                                    >
                                        {{ $user?->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold dark:text-zinc-100">{{ $user?->name }}</span>
                                    <span class="truncate text-xs dark:text-zinc-200">{{ $user?->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    @if (!$user?->currentPlayer)
                        <flux:menu.separator />

                        <flux:menu.radio.group>
                            <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                        </flux:menu.radio.group>
                    @endif

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()?->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg shadow-md">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-600 dark:text-white shadow-md"
                                    >
                                        {{ $user?->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold dark:text-zinc-100">{{ $user?->name }}</span>
                                    <span class="truncate text-xs dark:text-zinc-200">{{ $user?->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>
        @endif

        {{ $slot }}

        @fluxScripts
    </body>
</html>
