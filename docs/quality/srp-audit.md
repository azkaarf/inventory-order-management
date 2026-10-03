# SRP Audit Note

**Class examined:** `UserService`, specifically the rule that the last
active Admin account can't be demoted or deactivated.

**What almost went wrong:** the natural first instinct when adding this
rule (discovered after a real incident — see below) is to write it
directly inside `UserController::update()` / `toggleActive()`, right next
to the `$_POST` handling: count the active admins with a quick query,
compare, bail out with an error if it's the last one. That keeps the fix
"close to where the bug was noticed," but it mixes two responsibilities
into the Controller: *handling an HTTP request* and *enforcing a domain
business rule*. It also would have meant writing the same admin-count
check twice — once for the role-change path, once for the deactivate
path.

**How it's actually split:** the rule lives entirely in `UserService`, as
one private method:

```php
private function isLastActiveAdmin(int $id): bool
{
    $target = $this->userRepository->findById($id);
    if ($target === null || $target->role !== 'Admin' || !$target->isActive) {
        return false;
    }
    $activeAdminCount = count(array_filter(
        $this->userRepository->findAll(),
        fn (User $u) => $u->role === 'Admin' && $u->isActive,
    ));
    return $activeAdminCount <= 1;
}
```

Both `validateForUpdate()` (blocks a role change away from Admin) and
`setActive()` (blocks deactivation) call this one method. `UserController`
never sees the rule at all — it just checks the boolean result
(`validateForUpdate()`'s errors array, or `setActive()`'s return value)
and turns that into an HTTP redirect. If the rule ever changes (e.g. "at
least 2 active admins" instead of 1), there is exactly one place to
change it, and the Controller doesn't need to change at all.

**Why this matters concretely:** this rule exists *because* of a real
bug hit during manual testing — an Admin test account was accidentally
demoted to WarehouseStaff via the Edit User form, which would have
locked that account out of `/users` entirely with no way back in through
the UI. Keeping the fix in the Service (rather than scattered across
Controller actions) is what makes it possible to guard both the "change
role" and "deactivate" paths with the same rule instead of two
independent, potentially-inconsistent copies.
