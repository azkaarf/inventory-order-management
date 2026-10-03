# ADR-0001: Repository Pattern with Dependency Inversion (instead of direct PDO in Controllers/Services)

## Context

Business logic (login validation, order status rules, the last-active-admin
guard, etc.) needs to read and write persistent data, but ARCH-01 requires
that this logic not depend directly on PDO, `$_SESSION`, or other
superglobals — and that at least one Repository be provable as testable
without a real database connection.

## Decision

Every Controller depends on a Service; every Service depends on a
**Repository interface**, never a concrete class. Concrete `MySql*Repository`
classes implement those interfaces and are the only place raw SQL/PDO calls
live. For `User` specifically — the module chosen to prove the pattern —
we also built `InMemoryUserRepository`, a second implementation of
`UserRepositoryInterface` backed by a plain PHP array, used exclusively by
`tests/Unit/AuthServiceTest.php` and `UserServiceTest.php`.

## Consequences

- `AuthService`/`UserService` business logic is fully unit-testable without
  a real database connection — swap in the fake, no Docker/MySQL needed for
  those tests to run.
- Swapping the persistence layer later (a different DB engine, an external
  API, whatever) would only require a new Repository implementation; no
  Service or Controller code would need to change, because they only know
  about the interface.
- Trade-off: extra files per entity (an interface plus an implementation)
  compared to calling PDO directly from a Controller. We did **not** build
  a second (fake) implementation for every Repository — Category,
  Warehouse, Supplier, and Customer each only have a single `MySql*`
  implementation. ARCH-01 only requires *one* Repository to demonstrate the
  dual-implementation pattern; building 8 parallel in-memory fakes for
  Services with no complex validation logic to isolate from the database
  would be duplicated boilerplate with no real testing benefit — exactly
  the kind of unnecessary complexity the brief warns against.
