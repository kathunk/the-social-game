<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use App\Events\UserSubscribedToNewsletter;
use App\Events\UserUnsubscribedFromNewsletter;

new class extends Component {
    public string $name = '';
    public string $email = '';
    public bool $has_active_game = false;
    public bool $subscribed_to_newsletter;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->has_active_game = Auth::user()->has_active_game;
        $this->subscribed_to_newsletter = Auth::user()->subscribed_to_newsletter;
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
