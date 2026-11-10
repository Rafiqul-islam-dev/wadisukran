---
paths:
  - "tests/**/*.php"
  - "phpunit.xml"
---

# Testing (Pest 3)

- Current coverage is only starter-kit auth/settings. New domain work should add tests under `tests/Feature/<Area>/` and pure-logic tests under `tests/Unit/`.
- Tests run with `php artisan test` / `composer test`. Check `phpunit.xml` for the test DB (never point tests at the real MySQL dev DB with data you care about — use `RefreshDatabase` with sqlite in-memory or a dedicated test DB).
- Seed what you need explicitly in the test (roles/permissions via spatie, a `CompannySetting` row, agent user, product with prizes). `company_setting()` is cached — clear cache in `beforeEach`.
- Priority test targets (none exist yet): `CheckWinService` (Straight/Rumble/Chance, held tickets, time window, claimed), `OrderService::createOrder` (commission/VAT snapshot, draw_number), `RiskCapService`, claim flow + ledger, API auth + `orders` endpoint, permission middleware on key routes.
- Use Pest style (`it()`, `expect()`); use factories (add `OrderFactory` etc. in `database/factories` when needed).
- A bug fix should come with a regression test that fails before and passes after.
- CI: `.github/workflows/tests.yml.disabled` is disabled; only lint runs. Don't claim "CI passed" for tests.
