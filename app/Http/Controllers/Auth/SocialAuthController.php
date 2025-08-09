<?php

namespace App\Http\Controllers\Auth;

use App\Events\UserCreated;
use App\Events\UserSubscribedToNewsletter;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Thunk\Verbs\Facades\Verbs;

class SocialAuthController extends Controller
{
    /**
     * Redirect to the provider's authentication page.
     */
    public function redirectToProvider(
        string $provider,
        Request $request,
    ): \Illuminate\Http\RedirectResponse {
        $this->validateProvider($provider);

        $socialiteDriver = Socialite::driver($provider);

        // Preserve game parameter through OAuth state
        if ($request->has('game')) {
            // Store game parameter in session instead of state
            session()->put('social_auth_game', $request->get('game'));
        }

        return $socialiteDriver->redirect();
    }

    /**
     * Handle the callback from the provider.
     */
    public function handleProviderCallback(
        string $provider,
        Request $request,
    ): \Illuminate\Http\RedirectResponse {
        $this->validateProvider($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'social' => 'Authentication failed. Please try again.',
                ]);
        }

        // Find existing user by provider or email
        $user = User::query()
            ->where('provider_id', $socialUser->getId())
            ->where('provider_name', $provider)
            ->first();

        if (! $user) {
            // Check if user exists with same email
            $existingUser = User::query()
                ->where('email', $socialUser->getEmail())
                ->first();

            if ($existingUser) {
                // Link social account to existing user
                $existingUser->update([
                    'provider_id' => $socialUser->getId(),
                    'provider_name' => $provider,
                    'avatar' => $socialUser->getAvatar(),
                ]);
                $user = $existingUser;
            } else {
                // Create new user using the event system
                $user_id = UserCreated::fire(
                    name: $socialUser->getName() ?:
                    $socialUser->getNickname() ?:
                    explode('@', $socialUser->getEmail())[0],
                    email: $socialUser->getEmail(),
                    encrypted_password: bcrypt(Str::random(32)), // Random password since they'll use social login
                    provider_id: $socialUser->getId(),
                    provider_name: $provider,
                    avatar: $socialUser->getAvatar(),
                )->user_id;

                Verbs::commit();

                $user = User::query()->find($user_id);

                event(new Registered($user));

                // Subscribe to newsletter by default for social signups
                UserSubscribedToNewsletter::fire(user_id: $user->id);
            }
        }

        Auth::login($user, true);

        // Extract game parameter from session
        $game = session()->pull('social_auth_game');

        // Also check query parameter as fallback (for direct URLs)
        if (! $game) {
            $game = $request->query('game');
        }

        if ($game) {
            return redirect()->route('pre-game-lobby', ['game' => $game]);
        }

        // Use Laravel's intended URL system like regular auth
        // Check for games URLs and redirect to dashboard instead (matches login.blade.php logic)
        if (
            session()->has('url.intended') &&
            str_contains(session()->get('url.intended'), '/games/')
        ) {
            session()->forget('url.intended');

            return redirect()->route('dashboard');
        }

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Validate that the provider is supported.
     */
    protected function validateProvider(string $provider): void
    {
        $allowedProviders = ['google', 'apple', 'discord'];

        if (! in_array($provider, $allowedProviders)) {
            abort(404);
        }
    }
}
