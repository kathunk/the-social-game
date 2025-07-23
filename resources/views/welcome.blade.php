<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="relative min-h-screen bg-bold-orange text-pale">
        <!-- Header positioned absolutely at the top -->
        <header class="absolute top-0 left-0 right-0 p-6 lg:p-8 text-sm">
            @if (Route::has('login'))
                <nav class="flex items-center justify-end gap-4">
                    @auth
                        <a
                            href="{{ url('/dashboard') }}"
                            class="inline-block px-5 py-1.5 border-[#19140035] border hover:border-[#3E3E3A] rounded-sm text-sm leading-normal"
                        >
                            Dashboard
                        </a>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="inline-block px-5 py-1.5 border border-transparent hover:border-[#19140035] hover:border-[#3E3E3A] rounded-sm text-sm leading-normal"
                        >
                            Log in
                        </a>

                        @if (Route::has('register'))
                            <a
                                href="{{ route('register') }}"
                                class="inline-block px-5 py-1.5 border-[#19140035] hover:border-[#1915014a] border hover:border-[#3E3E3A] rounded-sm text-sm leading-normal">
                                Register
                            </a>
                        @endif
                    @endauth
                </nav>
            @endif
        </header>

        <!-- Main content centered -->
        <div class="flex items-center justify-center min-h-screen p-6 lg:p-8">
            <div class="flex flex-col items-center w-full max-w-4xl">
                <div class="flex flex-col items-center justify-center w-full transition-opacity opacity-100 duration-750 lg:grow starting:opacity-0">
                    <div class="w-full max-w-xs sm:max-w-sm mx-auto">
                        <x-icons.big-logo class="w-full h-auto" />
                    </div>
                    <div class="w-screen lg:w-full -ml-6 lg:ml-0 -mr-6 lg:mr-0">
                        <div class="w-full max-w-sm sm:max-w-md mx-auto">
                            <x-icons.gaggle class="w-full h-auto" />
                        </div>
                    </div>
                </div>

                @if (Route::has('login'))
                    <div class="h-14.5 hidden lg:block"></div>
                @endif
            </div>
        </div>
    </body>
</html>
