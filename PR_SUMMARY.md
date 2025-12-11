# Multi-Channel Notification System

## Summary
Implements a comprehensive, user-configurable notification system supporting **5 channels** (Email, Discord, SMS, Telegram, Web Push) for game events. Users can customize which channels to use and which events to be notified about through their profile settings.

## Features

### 📬 Notification Channels
- **Email** - Markdown-based email notifications via Laravel Mail
- **Discord** - Webhook-based notifications with rich embeds
- **SMS** - Text notifications via Vonage (optional, requires setup)
- **Telegram** - Bot-based notifications with account linking
- **Web Push** - Browser push notifications (works even when tab closed)

### 🎮 Game Events
- **Game Started** - When a game begins
- **Challenge Started** - When a new challenge/round begins (with special "final round" messaging)
- **Game Ended** - When a game completes

### ⚙️ User Preferences
Granular control per user:
- Toggle each notification channel on/off
- Toggle each game event on/off
- Only notifies players who haven't resigned
- Channel-specific configuration (webhook URLs, phone numbers, account linking)

## Technical Implementation

### Database Changes
**Migration 1:** `add_notification_preferences_to_users_table`
- `notification_preferences` (JSON) - Stores user's channel and event preferences
- `phone_number` - For SMS notifications
- `default_discord_webhook` - Discord webhook URL

**Migration 2:** `add_telegram_and_push_fields_to_users_table`
- `telegram_chat_id` - Telegram account identifier
- `telegram_username` - Telegram username
- `telegram_verification_token` - For account linking
- `telegram_connected_at` - Connection timestamp
- `push_subscriptions` (JSON) - Web Push subscription data (supports multiple devices)

### Architecture

#### Notification Classes
All notification classes implement the same pattern:
- `GameStartedNotification` - app/Notifications/GameStartedNotification.php:15
- `ChallengeStartedNotification` - app/Notifications/ChallengeStartedNotification.php:17
- `GameEndedNotification` - app/Notifications/GameEndedNotification.php:15

Each implements:
- `via()` - Determines which channels to send through based on user preferences
- `toMail()`, `toDiscord()`, `toSms()`, `toTelegram()`, `toWebPush()` - Format for each channel

#### Custom Notification Channels
- `DiscordChannel` - app/Notifications/Channels/DiscordChannel.php:11
- `SmsChannel` - app/Notifications/Channels/SmsChannel.php:16
- `TelegramChannel` - app/Notifications/Channels/TelegramChannel.php:18
- `WebPushChannel` - app/Notifications/Channels/WebPushChannel.php:18

All channels include error handling with logging only on failures.

#### Services
- `TelegramBotService` - app/Services/TelegramBotService.php:10 - Telegram Bot API wrapper
- `WebPushService` - app/Services/WebPushService.php:13 - Web Push notification sender using VAPID

#### API Endpoints
**routes/api.php:**
- `POST /api/telegram/webhook` - Receives Telegram bot updates for account linking
- `POST /api/push/subscribe` - Saves web push subscription (auth required)
- `POST /api/push/unsubscribe` - Removes web push subscription (auth required)

#### Controllers
- `TelegramWebhookController` - app/Http/Controllers/TelegramWebhookController.php:16 - Handles bot verification flow
- `PushSubscriptionController` - app/Http/Controllers/PushSubscriptionController.php:10 - Manages push subscriptions

### Frontend

#### Service Worker
**public/sw.js** - Handles push notifications in the background:
- Displays notifications when received
- Handles notification clicks to open game URLs

#### Push Notification Handler
**resources/js/push-notifications.js** - PushNotifications class:
- Registers service worker
- Requests notification permissions
- Manages push subscriptions (subscribe/unsubscribe)
- Provides user feedback via custom events

