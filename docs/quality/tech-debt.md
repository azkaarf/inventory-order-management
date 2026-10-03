# Tech-Debt Register

Honest record of shortcuts and known limitations, and what the ideal fix
would look like. Nothing here is hidden — it's either explained in a
comment at the relevant code, or listed here, or both.

## 1. One-off regex special case for the API-01 route

`public/index.php` handles `GET /api/products/{sku}/availability` with a
single `preg_match()` special case *before* the flat `$routes` lookup
table, instead of extending the router to support path parameters
generally.

**Why this shortcut:** the project has exactly one dynamic route. Building
a full parameterized router (regex compilation, named segments, etc.) for
one endpoint would be more routing machinery than the problem calls for —
the brief's own warning against unnecessary complexity applies directly
here.

**When to revisit:** the moment a second dynamic route is needed, this
special-case approach stops scaling and it's time to replace the flat
array with a small proper Route/Router abstraction.

## 2. Sales Orders are all-or-nothing on goods issue

Unlike Purchase Orders (`Draft → Ordered → PartiallyReceived → Received`),
Sales Orders have no `PartiallyFulfilled` status — `MySqlGoodsIssueRepository::issue()`
checks and locks every item's stock inside one transaction and rejects the
*entire* operation if even one item is short, rather than fulfilling what
it can and leaving the rest pending.

**Why this isn't a bug:** this matches the status enum given in the brief
(`Draft/PendingApproval/Approved/Fulfilled/Cancelled` — no partial state),
so partial fulfillment literally has nowhere to be represented. Treating
it as all-or-nothing was a deliberate reading of that constraint, not an
oversight.

**When to revisit:** only if a future requirement adds a partial-fulfillment
status to the schema.

## 3. No password-reset flow for users

`UserController` lets an Admin create and edit Sales/Warehouse Staff
accounts, but there's no "reset this user's password" action anywhere —
if someone forgets their password, an Admin currently has no in-app way to
help them.

**Why this shortcut:** USR-01's stated scope is add/view/edit/
activate-deactivate; password reset was never explicitly requested, and
adding it well (secure token generation, expiry, notification) is a
non-trivial feature in its own right.

**When to revisit:** before any real (non-demo) usage — this is a real
gap for actual operation, just outside this project's explicit scope.

## 4. Replacing a product's image leaves the old file on disk

`ProductController::update()` calls `ImageUploader::store()` for a new
image but never deletes the previously-stored file at the old
`image_path`, so `public/uploads/products/` slowly accumulates orphaned
files as products get their images changed.

**Why this shortcut:** time; it's a straightforward fix (delete the old
file if `$product->imagePath` is non-null and a new upload succeeded) but
wasn't prioritized over functional gaps.

**When to revisit:** any time before relying on disk space limits in a
real deployment — low risk at this project's scale.

## 5. Master-data lists (Users, Categories, Warehouses, Suppliers,
   Customers) have no pagination

FIND-01 explicitly scopes search/filter/sort/pagination to Products and
Orders (PO & SO) — master-data lists were deliberately left as plain
"show everything" tables.

**Why this isn't a bug:** it's exactly what FIND-01 asks for; these lists
are also expected to stay small (a handful of categories/warehouses/
suppliers/customers, and a small user roster), so an unpaginated table is
the right amount of complexity right now.

**When to revisit:** if any of these lists is ever expected to grow into
the hundreds of rows.
