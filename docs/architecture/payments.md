# Payment architecture

HORTON keeps payment business rules independent from any specific provider.

```text
PaymentOrchestrator
        │
        ├── PaymentGatewayManager
        │        │
        │        └── PaymentGatewayContract
        │                 │
        │                 └── AbstractPaymentGateway
        │                          ├── FakePaymentGateway
        │                          └── ManualPaymentGateway
        │
        └── WalletService (wallet payments)
```

## Rules

- `PaymentGatewayContract` defines the capabilities every gateway must implement.
- `AbstractPaymentGateway` owns common invariants: positive amount, required currency, currency support, already-successful payment handling, and callback amount validation.
- Concrete gateways contain only provider-specific behavior.
- `PaymentOrchestrator` owns application-level orchestration: payment persistence, attempts, callbacks, verification, order settlement, wallet payment and idempotency.
- `PaymentCallback.callback_id` is unique and processed callbacks are replay-safe.
- Gateway transaction/reference identifiers cannot be attached to another payment.
- `payments.idempotency_key` is unique to protect concurrent duplicate initiation.
- Wallet payment uses the existing wallet ledger/idempotency implementation instead of pretending the wallet is an external gateway.
- Gateway credentials belong in environment/configuration; runtime gateway selection can be controlled through the settings store.

## Adding a gateway

Implement `PaymentGatewayContract`, preferably by extending `AbstractPaymentGateway`, then register the driver in `config/payment.php`. Order/checkout code does not need to know the gateway implementation.

## Current adapters

`FakePaymentGateway` is deterministic enough for application tests and supports simulated failures. `ManualPaymentGateway` remains pending until a trusted verification/callback marks it successful.
