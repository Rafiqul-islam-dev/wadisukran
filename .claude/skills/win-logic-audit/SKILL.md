---
name: win-logic-audit
description: Audit winner-matching, claim, risk-cap and ledger logic for consistency and money bugs (read-only unless told to fix). Use before/after changing CheckWinService, claims, draws, lock tickets, or payout reports.
---

# Winner / claim / risk audit (read-only by default)

Goal: confirm every place that decides "is this ticket a winner / payable" applies the **same** rules.

1. Locate all call sites: `grep -rn "publish\|is_claimed\|withoutRiskHold\|from_time\|win_number" app/`. Main ones: `CheckWinService`, `Product/CheckWinController`, `Api/CheckWinnerController`, `Product/DrawController`, `Report/WinnerReport*`, `Order/OrderController::probableWins*`, `LockTicketService`, `RiskCapService`.
2. Compare eligibility predicates (see `.claude/rules/domain-money-and-wins.md` §3). List any divergence as a table: location | predicate | difference.
3. Check play-type logic: Straight / Rumble / Chance — operator bugs (`&` vs `&&`), type juggling (`"01"` vs `1`, `array_map('intval')` dropping leading zeros), reversed/sorted comparisons, `chance_number` ordering.
4. Check claim path: transaction? `lockForUpdate`? re-check `is_claimed` after lock? ledger posting (`AgentAccountService`) atomic with the claim? `max_win_amount` enforced?
5. Check risk path: hold/release/cancel idempotent? `RiskCapService` budget math (decimals, commission, net_sale, group key)?
6. Check null/edge: missing invoice, unpublished win, order outside window, soft-deleted product, ticket in multiple wins.
7. Report findings ranked by money impact: `file:line`, failing scenario, suggested fix, suggested Pest test. **Do not edit code** unless the user explicitly asks to fix.
