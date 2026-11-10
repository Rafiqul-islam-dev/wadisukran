---
name: write-tests
description: Write Pest tests for this project's domain logic (orders, winners, claims, risk caps, permissions, API). Use when asked to add tests or when changing logic that has none.
---

# Write Pest tests

1. Read `.claude/rules/testing.md` and `phpunit.xml`; confirm the test DB is isolated (sqlite memory or dedicated MySQL) before running anything that migrates.
2. Build fixtures with factories (create missing ones in `database/factories/`): User (agent + staff via spatie roles/permissions), Agent (commission), CompannySetting (vat, order_status=1, max_win_amount), Category, Product + ProductPrize (Straight/Rumble/Chance), Win (`publish=1`, window), Order + OrderTicket.
3. `beforeEach`: `cache()->forget('company_setting')`; seed permissions needed for the route under test.
4. Cases, per target:
   - **CheckWinService:** straight win, rumble win (not when straight), chance N tiers, non-win, already claimed, risk-held ticket, order outside window, unpublished win, leading-zero numbers.
   - **OrderService / API orders:** commission & VAT snapshot, draw_number = last published + 1, `order_status=0` → 403, inactive product → 403, validation 422.
   - **Claim:** double-claim rejected, ledger posting once, max_win_amount.
   - **Permissions:** 403 without permission, 200 with.
5. Run `php artisan test --filter=<name>` and report real output. If a test exposes a real bug, report it — don't weaken the assertion.
