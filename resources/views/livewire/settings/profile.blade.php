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

        $prefs = Auth::user()->notification_preferences ?? [];
        $this->notify_on_game_start = $prefs['notify_on_game_start'] ?? false;
        $this->notify_before_challenge_end = $prefs['notify_before_challenge_end'] ?? false;
        $this->notify_on_challenge_start = $prefs['notify_on_challenge_start'] ?? false;
        $this->notify_on_game_end = $prefs['notify_on_game_end'] ?? false;

        $this->notify_via_email = $prefs['notify_via_email'] ?? false;
        $this->notify_via_sms = $prefs['notify_via_sms'] ?? false;
        $this->notify_via_discord = $prefs['notify_via_discord'] ?? false;
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
                <div class="space-y-4 mt-4" x-data="{ notify_via_sms: $wire.$entangle('notify_via_sms', true) , notify_via_discord: $wire.$entangle('notify_via_discord', true) }">
                    <flux:checkbox
                        wire:model="notify_via_email"
                        :label="__('Notify me via Email')"
                    />
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

                    <flux:checkbox
                        wire:model="notify_via_discord"
                        :label="__('Notify me via Discord')"
                    />

                    <div x-show="notify_via_discord">
                        <flux:input
                            wire:model="default_discord_webhook"
                            :label="__('Discord Webhook URL (optional)')"
                            type="url"
                            placeholder="https://discord.com/api/webhooks/..."
                        />
                        <flux:text class="mt-1 text-xs">
                            <a href="https://support.discord.com/hc/en-us/articles/228383668-Intro-to-Webhooks" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline">
                                {{ __('How to create a Discord webhook →') }}
                            </a>
                        </flux:text>
                    </div>
                </div>

                <!-- Notification Events -->
                <div class="space-y-3 mt-6">
                    <flux:subheading>{{ __('Notify me when:') }}</flux:subheading>

                    <flux:checkbox
                        wire:model="notify_on_game_start"
                        :label="__('A game I\'m in starts')"
                    />

                    <flux:checkbox
                        wire:model="notify_on_challenge_start"
                        :label="__('A new challenge begins')"
                    />

                    <flux:checkbox
                        wire:model="notify_before_challenge_end"
                        :label="__('A challenge is ending soon (5 minutes before)')"
                    />

                    <flux:checkbox
                        wire:model="notify_on_game_end"
                        :label="__('A game I\'m in ends')"
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
