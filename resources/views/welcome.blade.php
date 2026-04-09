<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <x-meta-tags
        title="The Social Game™ — Free social games for groups of any size"
        description="Like Jackbox, but smarter, free, and web-based. Play smart social games with your friends, your party, or your whole conference."
        url="{{ request()->url() }}"
    />

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fredoka:400,500,600" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @keyframes float-slow {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-12px); }
        }
        @keyframes fade-in-up {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4); }
            50% { box-shadow: 0 0 0 12px rgba(255, 255, 255, 0); }
        }
        .animate-float-slow { animation: float-slow 5s ease-in-out infinite; }
        .animate-fade-in-up { animation: fade-in-up 0.8s ease-out forwards; }
        .animate-pulse-glow { animation: pulse-glow 2.5s infinite; }
        .delay-100 { animation-delay: 0.1s; opacity: 0; }
        .delay-200 { animation-delay: 0.2s; opacity: 0; }
        .delay-300 { animation-delay: 0.3s; opacity: 0; }

        /* Phone frame for game mockups */
        .phone-frame {
            border-radius: 2rem;
            border: 8px solid #1a1a1a;
            background: #fff;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3), 0 0 0 2px rgba(255,255,255,0.1);
            overflow: hidden;
            max-width: 280px;
            margin: 0 auto;
        }
        .phone-frame::before {
            content: '';
            display: block;
            height: 18px;
            background: #1a1a1a;
            border-bottom-left-radius: 14px;
            border-bottom-right-radius: 14px;
            width: 110px;
            margin: -2px auto 0;
        }
    </style>
