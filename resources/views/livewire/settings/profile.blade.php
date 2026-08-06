<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use App\Events\UserSubscribedToNewsletter;
use App\Events\UserUnsubscribedFromNewsletter;
use App\Events\UserUpdatedNotificationPreferences;

new class extends Component {
    public string $name = '';
    public string $email = '';
    public bool $has_active_game = false;
    public bool $subscribed_to_newsletter;

    // Notification preferences
    public ?string $phone_number = null;
    public ?string $default_discord_webhook = null;
    public bool $notify_on_game_start = true;
    public bool $notify_before_challenge_end = true;
    public bool $notify_on_challenge_start = true;
    public bool $notify_on_game_end = true;
    public bool $notify_via_email = true;
    public bool $notify_via_sms = true;
    public bool $notify_via_discord = true;
    public bool $notify_via_telegram = false;
    public bool $notify_via_browser = false;
    public ?string $telegram_chat_id = null;
    public ?string $telegram_username = null;
    public bool $has_push_subscription = false;
    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->has_active_game = Auth::user()->has_active_game;
        $this->subscribed_to_newsletter = Auth::user()->subscribed_to_newsletter;

        // Load notification preferences
        $this->phone_number = Auth::user()->phone_number;
        $this->default_discord_webhook = Auth::user()->default_discord_webhook;
        $this->telegram_chat_id = Auth::user()->telegram_chat_id;
        $this->telegram_username = Auth::user()->telegram_username;
        $this->has_push_subscription = Auth::user()->hasPushSubscriptions();

        $prefs = Auth::user()->notification_preferences ?? [];
        $this->notify_on_game_start = $prefs['notify_on_game_start'] ?? false;
        $this->notify_before_challenge_end = $prefs['notify_before_challenge_end'] ?? false;
        $this->notify_on_challenge_start = $prefs['notify_on_challenge_start'] ?? false;
        $this->notify_on_game_end = $prefs['notify_on_game_end'] ?? false;

        $this->notify_via_email = $prefs['notify_via_email'] ?? false;
        $this->notify_via_sms = $prefs['notify_via_sms'] ?? false;
        $this->notify_via_discord = $prefs['notify_via_discord'] ?? false;
        $this->notify_via_telegram = $prefs['notify_via_telegram'] ?? false;
        $this->notify_via_browser = $prefs['notify_via_browser'] ?? false;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id)
            ],

            // Notification preferences validation
            'phone_number' => ['nullable', 'string', 'max:20'],
            'default_discord_webhook' => ['nullable', 'url', 'max:500'],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($this->subscribed_to_newsletter !== $user->subscribed_to_newsletter) {
            $this->subscribed_to_newsletter
                ? UserSubscribedToNewsletter::fire(user_id: $user->id)
                : UserUnsubscribedFromNewsletter::fire(user_id: $user->id);
        }

        // Check if notification preferences changed
        $currentPrefs = $user->notification_preferences ?? [];
        $newPrefs = [
            'notify_on_game_start' => $this->notify_on_game_start,
            'notify_before_challenge_end' => $this->notify_before_challenge_end,
            'notify_on_challenge_start' => $this->notify_on_challenge_start,
            'notify_on_game_end' => $this->notify_on_game_end,
            'notify_via_email' => $this->notify_via_email,
            'notify_via_sms' => $this->notify_via_sms,
            'notify_via_discord' => $this->notify_via_discord,
            'notify_via_telegram' => $this->notify_via_telegram,
            'notify_via_browser' => $this->notify_via_browser,
        ];

        $prefsChanged = $currentPrefs !== $newPrefs ||
                        $this->phone_number !== $user->phone_number ||
                        $this->default_discord_webhook !== $user->default_discord_webhook;

        if ($prefsChanged) {
            UserUpdatedNotificationPreferences::fire(
                user_id: $user->id,
                phone_number: $this->phone_number,
                default_discord_webhook: $this->default_discord_webhook,
                notification_preferences: $newPrefs
            );
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Generate a Telegram verification link.
     */
    public function generateTelegramLink(): string
    {
        $botUsername = config('services.telegram.bot_username');

        if (empty($botUsername)) {
            return '';
        }

        $user = Auth::user();

        // Reuse existing token if present, or generate new one
        if (empty($user->telegram_verification_token)) {
            $token = \Illuminate\Support\Str::random(32);
            $user->telegram_verification_token = $token;
            $user->save();
        } else {
            $token = $user->telegram_verification_token;
        }

        return "https://t.me/{$botUsername}?start={$token}";
    }

    /**
     * Disconnect the Telegram account.
     */
    public function disconnectTelegram(): void
    {
        $user = Auth::user();
        $user->telegram_chat_id = null;
        $user->telegram_username = null;
        $user->telegram_verification_token = null;
        $user->telegram_connected_at = null;
        $user->save();

        $this->telegram_chat_id = null;
        $this->telegram_username = null;
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input :disabled="$this->has_active_game" wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            @if ($this->has_active_game)
                <flux:text>
                    {{ __('You cannot change your name while you have an active game.') }}
                </flux:text>
            @endif

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail &&! auth()->user()->hasVerifiedEmail())
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center justify-between">
                <flux:checkbox
                    wire:model="subscribed_to_newsletter"
                    :label="__('Email me when something cool drops')"
                />
            </div>

            <!-- Notification Preferences Section -->
            <div class="space-y-4 border-t pt-6">
                <flux:heading size="lg">{{ __('Notification Preferences') }}</flux:heading>
                <flux:subheading>{{ __('Configure how and when you receive game notifications') }}</flux:subheading>

                <!-- Contact Information -->
                <div class="space-y-4 mt-4" x-data="{
                    notify_via_sms: $wire.$entangle('notify_via_sms', true),
                    notify_via_discord: $wire.$entangle('notify_via_discord', true),
                    notify_via_telegram: $wire.$entangle('notify_via_telegram', true),
                    notify_via_browser: $wire.$entangle('notify_via_browser', true),
                    init() {
                        window.addEventListener('push-notification-registered', (e) => {
                            $flux.toast({ text: e.detail.message, variant: 'success' });
                        });
                        window.addEventListener('push-notification-unregistered', (e) => {
                            $flux.toast({ text: e.detail.message, variant: 'success' });
                        });
                        window.addEventListener('push-notification-error', (e) => {
                            $flux.toast({ text: e.detail.message, variant: 'danger' });
                        });
                    }
                }">
                    <flux:checkbox
                        wire:model="notify_via_email"
                        :label="__('Notify me via Email')"
                    />
                    {{--
                    <flux:checkbox
                        wire:model="notify_via_sms"
                        :label="__('Notify me via SMS')"
                    />
                    <div x-show="notify_via_sms">
                        <flux:input
                            wire:model="phone_number"
                            :label="__('Phone Number (optional)')"
                            type="tel"
                            placeholder="+1234567890"
                        />
                    </div>
                    --}}
                    <flux:checkbox
                        wire:model="notify_via_discord"
                        :label="__('Notify me via Discord')"
                    />

                    <div x-show="notify_via_discord">
                        <flux:input
                            wire:model="default_discord_webhook"
                            :label="__('Discord Webhook URL')"
                            type="url"
                            placeholder="https://discord.com/api/webhooks/..."
                        />
                        <flux:text class="mt-1 text-xs">
                            <a href="https://support.discord.com/hc/en-us/articles/228383668-Intro-to-Webhooks" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline">
                                {{ __('How to create a Discord webhook →') }}
                            </a>
                        </flux:text>
                    </div>

                    <!-- Telegram Notifications -->
                    <flux:checkbox
                        wire:model="notify_via_telegram"
                        :label="__('Notify me via Telegram')"
                    />

                    <div x-show="notify_via_telegram" class="mt-2">
                        @if ($telegram_chat_id)
                            <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded border border-green-200 dark:border-green-800">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <flux:text class="text-green-700 dark:text-green-300 font-medium">
                                            ✓ Telegram Connected
                                        </flux:text>
                                        <flux:text class="text-sm text-green-600 dark:text-green-400 mt-1">
                                            Connected as {{ '@' . ($telegram_username ?? 'Unknown') }}
                                        </flux:text>
                                    </div>
                                    <flux:button
                                        wire:click="disconnectTelegram"
                                        variant="danger"
                                        size="sm"
                                    >
                                        {{ __('Disconnect') }}
                                    </flux:button>
                                </div>
                            </div>
                        @else
                            @php
                                $telegramLink = $this->generateTelegramLink();
                            @endphp

                            @if (empty($telegramLink))
                                <flux:text class="text-red-600 dark:text-red-400 text-sm">
                                    {{ __('Telegram bot is not configured. Please set TELEGRAM_BOT_USERNAME in your environment file.') }}
                                </flux:text>
                            @else
                                <flux:text class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                    {{ __('Connect your Telegram account to receive notifications') }}
                                </flux:text>
                                <a
                                    href="{{ $telegramLink }}"
                                    target="_blank"
                                    class="inline-flex items-center justify-center h-8 px-3 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 rounded-lg transition-colors"
                                >
                                    {{ __('Connect Telegram') }}
                                </a>
                                <flux:text class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                    {{ __('This will open Telegram and connect your account') }}
                                </flux:text>
                            @endif
                        @endif
                    </div>

                    {{--
                    <!-- Browser Push Notifications -->
                    <div x-data="{
                        browserSupported: ('serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window),
                        vapidConfigured: '{{ config('webpush.vapid.public_key') }}' !== ''
                    }">
                        <template x-if="!browserSupported">
                            <div>
                                <flux:checkbox
                                    disabled
                                    :label="__('Notify me via Browser')"
                                />
                                <flux:text class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ __('Browser notifications are not supported in your current browser. Try Chrome, Edge, or Safari.') }}
                                </flux:text>
                            </div>
                        </template>
                        <template x-if="browserSupported && !vapidConfigured">
                            <div>
                                <flux:checkbox
                                    disabled
                                    :label="__('Notify me via Browser')"
                                />
                                <flux:text class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ __('Browser notifications are not configured on this server.') }}
                                </flux:text>
                            </div>
                        </template>
                        <template x-if="browserSupported && vapidConfigured">
                            <flux:checkbox
                                wire:model="notify_via_browser"
                                :label="__('Notify me via Browser')"
                            />
                        </template>
                    </div>

                    <div x-show="notify_via_browser" class="mt-2">
                        @if ($has_push_subscription)
                            <flux:text class="text-green-600 dark:text-green-400">
                                ✓ Browser notifications enabled
                            </flux:text>
                            <flux:button
                                onclick="window.pushNotifications.unregister()"
                                variant="ghost"
                                size="sm"
                                class="mt-2"
                            >
                                {{ __('Disable') }}
                            </flux:button>
                        @else
                            <flux:text class="text-sm text-gray-600 dark:text-gray-400">
                                {{ __('Enable browser notifications to get alerts even when the tab is closed') }}
                            </flux:text>
                            <flux:button
                                onclick="window.pushNotifications.register()"
                                variant="primary"
                                size="sm"
                                class="mt-2"
                            >
                                {{ __('Enable Browser Notifications') }}
                            </flux:button>
                        @endif
                    </div>
                    --}}
                </div>

                <!-- Notification Events -->
                <div class="space-y-3 mt-6">
                    <flux:subheading>{{ __('Notify me when:') }}</flux:subheading>

                    <flux:checkbox
                        wire:model="notify_on_game_start"
                        :label="__('My game starts')"
                    />

                    <flux:checkbox
                        wire:model="notify_on_challenge_start"
                        :label="__('A new challenge begins')"
                    />

                    <flux:checkbox
                        wire:model="notify_on_game_end"
                        :label="__('My game ends')"
                    />
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <x-button variant="primary" type="submit" class="w-full">{{ __('Save') }}</x-button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        <livewire:settings.delete-user-form />
    </x-settings.layout>
</section>
