# laracon-2025

A Laravel application with social authentication support for Google, Apple, and Discord.

## Features

- **Social Authentication**: Sign in with Google, Apple, or Discord
- **User Management**: Comprehensive user profiles with social provider linking
- **Game Integration**: Social login with game context preservation
- **Newsletter Subscription**: Automatic newsletter signup for social registrations

## Social Authentication Setup

This application supports OAuth authentication with:
- **Google** - Sign in with Google accounts
- **Apple** - Sign in with Apple ID
- **Discord** - Sign in with Discord accounts

### Configuration

1. Copy the environment variables template and configure your OAuth providers:

```env
# Google OAuth
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret

# Apple OAuth  
APPLE_CLIENT_ID=your_apple_client_id
APPLE_CLIENT_SECRET=your_apple_client_secret

# Discord OAuth
DISCORD_CLIENT_ID=your_discord_client_id
DISCORD_CLIENT_SECRET=your_discord_client_secret
```

2. Set up OAuth applications with each provider:
   - **Google**: [Google Cloud Console](https://console.cloud.google.com/)
   - **Apple**: [Apple Developer Console](https://developer.apple.com/)
   - **Discord**: [Discord Developer Portal](https://discord.com/developers/applications)

3. Configure redirect URIs for each provider:
   - Google: `https://yourdomain.com/auth/google/callback`
   - Apple: `https://yourdomain.com/auth/apple/callback`
   - Discord: `https://yourdomain.com/auth/discord/callback`

For detailed setup instructions, see `docs/SOCIAL_AUTH_SETUP.md`.

## Installation

1. Install dependencies:
```bash
composer install
npm install
```

2. Run migrations:
```bash
php artisan migrate
```

3. Configure your social OAuth providers (see above)

4. Start the development server:
```bash
php artisan serve
```

## Architecture

- **Laravel Socialite**: Handles OAuth authentication flow
- **Livewire Components**: Interactive authentication forms  
- **Flux UI**: Modern, accessible component library
- **Event-Driven**: User creation and management via events
- **Database**: Social provider information stored securely

## Security Features

- **Account Linking**: Automatically links social accounts to existing users
- **Email Verification**: Social accounts are pre-verified
- **Password Optional**: Users can authenticate purely via social providers
- **Error Handling**: Comprehensive error handling for failed authentications
- **CSRF Protection**: All forms protected against CSRF attacks
