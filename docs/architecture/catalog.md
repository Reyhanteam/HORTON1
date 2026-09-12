# Catalog & Pricing Architecture

HORTON keeps the storefront catalog independent from Telegram, payments, and service providers.

## Layers

```text
Bot / Admin Dashboard
        |
        v
Application Actions
        |
        +--> CatalogService
        |      -> AbstractCatalogService
        |      -> DatabaseCatalogService
        |
        +--> PricingService
               -> AbstractPricingService
               -> DatabasePricingService
```

Interfaces define the capabilities and invariants expected by the application. Abstract services own shared validation and selection rules. Concrete implementations only provide persistence-specific behavior.

## Catalog hierarchy

`Category -> Product -> Plan -> PlanPrice`

The storefront uses active hierarchy filtering:

- inactive categories are hidden;
- products under inactive categories are hidden;
- inactive products are hidden;
- inactive plans are hidden;
- deterministic ordering uses `sort_order` and `id`.

`findPurchasablePlan()` is stricter than a normal lookup. It verifies the plan, product, and category are active and that the plan has a valid duration configuration.

## Pricing

A plan has a base price and optional `plan_prices` for currency-specific and time-bounded prices.

Pricing selection is deterministic:

1. normalize and validate the requested three-letter currency;
2. select prices matching the currency and current validity window;
3. prefer default prices;
4. prefer the latest applicable `starts_at`;
5. prefer the newest record on a tie;
6. fall back to the plan base price only when the requested currency matches the plan currency;
7. otherwise fail explicitly instead of silently using a price in another currency.

`PriceQuote` carries the resolved plan id, currency, amount, source, and validity window so checkout code can retain an auditable pricing decision.

## Checkout rule

The catalog layer does not create orders or charge wallets. Checkout must resolve a purchasable plan and a price quote, then pass those values to the Order and Payment application services. This keeps pricing independent from payment gateways and service providers.

## Extensibility

A future catalog backend (for example, a remote catalog or cached read model) can implement `CatalogService` without changing consumers. A future pricing engine can implement `PricingService` without changing checkout orchestration, provided it respects the contract.

## Testing

`CatalogPricingTest` covers active hierarchy filtering, purchasable-plan rules, currency normalization, unavailable currencies, current price windows, and deterministic selection between overlapping applicable prices.
