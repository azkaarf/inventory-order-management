# Refactoring Log

Format for each entry: the smell found, the technique used, and a
before/after snippet.

## 1. Duplicate Code — HTML boilerplate in every view

**Found during:** Phase 1 (Auth & User), after `login.php`,
`placeholder.php` (dashboard), and the 3 views in `views/users/` all had
an identical `<!DOCTYPE html><html>...<head>...` block, plus the
"Manage Users" link and Logout form copy-pasted across several views.

**Technique:** Extract Method (as a partial/include) — the shared block
was moved into `views/layout/header.php` and `views/layout/footer.php`.
Every view now just `require`s them at the top and bottom instead of
repeating the boilerplate.

**Before** (`views/dashboard/placeholder.php`, trimmed):
```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
</head>
<body>
    <h1>Dashboard</h1>
    <p>Hello, ...</p>
    <?php if ($user['role'] === 'Admin'): ?>
        <p><a href="/users">Manage Users</a></p>
    <?php endif; ?>
    <form method="POST" action="/logout">
        <button type="submit">Logout</button>
    </form>
</body>
</html>
```

**After:**
```php
<?php
$pageTitle = 'Dashboard';
require __DIR__ . '/../layout/header.php';
?>
<h1>Dashboard</h1>
<p>Hello, ...</p>

<?php require __DIR__ . '/../layout/footer.php'; ?>
```

**Positive side effect:** navigation links (Dashboard/Manage
Users/etc.) and the Logout button are now defined once, in
`header.php` (driven by `$_SESSION['user']['role']`), instead of being
repeated manually in every view — changing the menu now only requires
editing one file.

## 2. Duplicate Code — `isValidDate()` copy-pasted into three Services

**Found during:** Phase 8 (documentation/hardening pass). A grep across
`app/Service/` turned up an identical private `isValidDate()` method —
same body, same `DateTime::createFromFormat('Y-m-d', ...)` check — in
`PurchaseOrderService`, `SalesOrderService`, and `DashboardService`. Each
one had been written independently as each feature was built, without
noticing the other two already existed.

**Technique:** Extract Class — moved the check into a new
`App\Support\DateValidator::isValid()` static method, and had all three
Services call that instead of keeping their own private copy.

**Before** (repeated identically in 3 files):
```php
private function isValidDate(string $date): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d !== false && $d->format('Y-m-d') === $date;
}
```

**After** (one shared implementation, called from all three):
```php
// App\Support\DateValidator
public static function isValid(string $date, string $format = 'Y-m-d'): bool
{
    $d = DateTime::createFromFormat($format, $date);
    return $d !== false && $d->format($format) === $date;
}

// in each Service:
if (!DateValidator::isValid($orderDate)) { ... }
```

Verified with PHPStan (level 5) after the change — still zero errors,
confirming nothing was left referencing the removed private methods.

## 3. Dead Code — `SalesOrderService::listOwnedBy()`

**Found during:** Phase 5 (FIND-01), while adding search/filter/pagination
to Sales Orders. The Controller previously called
`listOwnedBy($userId)` to restrict Sales users to their own orders, backed
by `SalesOrderRepositoryInterface::findAllByCreatedBy()`.

**Technique:** Remove Dead Code. Once `searchSalesOrders(...)` was added
with its own `$createdBy` parameter covering the exact same restriction
(plus search/filter/sort/pagination on top), `listOwnedBy()` and
`findAllByCreatedBy()` were never called from anywhere else. Rather than
leave an unused method sitting in the interface and two implementations,
both were deleted outright — an unused method is one an assessor (or a
future contributor) has to read, understand, and rule out as unused
manually.

**Before:**
```php
// SalesOrderService
public function listOwnedBy(int $userId): array
{
    return $this->salesOrderRepository->findAllByCreatedBy($userId);
}

// SalesOrderRepositoryInterface + MySqlSalesOrderRepository
public function findAllByCreatedBy(int $userId): array { ... }
```

**After:** both methods removed entirely; `SalesOrderController::index()`
calls `searchSalesOrders($q, $status, $sort, $page, $createdBy)` instead,
passing `$createdBy` only when the current user's role is Sales.

## 4. JS modernization + Separation of Concerns — `sales-orders/show.php`'s inline script

**Found during:** audit against the JavaScript training module's topic
list. The app's only client-side script (a small stock-availability
widget) was written in pre-ES6 style (`var`, classic `function`,
`.then()` chains) and mixed pure formatting logic together with DOM/fetch
code in one inline block — nothing wrong functionally, but it didn't
reflect current JS idioms and had no unit-test surface at all.

**Technique:** Extract Class/Module (the pure parts) + Replace Conditional
with modern syntax throughout. `formatAvailabilityText()` and a new
`StockLookupError` class were pulled out into a standalone,
dependency-free module (`public/assets/js/stock-availability.js`) that
runs equally under a browser `<script>` tag or Node/Jest (UMD-style
export). The remaining inline script — DOM binding, `fetch`, caching,
retry — stays page-specific glue code, now written with arrow functions,
`const`, destructuring, and `async`/`await` instead of the previous
`var`/`function`/`.then()` style.

**Before** (excerpt):
```js
fetch('/api/products/' + encodeURIComponent(sku) + '/availability')
    .then(function (response) {
        if (!response.ok) { throw new Error('lookup failed'); }
        return response.json();
    })
    .then(function (data) {
        var parts = data.per_warehouse.map(function (w) {
            return w.warehouse_name + ': ' + w.quantity;
        });
        stockInfo.textContent = 'Available stock — ' + parts.join(', ') + ' (total: ' + data.total + ')';
    })
    .catch(function () {
        stockInfo.textContent = 'Could not check stock for this product.';
    });
```

**After** (excerpt):
```js
try {
    const data = await fetchAvailability(sku);
    writeCache(sku, data);
    stockInfo.textContent = formatAvailabilityText(data);
} catch (error) {
    stockInfo.textContent = error instanceof StockLookupError && error.code === 'NOT_FOUND'
        ? 'This product could not be found.'
        : 'Could not check stock for this product.';
}
```

**Why now, not earlier:** this is a judgment call worth making
explicit (Bab 12.1's own point — prioritize by evidence, not by
"newer is always better"). The old version worked correctly and had
zero reported bugs; the trigger here was wanting the pure formatting
logic to be unit-testable (`tests/js/stock-availability.test.js`, 6
Jest tests, all passing) and wanting failure modes (404 vs. transient
network error) to be distinguishable, which the flat `.catch()` before
couldn't do. Modernizing syntax was a side effect of that split, not
the goal on its own — rewriting working code purely for style would
have been change without evidence behind it.
