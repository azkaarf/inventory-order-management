# ADR-0002: Pessimistic Locking (`SELECT ... FOR UPDATE`) for Concurrency-Safe Stock Operations

## Context

ARCH-02 requires that two goods-issue (or goods-receipt) operations against
the same product+warehouse, processed nearly simultaneously, never oversell
stock and never silently overwrite each other's update. Two standard
strategies exist: **pessimistic locking** (take a row lock before reading,
so a second transaction physically waits) or **optimistic locking** (add a
version/timestamp column, write conditionally on it being unchanged, and
retry on conflict).

## Decision

We chose pessimistic locking: inside one transaction
(`beginTransaction()` / `commit()` / `rollBack()`), lock the affected
`product_stock` row with
`SELECT quantity FROM product_stock WHERE product_id = ? AND warehouse_id = ? FOR UPDATE`,
check availability using that locked read, and only then write. This is
implemented identically in `MySqlGoodsReceiptRepository::receive()` and
`MySqlGoodsIssueRepository::issue()`.

## Consequences

- Simple to reason about and to explain at defense: a second concurrent
  request against the *same* row physically blocks at the `FOR UPDATE`
  statement until the first transaction commits or rolls back, then reads
  the now-current quantity — no retry loop needed, and no possibility of
  two transactions both reading "10 available" and both deciding to
  proceed.
- Matches the requirement's own description of the proof needed almost
  word-for-word: "permintaan kedua ditolak atau ditunda ketika stok sudah
  habis oleh permintaan pertama" — this is exactly what a blocked-then-
  re-read lock produces.
- Trade-off: under heavy write contention on the *same* row, requests
  serialize (queue up) rather than failing fast. At this project's scale
  (a handful of warehouses, demo-level concurrency) that's a non-issue; a
  high-throughput system with many concurrent writers hammering the same
  hot row might prefer optimistic locking with backoff instead, to avoid
  threads piling up waiting on a lock.
- This discipline (always lock before checking, inside one transaction)
  only needs to be maintained in exactly two places —
  `MySqlGoodsReceiptRepository` and `MySqlGoodsIssueRepository` — because
  those are the only two code paths that ever mutate `product_stock`. No
  other Repository writes to that table.