</head>
<body class="relative min-h-screen bg-bold-orange text-pale font-sans">
    <!-- HEADER -->
    <header class="absolute top-0 left-0 right-0 p-6 lg:p-8 text-sm z-20">
        @if (Route::has('login'))
            <nav class="flex items-center justify-end gap-3">
                @auth
                    <a href="{{ url('/dashboard') }}"
                       class="inline-block px-5 py-2 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-lg text-sm font-semibold transition-colors">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="inline-block px-5 py-2 hover:bg-white/10 rounded-lg text-sm font-semibold transition-colors">
                        Log in
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}"
                           class="inline-block px-5 py-2 bg-white text-bold-orange hover:scale-105 rounded-lg text-sm font-bold transition-transform">
                            Sign up
                        </a>
                    @endif
                @endauth
            </nav>
        @endif
    </header>

    <!-- HERO -->
    <section class="relative overflow-hidden pt-24 pb-20 lg:pt-32 lg:pb-28">
        <!-- Floating background bits -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            @for($i = 0; $i < 12; $i++)
                <div class="absolute w-2 h-2 bg-white/10 rounded-full animate-float-slow"
                     style="left: {{ rand(5, 95) }}%; top: {{ rand(5, 95) }}%; animation-delay: {{ rand(0, 4000) }}ms; animation-duration: {{ rand(4000, 7000) }}ms;"></div>
            @endfor
        </div>

        <div class="relative z-10 max-w-6xl mx-auto px-6">
            <div class="grid md:grid-cols-2 gap-8 md:gap-12 items-center">
                {{-- Left: logo + gaggle stack --}}
                <div class="flex flex-col items-center md:items-end animate-fade-in-up">
                    <div class="w-full max-w-xs sm:max-w-sm md:max-w-full">
                        <x-icons.big-logo class="w-full h-auto" />
                    </div>
                    <div class="w-full max-w-sm sm:max-w-md md:max-w-full -mt-2">
                        <x-icons.gaggle class="w-full h-auto" />
                    </div>
                </div>

                {{-- Right: headline + CTAs --}}
                <div class="text-center md:text-left">
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold leading-tight mb-6 animate-fade-in-up delay-100">
                        Smart social games<br />for groups of any size.
                    </h1>

                    <p class="text-lg md:text-xl opacity-90 max-w-xl md:max-w-none leading-relaxed mb-8 mx-auto md:mx-0 animate-fade-in-up delay-200">
                        Play on your phone with 4 friends or 400. No download. Web-based and free.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-3 justify-center md:justify-start animate-fade-in-up delay-300">
                        @auth
                            <a href="{{ url('/dashboard') }}"
                               class="inline-block bg-white text-bold-orange font-bold py-4 px-8 rounded-xl hover:scale-105 transition-transform animate-pulse-glow">
                                Open dashboard
                            </a>
                        @else
                            <a href="{{ route('register') }}"
                               class="inline-block bg-white text-bold-orange font-bold py-4 px-8 rounded-xl hover:scale-105 transition-transform animate-pulse-glow">
                                Create a free account
                            </a>
                            <a href="{{ route('login') }}"
                               class="inline-block border-2 border-white/40 hover:border-white text-white font-bold py-4 px-8 rounded-xl transition-colors">
                                Log in
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- VALUE PROPS STRIP -->
    <section class="relative bg-pale text-zinc-900 py-12 lg:py-16">
        <div class="max-w-5xl mx-auto px-6 grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
            <div>
                <div class="text-3xl mb-2">📱</div>
                <h3 class="font-bold text-base mb-1">Mobile-first</h3>
                <p class="text-sm opacity-70 leading-relaxed">Designed for thumbs. No download required. Just open a link and play.</p>
            </div>
            <div>
                <div class="text-3xl mb-2">🎯</div>
                <h3 class="font-bold text-base mb-1">Built for groups</h3>
                <p class="text-sm opacity-70 leading-relaxed">From a 4-player game night to a 400-person conference. Same site, no friction.</p>
            </div>
            <div>
                <div class="text-3xl mb-2">⏱️</div>
                <h3 class="font-bold text-base mb-1">Short or long</h3>
                <p class="text-sm opacity-70 leading-relaxed">Quick 10-minute rounds, or week-long campaigns. Pick your vibe.</p>
            </div>
        </div>
    </section>

    <!-- TIER LIST GAME SHOWCASE -->
    <section class="relative bg-bold-orange text-pale py-16 lg:py-24">
        <div class="max-w-5xl mx-auto px-6 grid md:grid-cols-2 gap-12 items-center">
            <div class="order-2 md:order-1">
                <h2 class="text-3xl md:text-4xl font-bold mb-4 leading-tight">Tier List</h2>
                <p class="text-base md:text-lg opacity-90 mb-4 leading-relaxed">
                    Build your perfect tier list. Then guess how <em>your friends</em> ranked theirs.
                    The closer your guess, the more you score.
                </p>
                <p class="text-base md:text-lg opacity-90 leading-relaxed">
                    Start arguments about why velvet is an A-tier texture, or why Newsies should not be
                    on anyone's top movie list.
                </p>
                <div class="mt-6 flex gap-2 flex-wrap">
                    <span class="text-xs font-semibold bg-white/15 rounded-full px-3 py-1">2–10 players</span>
                    <span class="text-xs font-semibold bg-white/15 rounded-full px-3 py-1">~15 minutes</span>
                    <span class="text-xs font-semibold bg-white/15 rounded-full px-3 py-1">Drag &amp; drop</span>
                </div>
            </div>

            <div class="order-1 md:order-2">
                {{-- Mock screenshot of the tier list guessing UI --}}
                <div class="phone-frame animate-float-slow">
                    <div class="p-3 bg-white">
                        <div class="text-center mb-2">
                            <div class="text-xs font-bold text-faded-gray uppercase tracking-wide">Round 2 of 4</div>
                            <div class="text-sm font-semibold mt-1">Rank Jed's<br />favorite snacks</div>
                        </div>
                        <div class="rounded-lg p-3 bg-gradient-to-b from-green-200 via-yellow-100 to-red-100">
                            <ul class="flex flex-col gap-2">
                                @php
                                    $mockTiers = [
                                        ['letter' => 'A', 'value' => 'Hot Cheetos'],
                                        ['letter' => 'B', 'value' => 'Trail mix'],
                                        ['letter' => 'C', 'value' => 'Sour gummies'],
                                        ['letter' => 'D', 'value' => 'Apple slices'],
                                        ['letter' => 'F', 'value' => 'Plain rice cake'],
                                    ];
                                @endphp
                                @foreach ($mockTiers as $tier)
                                    <li class="w-full p-2.5 text-xs flex flex-row justify-between items-center bg-white/60 rounded shadow-sm">
                                        <div class="flex flex-row gap-2 items-center">
                                            <span class="inline-flex items-center justify-center w-5 h-5 rounded text-[10px] font-bold bg-zinc-800 text-white">{{ $tier['letter'] }}</span>
                                            <span class="font-medium text-zinc-800">{{ $tier['value'] }}</span>
                                        </div>
                                        <span class="text-zinc-400 text-base leading-none">⋮⋮</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- PECKING ORDER GAME SHOWCASE -->
    <section class="relative bg-pale text-zinc-900 py-16 lg:py-24">
        <div class="max-w-5xl mx-auto px-6 grid md:grid-cols-2 gap-12 items-center">
            <div>
                {{-- Mock screenshot of pecking order voting --}}
                <div class="phone-frame animate-float-slow" style="animation-delay: 1s;">
                    <div class="p-3 bg-white text-zinc-900">
                        <div class="text-center mb-3">
                            <div class="text-xs font-bold text-zinc-400 uppercase tracking-wide">Round 3 of 11</div>
                            <div class="text-sm font-semibold mt-1">Cast your votes</div>
                        </div>

                        <div class="space-y-3">
                            <div>
                                <label class="block text-[10px] font-semibold text-zinc-500 uppercase mb-1">Upvote</label>
                                <div class="border-2 border-green-500 rounded-lg p-2 flex items-center justify-between bg-green-50">
                                    <span class="font-semibold text-sm">Mira</span>
                                    <span class="text-green-600 text-base">▲</span>
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] font-semibold text-zinc-500 uppercase mb-1">Downvote</label>
                                <div class="border-2 border-red-500 rounded-lg p-2 flex items-center justify-between bg-red-50">
                                    <span class="font-semibold text-sm">Theo</span>
                                    <span class="text-red-600 text-base">▼</span>
                                </div>
                            </div>

                            <button class="w-full mt-2 bg-zinc-900 text-white font-bold py-2 rounded-lg text-xs">
                                Submit votes
                            </button>
                        </div>

                        <div class="mt-4 pt-3 border-t border-zinc-100">
                            <div class="text-[10px] font-semibold text-zinc-500 uppercase mb-1">Pecking order</div>
                            <div class="space-y-1">
                                <div class="flex justify-between text-xs"><span>1. Sasha</span><span class="text-zinc-400">+12</span></div>
                                <div class="flex justify-between text-xs"><span>2. Mira</span><span class="text-zinc-400">+8</span></div>
                                <div class="flex justify-between text-xs"><span>3. You</span><span class="text-zinc-400">+5</span></div>
                                <div class="flex justify-between text-xs"><span>4. Theo</span><span class="text-zinc-400">−3</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <h2 class="text-3xl md:text-4xl font-bold mb-4 leading-tight">Pecking Order</h2>
                <p class="text-base md:text-lg opacity-80 mb-4 leading-relaxed">
                    Each round, you upvote and downvote your opponents — but you also predict how
                    everyone <em>else</em> will vote. Outsmart the room and you'll quietly accumulate hidden points
                    that get revealed at the end.
                </p>
                <p class="text-base md:text-lg opacity-80 leading-relaxed">
                    A popularity contest for the truly devious.
                </p>

                <div class="mt-6 space-y-3">
                    <div class="flex items-start gap-3">
                        <span class="text-xl leading-none">🩸</span>
                        <div>
                            <div class="font-bold text-sm">Blood Oaths</div>
                            <div class="text-sm opacity-70">A variant where you can secretly ally with one player. Trust no one.</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="text-xl leading-none">👑</span>
                        <div>
                            <div class="font-bold text-sm">King Maker</div>
                            <div class="text-sm opacity-70">Resign early to give your points away — anoint a new king or nuke your opponents. Great for large groups.</div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex gap-2 flex-wrap">
                    <span class="text-xs font-semibold bg-zinc-100 rounded-full px-3 py-1">4–12 players</span>
                    <span class="text-xs font-semibold bg-zinc-100 rounded-full px-3 py-1">~20 minutes</span>
                    <span class="text-xs font-semibold bg-zinc-100 rounded-full px-3 py-1">Strategy</span>
                </div>
            </div>
        </div>
    </section>

    <!-- "MORE COMING" + FINAL CTA -->
    <section class="bg-bold-orange text-pale py-16 lg:py-24">
        <div class="max-w-3xl mx-auto px-6 text-center">
            <h2 class="text-2xl md:text-3xl font-bold mb-3">And there's more cooking.</h2>
            <p class="text-base md:text-lg opacity-90 mb-8 leading-relaxed">
                We have a growing roster of game modes — short ones for parties, long ones for conferences,
                and weird experiments in between. New games drop on a regular cadence.
            </p>

            @auth
                <a href="{{ url('/dashboard') }}"
                   class="inline-block bg-white text-bold-orange font-bold py-4 px-8 rounded-xl hover:scale-105 transition-transform">
                    Open dashboard →
                </a>
            @else
                <a href="{{ route('register') }}"
                   class="inline-block bg-white text-bold-orange font-bold py-4 px-8 rounded-xl hover:scale-105 transition-transform">
                    Create a free account →
                </a>
                <p class="mt-4 text-xs opacity-80">No credit card. No app. No nonsense.</p>
            @endauth
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="bg-zinc-900 text-pale/70 py-8">
        <div class="max-w-3xl mx-auto px-6 text-xs leading-relaxed">
            <h2 class="font-semibold tracking-wide uppercase text-[11px] mb-2">Privacy Policy</h2>
            <p class="mb-4">
                We collect only the information needed to create your account and run the game.
                We do not sell your data. We do not use cookies. Enjoy the game!
            </p>
            <p class="text-[10px] opacity-60">© {{ date('Y') }} Catacombian Games. The Social Game™.</p>
        </div>
    </footer>
</body>
</html>