#### Profile Settings UI
**resources/views/livewire/settings/profile.blade.php** - Enhanced with:
- Channel toggles (Email, Discord, SMS, Telegram, Browser)
- Event toggles (Game start, Challenge start, Game end)
- Discord webhook input
- Telegram connection flow (direct link to bot)
- Browser notification enable/disable
- Visual feedback for connected accounts (green box for Telegram, status indicators)
- Browser compatibility detection

### User Flows

#### Telegram Connection
1. User enables "Notify me via Telegram" in settings
2. Clicks "Connect Telegram" button (opens Telegram directly)
3. Sends `/start <token>` to bot
4. Bot webhook verifies token and links account
5. User sees green "Telegram Connected" status with disconnect button

#### Web Push Setup
1. User enables "Notify me via Browser" in settings
2. Clicks "Enable Browser Notifications"
3. Browser shows permission prompt
4. On accept, subscription saved to database
5. Toast confirms success, page reloads to show status

## Configuration Required

### Environment Variables
```env
# Telegram (requires HTTPS for webhook)
TELEGRAM_BOT_TOKEN=your_bot_token
TELEGRAM_BOT_USERNAME=your_bot_username
TELEGRAM_WEBHOOK_SECRET=random_secret

# Web Push (VAPID keys)
VAPID_PUBLIC_KEY=your_vapid_public_key
VAPID_PRIVATE_KEY=your_vapid_private_key
VAPID_SUBJECT=mailto:your-email@example.com
VITE_VAPID_PUBLIC_KEY="${VAPID_PUBLIC_KEY}"

# Optional: SMS via Vonage
VONAGE_KEY=your_vonage_key
VONAGE_SECRET=your_vonage_secret
VONAGE_SMS_FROM="Your App Name"
```

### Setup Steps

1. **Install Dependencies**
   ```bash
   composer install
   npm install && npm run build
   ```

2. **Run Migrations**
   ```bash
   php artisan migrate
   ```

3. **Generate VAPID Keys** (for Web Push)
   ```bash
   # Use online generator or:
   php artisan tinker
   # Then run the VAPID key generation script
   ```

4. **Set Telegram Webhook** (production only, requires HTTPS)
   ```bash
   curl -X POST "https://api.telegram.org/bot<TOKEN>/setWebhook" \
     -H "Content-Type: application/json" \
     -d '{"url": "https://your-domain.com/api/telegram/webhook", "secret_token": "your_secret"}'
   ```

## Dependencies Added
- `vonage/client` ^4.0 - SMS notifications
- `minishlink/web-push` ^9.0 - Web Push notifications

## Testing

### Manual Testing Checklist
- [ ] Email notifications work for all game events
- [ ] Discord webhooks send properly formatted embeds
- [ ] Telegram connection flow works (requires HTTPS/ngrok locally)
- [ ] Web push notifications display correctly
- [ ] User preferences persist correctly
- [ ] Only non-resigned players receive notifications
- [ ] Final round messaging displays for last challenge
- [ ] Multiple browser push subscriptions work (multi-device)
- [ ] Failed push subscriptions are automatically removed
- [ ] Disconnecting Telegram/browser notifications works

### Automated Tests
- `tests/Feature/Settings/NotificationPreferencesTest.php` - Tests preference updates

## Notes

- **Telegram requires HTTPS**: Webhook only works in production or with ngrok locally
- **Web Push requires HTTPS**: Browser push only works over HTTPS (production)
- **SMS is optional**: Can be enabled later, not required for core functionality
- **Logging is minimal**: Only errors are logged to avoid log spam
- **Multi-device support**: Web push supports multiple subscriptions per user
- **Final round detection**: Challenge notifications detect and display special message for last round
- **Resigned players excluded**: Players who resigned don't receive game notifications

## Breaking Changes
None - This is a new feature with no impact on existing functionality.

## Future Enhancements
- [ ] Add "X minutes before challenge ends" reminder notifications
- [ ] Add notification history/log for users
- [ ] Add batch notification summary (daily digest option)
- [ ] Add Slack channel support
- [ ] Add webhook secret rotation UI
- [ ] Add notification test/preview in settings
