---
paths:
  - "app/Services/CheckWinService.php"
  - "app/Services/RiskCapService.php"
  - "app/Services/LockTicketService.php"
  - "app/Services/DrawService.php"
  - "app/Services/OrderService.php"
  - "app/Services/AgentAccountService.php"
  - "app/Http/Controllers/Order/**"
  - "app/Http/Controllers/Product/CheckWinController.php"
  - "app/Http/Controllers/Product/DrawController.php"
  - "app/Http/Controllers/Api/CheckWinnerController.php"
  - "app/Http/Controllers/Api/OrderController.php"
  - "app/Http/Controllers/Accounts/**"
  - "app/Http/Controllers/Report/**"
---

# Money, winners & risk — high-stakes code

This code decides who gets paid. Treat every change as financial.

1. **Never alter behaviour silently.** State in the reply exactly what changes in payout/winner/ledger outcomes.
2. **Snapshots are immutable:** order `commission`, `vat`, `*_percentage`, `total_price`, `draw_number` are captured at sale time. Never recompute from current agent/company settings on historical orders.
3. **Winner eligibility** (must stay consistent across `CheckWinService`, `CheckWinController`, `Api/CheckWinnerController`, `WinnerReport*`, `ProbableWins`, `LockTicketService`): order `status='Printed'`, `is_claimed=0`, ticket not risk-held/cancelled (`withoutRiskHold()`), order created within Win `from_time..to_time`, Win `publish=1`. If you change the rule in one place, grep and change all.
4. **Play types:** Straight (exact order), Rumble (same digits any order; only if not Straight-winner), Chance N (match last-N digits reversed). Don't double-pay one ticket for multiple play types unless the existing code does.
5. **Double-claim / double-post:** claim, cancel-accept, ledger posting, risk hold/release must run inside `DB::transaction` with `lockForUpdate()` on the order/ticket rows and re-check state after locking.
6. **Risk cap:** prize amount is never reduced; rows are dropped to fit the group budget (`RiskCapService`). Group key = product_id|draw_number|user_id.
7. **Numbers:** money is `decimal(…,2)`; do not use floats for comparisons/sums — use `bcmath` or cast carefully, round only at presentation.
8. **Null-safety:** invoice/agent/company_setting lookups can be missing — return a clean 404/422 instead of a 500.
9. **Idempotency:** retrying an API call (mobile network) must not create a second order/claim.
10. Any change here needs a Pest test (see `testing.md`) covering at least: winning, non-winning, already-claimed, held ticket, outside time window.
