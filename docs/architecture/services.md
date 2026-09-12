# Service Lifecycle & Provisioning

HORTON keeps service business logic independent from Telegram, payment gateways and concrete provisioning vendors.

## Architecture

```text
ServiceLifecycle Contract
        |
        v
AbstractServiceLifecycle
        |
        v
DatabaseServiceLifecycle
        |
        +---- ServiceProviderContract
                    |
                    +---- AbstractServiceProvider
                    +---- FakeServiceProvider
```

The provider contract is the infrastructure boundary introduced earlier. The lifecycle service owns HORTON's service invariants, provider/account selection, local state and operation history.

## Lifecycle

```text
pending -> active -> suspended/expired/disabled
    |
    +-> failed
```

Creation validates the plan, selects an active provider and an active account using priority, then persists a pending service. Provisioning calls the provider and changes the local service to active only after a successful provider result.

Trial plans use `trial_duration_value` / `trial_duration_unit`. A user cannot have more than one pending or active trial service.

## Provider operations

Supported lifecycle operations are:

- create / provision
- get
- status
- renew
- extend
- add capacity
- disable
- delete

Every provider operation is recorded in `service_operations` with status, timing, request metadata, response metadata and failure information.

Provider failures are persisted as failed service operations and move the local service to `failed` without losing the failure history when the original transaction rolls back.

## Idempotency

Provisioning has a deterministic operation key per service. A successful provisioning operation is treated as already completed, preventing a queue retry from creating a second remote resource. The provider abstraction itself also reconciles an existing `external_id` instead of creating another resource.

## Queue boundary

`ProvisionServiceJob` is the external provisioning boundary. It uses Laravel's queue contract with three attempts, a 10/30/90 second backoff and a 120 second timeout. Card 20 will add the project's full queue/deduplication/rate-limit/retry-after infrastructure around this boundary.

Paid orders can be handed to `QueuePaidOrderProvisioning`, which creates one service per order-item quantity and dispatches provisioning jobs. No Telegram code is involved.

## Deletion

The provider's delete operation is executed remotely, while the local service remains auditable and is marked disabled. HORTON does not physically delete service history as part of a provider deletion.
