# AI Usage Log

Per §6.2 of the brief: DISCLOSE, REVIEW, VERIFY, TEST. This log discloses
tool, purpose, and a sanitized summary of what was asked/generated for
every phase of the project. **The "Reviewed / Verified / Tested by me"
column is intentionally left for you to fill in honestly** — I can state
what was generated and what you told me you ran, but only you know how
carefully you personally read, understood, and re-verified each piece
before accepting it. Please don't leave any row blank before submission;
a one-line note ("read the diff, ran the suggested commands, confirmed
in browser") is enough per row.

**Tool used throughout:** Claude (claude.ai chat), Sonnet-class model.
**No other AI tool was used** (fill in / correct if that's inaccurate).

| Phase | Purpose | Prompt summary (sanitized) | Output used / rejected | Reviewed / Verified / Tested by me |
|---|---|---|---|---|
| Planning | Initial project plan and phase breakdown for the whole brief | Asked for a step-by-step plan to build the Inventory & Order Management System from the brief | Used: the phased build order (Login → Master Data → PO → SO → Dashboard/Report), matching the brief's own recommended sequence | _fill in_ |
| Setup | Environment setup guidance (Docker, Git, editor) | Asked what applications/tools are needed before starting | Used: tool checklist; installed Docker Desktop, Git, VS Code as suggested | _fill in_ |
| Fase 0 | Docker/Compose skeleton, Dockerfile, composer.json, .gitignore, smoke-test index.php | Asked for a working skeleton to start from | Used: all generated files, after placing them and running `docker compose up --build` myself | _fill in_ |
| Fase 1 | Auth (login/logout/session guard), User CRUD, Repository pattern proof (User + fake) | Asked to implement AUTH-01, AUTH-02, USR-01 | Used: all generated code. **Found and reported a real bug myself** (self-demoted the only Admin account via the Edit User form) — fix (last-active-admin guard) was then generated and I re-tested the scenario | _fill in_ |
| Fase 2 | Master data: Category, Warehouse, Product (+ image upload, stock init), Supplier, Customer | Asked to implement PRD-01, WH-01, plus supplier/customer CRUD | Used: all generated code, across several placement/debugging rounds (missing-file errors that I reported and got fixed) | _fill in_ |
| Fase 3 | Purchase Order + Goods Receipt, transactional stock write | Asked to implement PO-01 | Used: all generated code | _fill in_ |
| Fase 4 | Sales Order, Approval (segregation of duties), Goods Issue, concurrency-safe locking, JS/API-01 | Asked to implement SO-01 and ARCH-02, tie in the JS/API requirement meaningfully | Used: all generated code | _fill in_ |
| Fase 5 | Search/filter/sort/pagination (FIND-01), 30-product + 25-order seed data | Asked to implement FIND-01 and generate the seed data volume the brief requires | Used: all generated code and seed data | _fill in_ |
| Fase 6 | Dashboard per role (DASH-01), CSV reports (REPORT-01), dashboard visual redesign | Asked to implement DASH-01/REPORT-01, then separately asked for a better-looking dashboard | Used: all generated code | _fill in_ |
| Hardening | VAL-01/ERR-01 pass: preserved form input on validation failure, global exception handler | Asked to audit and close gaps in validation/error-handling | Used: all generated code | _fill in_ |
| JOB-01 | Standalone low-stock CLI script | Asked to implement JOB-01 | Used: generated script | _fill in_ |
| TEST-03 | Static analysis | Asked to run PHPStan/PHPCS and report results | The AI ran these tools directly against a mirror of the codebase (not inside the project's own Docker container) and reported the real output; **please re-run `vendor/bin/phpstan` and `vendor/bin/phpcs` yourself inside the actual container before submission** to confirm the same result on your real environment | _fill in_ |
| DESIGN-01/02/03 | As-built class diagram, 3 ADRs, refactor log (2 additional real entries), SRP audit, tech-debt register | Asked to close documentation gaps identified in a self-review of the brief's checklist | Used: all generated docs. One entry (`isValidDate()` duplication) was a **real refactor actually applied to the code**, not just written up — re-verified with PHPStan afterward | _fill in_ |

| Security hardening | CSRF token on all POST forms, log-poisoning mitigation, isolated test database | Asked to audit the project against a PHP security training module and implement genuinely applicable gaps | Used: all generated code (new `Csrf`/`LogSanitizer` classes, token injected into 32 forms across 24 views, `inventory_test` DB separated from dev) | _fill in_ |
| PHP modernization | Heredoc/nowdoc, `array_combine` refactor, CLI script profiling, example ALTER TABLE migration | Asked to implement genuinely-applicable gaps found by comparing the project against a PHP training syllabus | Used: all generated code. Declined several training-topic items (bitwise operators, pass-by-reference, abstract classes) because they had no natural fit in this codebase — documented as an explicit, reasoned decision rather than silently skipped | _fill in_ |
| CSS/JS modernization | z-index tokens, `clamp()`/`min()`, radio buttons, JS arrow/async-await/custom-error modernization, Jest test setup | Same self-audit approach, against a CSS/Styling and a JavaScript training syllabus | Used: all generated code. **Caught and self-corrected a real mistake**: a `str_replace` meant to add a new Service method accidentally deleted an existing one (`searchSalesOrders()`) — found via `php -l` before it reached you, fixed in the same turn | _fill in_ |
| UI redesign | Full layout split (sidebar/topbar/page-header), dashboard progress bars, table toolbar + CSV export + product thumbnails, login page redesign (show/hide password, working remember-me) | Asked to restyle the app toward a reference admin-theme screenshot, keeping native CSS (no framework) | Used: all generated code, across ~45 files | _fill in_ |
| Docker review | `.dockerignore` creation, Dockerfile/compose explanation script for presentation, honest gap-finding (missing app healthcheck, layer-order inefficiency) | Asked to audit the existing Docker setup against a Junior+Intermediate best-practice checklist | Used: new `.dockerignore`; the two gaps found were reported, not silently fixed, so they could be mentioned as self-identified improvement areas in the presentation | _fill in_ |
| Documentation recovery | Re-generated `docs/architecture/`, `docs/quality/` (partial), `docs/planning/erd.md` + `class-diagram-initial.md`, `ai-usage-log.md` after a `tree /F` listing showed they were missing from the actual project folder | Asked to cross-check the real folder structure against the brief's requirements | Used: files were re-delivered from this chat's own working history (not rewritten from scratch) after discovering they'd apparently never been placed in the real project folder — **please double check they're actually saved this time** | _fill in_ |
| ERD re-render | Converted the ERD from Mermaid-in-Markdown (which didn't render in your viewer) to an actual PNG image via Graphviz | Asked to make the ERD visible/clearer after a previous version couldn't be viewed | Used: generated `erd.png`, embedded in `erd.md`. **Caught and fixed a real inaccuracy while building it**: the first draft collapsed `sales_order.created_by` and `sales_order.approved_by` into a single `user → sales_order` line; corrected to show both distinct foreign keys | _fill in_ |
| `docs/testing/` | New test-report.md documenting all 33 existing test cases by scenario, mapped to requirement IDs | Asked to draft this after confirming it was the one genuinely-never-built required doc | Used: generated doc, built from the actual test method names in the repo, not invented | _fill in_ |

## Things I have not asked AI to do (for you to confirm/adjust)

- I have not personally traced every generated class back to the brief's
  requirement text line-by-line beyond what's summarized above.
- I have not independently re-derived the SQL in every Repository method to
  confirm correctness beyond running the app and its test suite.
- Add any other AI-assisted work you did outside this chat (if any) as
  additional rows above.
