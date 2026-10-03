# ADR-0003: The Transaction Boundary Lives in the Repository, Not the Service

## Context

Goods receipt and goods issue each need to atomically touch multiple
tables — `stock_ledger`, `product_stock`, the order item table, and the
order's own status column — in one transaction. ARCH-01 says Services must
not depend on PDO directly, but *something* has to call
`beginTransaction()` / `commit()` / `rollBack()`.

## Decision

The entire multi-step atomic write — lock, check availability, update
stock, write the ledger row, recompute and update order status — lives
inside **one Repository method**: `MySqlGoodsReceiptRepository::receive()`
and `MySqlGoodsIssueRepository::issue()`. The Service
(`PurchaseOrderService::receiveItem()` / `SalesOrderService::processGoodsIssue()`)
just calls that one method with plain scalar arguments; it never opens a
transaction or touches `Database::connection()` itself.

## Consequences

- `PurchaseOrderService` and `SalesOrderService` stay completely free of
  PDO/transaction code, matching ARCH-01's rule to the letter, not just
  its spirit — a Service constructed with a fake Repository could never
  accidentally start a real transaction.
- The atomic operation is easy to locate and reason about: it isn't split
  across a Service method that "orchestrates" several separate Repository
  calls inside a transaction *it* manages — the whole business transaction
  lives in exactly one place, which is also exactly the method that
  `GoodsReceiptIntegrationTest` / `GoodsIssueIntegrationTest` exercise
  against a real database.
- Trade-off: this means the Repository method contains a decision — "is
  there enough stock?" — that arguably reads like business logic rather
  than pure data access. We accepted this deliberately: that availability
  check must happen *inside* the same lock as the write, or the
  check-then-write race ARCH-02 exists to prevent would reopen between the
  check and the write. The only way to keep the check and the write inside
  one lock without the Service itself managing the transaction (which
  would violate ARCH-01 instead) is for both to live in the same
  Repository method. We chose to bend "no business logic in the
  Repository" slightly rather than bend "no PDO in the Service" — a
  conscious trade-off, not an oversight.
