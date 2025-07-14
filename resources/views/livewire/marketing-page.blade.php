<div class="min-h-screen relative overflow-hidden">
    <!-- Floating particles -->
    <div class="absolute inset-0 overflow-hidden">
        @for($i = 0; $i < 20; $i++)
            <div class="absolute animate-float w-2 h-2 bg-gray-300/20 dark:bg-gray-600/20 rounded-full"
                 style="left: {{ rand(0, 100) }}%; top: {{ rand(0, 100) }}%; animation-delay: {{ rand(0, 5000) }}ms; animation-duration: {{ rand(3000, 8000) }}ms;"></div>
        @endfor
    </div>

    <div class="relative z-10 container mx-auto px-4 py-8">
        <!-- Header Section -->
        <div class="text-center mb-12 animate-fade-in-up">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4 leading-tight">
                Keep the fun going
            </h1>
            <p class="text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto leading-relaxed">
                Start social games that your friends can join for free. Create custom games for your next event.
            </p>
        </div>

        <!-- Main Cards -->
        <div class="grid lg:grid-cols-2 gap-6 max-w-6xl mx-auto">
            <!-- Personal Subscription Card -->
            <div class="group relative">
                <!-- Glow effect -->
                <div class="absolute -inset-1 bg-gradient-to-r from-purple-600 via-pink-600 to-blue-600 rounded-2xl blur opacity-20 group-hover:opacity-40 transition duration-1000 group-hover:duration-200 animate-tilt"></div>

                <div class="relative bg-white dark:bg-zinc-900 rounded-2xl p-6 md:p-8 border border-gray-200 dark:border-zinc-700 hover:border-gray-300 dark:hover:border-zinc-600 transition-all duration-500 group-hover:scale-105 shadow-lg group-hover:shadow-xl">
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-3">Host games</h2>

                    <!-- Price -->
                    <div class="mb-6">
                        <div class="flex items-baseline">
                            <span class="text-3xl md:text-4xl font-black text-gray-900 dark:text-white">$9.99</span>
                            <span class="text-lg text-gray-600 dark:text-gray-400 ml-2">/ year</span>
                        </div>
                    </div>

                    <!-- Features -->
                    <div class="space-y-3 mb-8">
                        <div class="flex items-start space-x-3">
                            <div class="w-5 h-5 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-gray-900 dark:text-white font-semibold text-sm">Friends join free</p>
                                <p class="text-gray-600 dark:text-gray-400 text-xs">Create games, and invite as many friends as you want.</p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3">
                            <div class="w-5 h-5 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-gray-900 dark:text-white font-semibold text-sm">4 to 100+ Players</p>
                                <p class="text-gray-600 dark:text-gray-400 text-xs">A fresh flavor for parties, game nights, or conventions</p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3">
                            <div class="w-5 h-5 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-gray-900 dark:text-white font-semibold text-sm">30min to Week-long Games</p>
                                <p class="text-gray-600 dark:text-gray-400 text-xs">Quick fun or epic adventures</p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3">
                            <div class="w-5 h-5 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-gray-900 dark:text-white font-semibold text-sm">Endless variety</p>
                                <p class="text-gray-600 dark:text-gray-400 text-xs">We have 10+ game modes in development. We've only just begun.</p>
                            </div>
                        </div>
                    </div>

                    <!-- CTA Button -->
                    <a href="{{ route('subscribe.index') }}">
                        <button class="w-full bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold py-3 px-6 rounded-xl transition-all duration-300 transform hover:scale-105 hover:shadow-lg group-hover:animate-pulse">
                            <span class="text-base">Subscribe Now</span>
                            <svg class="w-4 h-4 inline-block ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </button>
                    </a>
                </div>
            </div>

            <!-- Enterprise/Custom Card -->
            <div class="group relative">
                <!-- Glow effect -->
                <div class="absolute -inset-1 bg-gradient-to-r from-blue-600 via-cyan-600 to-teal-600 rounded-2xl blur opacity-20 group-hover:opacity-40 transition duration-1000 group-hover:duration-200 animate-tilt animation-delay-1000"></div>

                <div class="relative bg-white dark:bg-zinc-900 rounded-2xl p-6 md:p-8 border border-gray-200 dark:border-zinc-700 hover:border-gray-300 dark:hover:border-zinc-600 transition-all duration-500 group-hover:scale-105 shadow-lg group-hover:shadow-xl">
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-3">Custom</h2>

                    <!-- Price -->
                    <div class="mb-6">
                        <p class="text-cyan-600 dark:text-cyan-400 font-medium mt-1 text-sm">Tailored to your conference, retreat, offsite, or festival</p>
                    </div>

                    <!-- Features -->
                    <div class="space-y-3 mb-8">
                        <div class="flex items-start space-x-3">
                            <div class="w-5 h-5 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-gray-900 dark:text-white font-semibold text-sm">Your Brand, Your Colors</p>
                                <p class="text-gray-600 dark:text-gray-400 text-xs">Fully customized with your logo & branding</p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3">
                            <div class="w-5 h-5 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-gray-900 dark:text-white font-semibold text-sm">Inside Jokes & Custom Content</p>
                                <p class="text-gray-600 dark:text-gray-400 text-xs">Tailored schedule and content for your event</p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3">
                            <div class="w-5 h-5 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-gray-900 dark:text-white font-semibold text-sm">Player Email Collection</p>
                                <p class="text-gray-600 dark:text-gray-400 text-xs">Opt-in lead generation with user consent</p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3">
                            <div class="w-5 h-5 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-gray-900 dark:text-white font-semibold text-sm">Expert Support</p>
                                <p class="text-gray-600 dark:text-gray-400 text-xs">Dedicated support throughout your event</p>
                            </div>
                        </div>
                    </div>

                    <!-- CTA Button -->
                    <a href="mailto:john@thunk.dev?subject=Custom%20Game%20Event%20Inquiry&body=Hi!%20I'm%20interested%20in%20a%20custom%20game%20for%20my%20event.%20Here%20are%20some%20details:%0A%0AEvent%20Type:%20%0AExpected%20Attendees:%20%0ADate:%20%0ALocation:%20%0A%0APlease%20let%20me%20know%20how%20we%20can%20make%20this%20amazing!"
                       class="block w-full text-center bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white font-bold py-3 px-6 rounded-xl transition-all duration-300 transform hover:scale-105 hover:shadow-lg group-hover:animate-pulse">
                        <span class="text-base">Contact Us</span>
                        <svg class="w-4 h-4 inline-block ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        @keyframes tilt {
            0%, 50%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(1deg); }
            75% { transform: rotate(-1deg); }
        }

        @keyframes wiggle {
            0%, 7%, 14%, 21%, 100% { transform: rotate(0deg); }
            3.5% { transform: rotate(-2deg); }
            10.5%, 17.5% { transform: rotate(2deg); }
        }

        @keyframes fade-in-up {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-float { animation: float 6s ease-in-out infinite; }
        .animate-tilt { animation: tilt 10s infinite linear; }
        .animate-wiggle { animation: wiggle 2s ease-in-out infinite; }
        .animate-fade-in-up { animation: fade-in-up 1s ease-out forwards; }

        .animation-delay-500 { animation-delay: 0.5s; }
        .animation-delay-1000 { animation-delay: 1s; }
        .animation-delay-2000 { animation-delay: 2s; }
        .animation-delay-4000 { animation-delay: 4s; }
    </style>
</div>
