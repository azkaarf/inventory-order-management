# Static Analysis Report (TEST-03)

Both tools were run directly against the full source tree (`app/`, `config/`,
`public/`, `scripts/`) — not against a partial selection.

## PHPStan — level 5 (the brief's minimum)

```
$ vendor/bin/phpstan analyse --level=5 app public config scripts

 [OK] No errors
```

**Zero errors at level 5.** No critical error, no warning to explain at this
level.

### Bonus check: level 8 (informational only, not required)

Out of curiosity we also ran level 8 (well above the required level 5).
That surfaces 56 findings, but all of them come from **one single root
cause**: the router in `public/index.php` dispatches routes as
`[$controller, 'method']` pairs and calls `$controller->$action()`
dynamically. Because the `$routes` array mixes many different controller
classes together, PHPStan's stricter levels infer `$controller`'s type as a
union of *every* controller class in the array, then (correctly, from a
purely static point of view) complains that not every method exists on
every class in that union — e.g. `UserController` doesn't have a `show()`
method, only some of the other controllers do.

This isn't a real bug: each array entry is only ever invoked with the
controller/method pair it was written with, and our own test suite
exercises every route. It's an inherent limitation of this deliberately
lightweight, framework-free router (a full typed-route object or a
per-controller dispatch table would resolve it, but would also be more
routing machinery than this project's single-endpoint API needs — see the
tech-debt register). We're noting this rather than silently ignoring it,
but it does not affect the level 5 result the brief asks for, which is
clean.

## PHP_CodeSniffer — PSR-12

```
$ vendor/bin/phpcs --standard=PSR12 app config public scripts

FOUND 0 ERRORS AND 15 WARNINGS
```

**Zero errors.** All 15 warnings are the exact same rule: `Line exceeds 120
characters`. Every single one is a multi-line SQL query string inside a
Repository class (e.g. the `JOIN`-heavy `SELECT` statements in
`MySqlSalesOrderRepository`, `MySqlPurchaseOrderRepository`, and the search
queries in `MySqlProductRepository`). These are left as one logical line
per query on purpose — splitting a SQL string across multiple PHP string
concatenations to satisfy a line-length rule would make the SQL itself
harder to read for no functional benefit, which is a worse trade-off than
the warning itself.

## Summary

| Tool | Level/Standard | Errors | Warnings |
|---|---|---|---|
| PHPStan | 5 (required minimum) | 0 | 0 |
| PHPCS | PSR-12 | 0 | 15 (all explained above, same cause) |

Both tools report **zero critical errors**, satisfying TEST-03.
