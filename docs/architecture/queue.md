# Queue, Retry, Deduplication and Rate Limiting

## Responsibility boundary

HORTON does not reimplement Telegram queue infrastructure. `ReyhanTeam/laravel-telegram-bot-router` owns queued Telegram update processing, route-level queueing, update deduplication, Telegram queue exception policy, failed-job events and outgoing Telegram rate limiting.

HORTON owns application jobs such as service provisioning and configures their Laravel queue policy separately.

## Telegram Router queue

The package supports:

- queued Telegram updates
- route-specific queues via `->queue()`
- configurable connection, queue, attempts, backoff and timeout
- duplicate-update protection keyed by Telegram `update_id`
- retryable/non-retryable exception policy
- Laravel `failed_jobs` as the canonical failed-job store
- `TelegramJobFailed` failure event
- Telegram `retry_after` precedence for outgoing rate-limit failures
- outgoing API throttling that does not block queue workers while waiting

HORTON keeps these settings under `config/telegram-bot-router.php` and does not create a second Telegram queue implementation.

## HORTON application jobs

Application jobs use `config/queue.php` → `horton`:

- `connection`
- `queue`
- `tries`
- `backoff`
- `timeout`
- `unique_for`
- optional queue middleware

`app/Jobs/Concerns/ConfiguresHortonQueue.php` centralizes this policy so application jobs do not duplicate queue configuration.

### Provisioning

`ProvisionServiceJob`:

- uses the HORTON queue policy
- is unique per service while queued/retrying
- keeps service provisioning idempotent at the domain layer
- supports configurable attempts, backoff and timeout
- accepts application queue middleware from configuration

`QueuePaidOrderProvisioning`:

- is a queued listener
- uses the same application queue policy
- waits for the `OrderPaid` transaction to commit before being queued
- creates each paid order service once using the existing order-item/index guard
- dispatches provisioning as a separate job

## Retry policy

Retries are for transient failures only. Deterministic invalid input or invalid Telegram routes are non-retryable through the package policy.

For HORTON application jobs, domain services remain responsible for idempotency. A retry must never be treated as a new business operation.

The queue visibility/retry window must be longer than the worker timeout. The example environment uses a 120 second application-job timeout and a 180 second database queue retry window.

## Rate limiting

Incoming Telegram route limits are provided by the package and configured under `telegram-bot-router.rate_limit`:

- user
- chat
- command

Outgoing Telegram API limits are provided by the package under `telegram-bot-router.outgoing_rate_limit`.

Queue workers use queue-aware throttling: they receive a retryable rate-limit exception instead of sleeping while waiting for an outgoing slot. Telegram `retry_after` is preferred when Telegram explicitly provides it.

These controls remain configurable and are disabled/enabled through environment/configuration until the Admin Dashboard owns runtime settings.

## Worker operation

For the current local Docker setup with the database queue:

```bash
php artisan queue:work --queue=default --tries=3 --timeout=120
```

If `TELEGRAM_QUEUE_NAME` or `HORTON_QUEUE_NAME` is changed, use the corresponding queue names. Production workers should be managed by Supervisor, systemd, or the platform's worker service.

After deployment:

```bash
php artisan queue:restart
```

Inspect failed jobs with Laravel:

```bash
php artisan queue:failed
php artisan queue:retry all
php artisan queue:forget <id>
```

## Verification checklist

1. Start a worker on the configured application queue.
2. Enable `TELEGRAM_QUEUE_UPDATES=true` when testing queued Telegram routes.
3. Send several real Telegram updates and confirm jobs enter and leave the queue.
4. Exercise a callback route and confirm it is processed by the configured queue when queueing is enabled.
5. Trigger a transient application-job failure and confirm a later attempt after the configured backoff.
6. Confirm an exhausted job appears in `failed_jobs`.
7. Confirm duplicate Telegram `update_id` values are processed only once by the package queue deduplication layer.
8. Enable incoming rate limits and verify excessive requests are throttled.
9. Enable outgoing limits and verify queue workers retry instead of blocking.
10. Verify a Telegram `retry_after` delay takes precedence over the generic outgoing backoff.
11. Verify service provisioning retries do not create a second service or remote resource.
12. Inspect logs/events and confirm bot tokens, credentials and sensitive payloads are not leaked.

No new Telegram infrastructure is introduced by HORTON for these concerns; the application consumes the package capabilities directly.
