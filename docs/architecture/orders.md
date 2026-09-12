# Orders, Checkout & Invoices

## Boundary
Order/Checkout is application business logic. Telegram and Admin Dashboard are adapters only; neither owns pricing, state transitions, invoice creation, or payment rules.

## Checkout pipeline

```text
User
  ↓
CheckoutService
  ↓
OrderService
  ├── CatalogService → active/purchasable Plan
  ├── PricingService → authoritative current price
  └── OrderItem snapshot
          ↓
      InvoiceService
          ↓
      Payment layer (Card 10)
          ↓
      OrderStateMachine
```

`CheckoutData` contains only the user's requested plans, quantities, currency, idempotency key and metadata. The server always obtains the authoritative price from `PricingService`; an optional client price is accepted only as a consistency check and never as an authority.

## Order invariants

- Every order contains at least one item.
- Currency is normalized to a three-letter uppercase code.
- Quantity must be positive.
- Only an active catalog hierarchy can be purchased.
- Price is re-read at checkout and stored on `order_items.unit_price`.
- Product/Plan display data and service-relevant attributes are snapshotted on `order_items`.
- Adjustments cannot make the total negative; their sum cannot exceed the subtotal.
- An idempotency key is unique and bound to the original user/currency. Reusing it with another user or currency is rejected.
- Existing idempotent checkout returns the original order and invoice instead of creating another purchase.

## State machine

Allowed transitions are deliberately narrow:

- `pending → paid | cancelled | failed`
- `paid → processing | cancelled | failed`
- `processing → completed | failed`
- `failed → pending | cancelled`
- `completed` and `cancelled` are terminal.

Paid/processing/completed orders cannot be cancelled through the normal cancellation path. A later refund/compensation flow must be implemented separately.

## Invoice lifecycle

An invoice is created once per order using the unique `order_id` constraint. It snapshots subtotal, discount, total, currency and issue time. When an order becomes paid, the same invoice is marked paid. Invoice numbers are generated server-side and are unique.

## Purchase history

`OrderService::history()` returns only orders belonging to the requested user and eager-loads items, invoice and payments. Pagination is capped at 100 records per page.

## Concurrency and replay

Order creation, state transitions and invoice settlement use database transactions and row locks where the operation can race. The unique order idempotency key and unique invoice order relationship provide database-level protection in addition to application checks.

Payment duplicate protection remains owned by the payment subsystem. Service provisioning remains owned by the provider/service subsystem and is intentionally not executed by checkout.

## Relationship to Card 14

Discount/Gift/Referral/Cashback calculation remains in the marketing services. Checkout exposes persisted order adjustment fields but does not trust client-supplied discount values. Card 14 will connect those services to the checkout pipeline using application-level rules.
