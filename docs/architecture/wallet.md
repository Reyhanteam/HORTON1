# Wallet & Transaction Ledger

## Responsibility

The wallet domain owns user balances and the append-only financial ledger. Payment, order, refund, manual balance adjustment and future financial features must mutate a wallet through `WalletService`; they must never update `wallets.balance` directly.

## Architecture

```text
Application Action / Payment / Dashboard
                |
                v
        WalletService Contract
                |
                v
      AbstractWalletService
                |
                v
      DatabaseWalletService

Read / reconciliation
                |
                v
          WalletLedger
                |
                v
      AbstractWalletLedger
                |
                v
      DatabaseWalletLedger
```

Contracts describe capabilities. Abstract classes enforce shared invariants. Concrete services contain persistence details.

## Balance rules

- Monetary amounts are stored as integer minor units (for the configured currency).
- Currency is normalized to an uppercase three-letter code.
- A wallet is unique per user and currency.
- Only active wallets can be mutated.
- Credit/debit amounts must be positive.
- Debit cannot make a wallet negative.
- Every mutation and balance change are committed in one database transaction.
- The wallet row is locked with `lockForUpdate()` before calculating the new balance.
- Idempotency keys are unique. Replaying the exact same mutation returns the original transaction; reusing the key for a different mutation is rejected.

## Append-only ledger

`wallet_transactions` records `balance_before` and `balance_after` for every mutation. Eloquent updates/deletes are rejected by the model so application code cannot silently rewrite financial history.

Each transaction also contains a SHA-256 hash chained to the previous transaction of the same wallet:

```text
transaction N previous_hash = ledger_hash(N-1)
transaction N ledger_hash    = SHA256(canonical transaction data + previous_hash)
```

`WalletLedger::reconcile()` locks the wallet, rebuilds the balance chain and verifies every hash. This detects direct database tampering or an inconsistent wallet balance. It is an integrity/audit mechanism, not a replacement for database access controls and backups.

## Reconciliation

Use `WalletLedger` for:

- transaction history
- balance reconciliation
- ledger hash verification
- identifying the first invalid transaction

A missing wallet is considered an empty wallet during reconciliation.

## Migration safety

The ledger-hash migration backfills existing wallet transactions in ID order before the new integrity fields are used by the application. This allows the feature to be introduced on a non-empty database without invalidating historical records.

## Future financial operations

Refunds, wallet deposits, wallet purchases and administrative balance changes should call the wallet application service with an explicit type, reference, description and idempotency key. The caller owns the business reason; the wallet layer owns atomic balance mutation and ledger integrity.
