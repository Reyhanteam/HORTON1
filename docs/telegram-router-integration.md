# HORTON Telegram Router Integration

HORTON uses `ReyhanTeam/laravel-telegram-bot-router` as its Telegram infrastructure layer.

## Transport

The application currently uses long polling. Webhook support remains a package capability but is not the active HORTON transport.

```env
TELEGRAM_BOT_MODE=polling
```

Start polling with the package command:

```bash
php artisan reyhan:start-polling
```

## Route ownership

All Telegram routes live in `routes/bot.php` and use the package `Route` facade.

Application controllers are intentionally thin. They resolve application services and do not implement Telegram transport concerns themselves.

## Package capabilities used by HORTON

- command routing
- callback-query routing
- route middleware and groups
- conversation steps, TTL, cache store and cancellation
- dependency injection through Laravel's container
- keyboard builders
- route rate-limit registration
- package queue configuration
- package retry/backoff/timeout configuration
- update deduplication configuration
- package Telegram API facade
- package polling transport

HORTON does not create a second Telegram routing implementation.

## Conversations

Registration is registered as a package conversation with two steps:

1. rules acceptance
2. phone verification when enabled

Conversation state is owned by the package conversation manager/cache. HORTON only provides the step handlers and application services.

## Rate limiting

Route rate limits are attached through the package route registrar. They are controlled by the existing router configuration and are disabled by default until explicitly enabled in the environment.

The configured scopes are:

- user
- chat
- command

Outgoing Telegram API rate limiting and Telegram `retry_after` handling are also provided by the package configuration and remain independently configurable.

## Queue and reliability

The package configuration is kept in `config/telegram-bot-router.php` for:

- update queueing
- queue connection/name
- attempts
- backoff
- timeout
- update deduplication
- deduplication TTL
- retryable/non-retryable exception policy
- outgoing rate limiting
- Telegram `retry_after`

Heavy application jobs will be assigned to queues in the dedicated queue/reliability work rather than forcing interactive registration conversations into a queue.

## Message editing rule

Normal user-facing bot responses go through `App\\Services\\Telegram\\BotMessageResponder`.

The responder first tries to edit the current bot message and persists the active bot message ID in `telegram_bot_message_states`.

A new Telegram message is only sent when there is no known bot message, for `/start`, or where Telegram itself requires a new message type such as the phone-number request keyboard.

This is an application architecture rule and is not implemented by duplicating Telegram routing functionality.

## Administration boundary

No Telegram route in HORTON is an administration interface. Admin users, roles, permissions and dashboard configuration belong to the web Admin Dashboard.

## Database boundary

Telegram infrastructure does not assume application tables. Application services read the existing HORTON database schema through their contracts/models. Current Telegram-specific persistence includes the existing `telegram_accounts`, `bot_messages`, `bot_settings`, `bot_channels` and `telegram_bot_message_states` structures already defined by HORTON migrations.

## Manual real-Telegram verification

No new PHPUnit/unit-test suite is added for this integration. Verification is intended to be performed against the running polling bot.

Minimum verification after pulling the branch:

1. Run migrations against the existing HORTON database.
2. Clear Laravel configuration/cache after changing environment values.
3. Start the polling worker.
4. Verify `/start` routing.
5. Verify registration conversation step transitions and `/cancel`.
6. Verify mandatory channel middleware when enabled.
7. Verify membership recheck callback.
8. Verify every main-menu callback edits the current bot message rather than creating a second normal message.
9. Verify the phone request remains the documented Reply Keyboard exception.
10. If rate limiting is enabled, verify user/chat/command limits with the configured thresholds.
