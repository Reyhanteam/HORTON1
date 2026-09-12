# Settings & Feature Flags

HORTON keeps runtime configuration behind application abstractions. Telegram controllers, shop services, payment services, provider implementations, and the Admin Dashboard must not read `BotSetting` directly.

## Contracts

- `App\Contracts\SettingsStore` defines the runtime settings API.
- `App\Contracts\FeatureManager` defines feature-flag operations.
- `App\Services\Settings\AbstractSettingsStore` contains shared storage rules, cache invalidation, JSON encoding, and type detection.
- `App\Services\Settings\DatabaseSettingsStore` is the current database implementation.
- `App\Services\Settings\FeatureManager` stores flags through `SettingsStore` using the `features.*` namespace.

This gives the application a stable boundary. A future Redis/API settings backend can replace `DatabaseSettingsStore` without changing consumers.

## Typed values

Settings are persisted in `bot_settings` with a `type` field. Supported values are string, integer, float, boolean, and JSON/array. Missing keys use the caller-provided safe default.

Example:

```php
$settings = app(\App\Contracts\SettingsStore::class);
$enabled = $settings->get('shop.enabled', false);
$settings->set('shop.currency', 'IRR');
```

## Feature flags

Application feature names are defined in `App\Enums\Feature`. Flags are stored as `features.<name>` and should only decide whether a capability is available; business logic stays in Actions/Services.

```php
$features = app(\App\Contracts\FeatureManager::class);

if ($features->enabled('wallet')) {
    // Delegate to the wallet application service.
}
```

Defined flags include registration, phone verification, channel membership, shop, wallet, referral, cashback, trial, support, and maintenance.

## Cache

The database store caches the complete settings snapshot and invalidates it after `set`, `forget`, or an explicit `flush`. This keeps read-heavy bot flows inexpensive while ensuring dashboard changes become visible to new reads.

## Secrets

Secrets are not runtime settings. Bot tokens, payment secrets, provider credentials, and encryption keys remain in environment/secret storage and are never exposed through public settings, normal logs, or dashboard listings.

## Extension rule

For a new integration, prefer:

`Contract -> Abstract base -> Concrete implementation -> Application service/action`

The contract defines required capabilities; the abstract base enforces shared invariants; the concrete class contains provider-specific details; application services orchestrate business workflows.
