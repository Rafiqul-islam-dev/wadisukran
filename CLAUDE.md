# Taqdheer (Wadi Shukran) — Project Guide

Draw/lottery ticket sales platform. Admin panel (Inertia + Vue) for staff/agents, plus a Sanctum-protected mobile/POS API (`/api/v1`) that agents use to sell tickets. Money, winners and payouts are handled here — correctness beats speed.

## Stack
- **Backend:** Laravel 12, PHP ^8.2 (CI uses 8.4), MySQL (local `.env`), Sanctum (API tokens), spatie/laravel-permission (roles/permissions), DomPDF (PDF reports), simple-qrcode (invoice QR), Ziggy (named routes in JS).
- **Frontend:** Vue 3 + TypeScript + Inertia 2, Vite 6, Tailwind 4, shadcn-vue style UI (`reka-ui`, `components/ui/*`), lucide icons, vue-sonner toasts, dayjs. SSR entry exists (`resources/js/ssr.ts`).
- **Tests:** Pest 3 (only Laravel starter-kit auth/settings tests exist — no domain tests yet).
- **Style:** Pint (PHP), Prettier (4 spaces, single quotes, width 150, tailwind + organize-imports plugins), ESLint 9. `.editorconfig`: LF, utf-8.
- **Deploy:** `deploy.sh` (git pull → composer --no-dev → migrate --force → npm ci/build → optimize:clear) on a cPanel-style host; production assets served under `public/` via `static_asset()`.

## Commands
```
composer dev            # php serve + queue:listen + vite
composer test           # config:clear + php artisan test (Pest)
vendor/bin/pint         # PHP format
npm run dev | build | build:ssr
npm run format | lint   # prettier / eslint --fix
php artisan migrate
```

## Architecture (app/)
- `Http/Controllers/<Area>/` — thin-ish controllers by area: Accounts, Agent, Api (v1 mobile), Banner, Category, Dashboard, Order, Payment, Product (+Draw, CheckWin), Report, Role, Settings, User.
  - ⚠ `Order/OrderController.php` is ~2200 lines (orders list, print, PDFs, probable wins). Don't grow it; extract into services when touching it.
- `Services/` — business logic: `OrderService` (create order + tickets + QR + commission/VAT), `CheckWinService` (winner matching), `DrawService`, `LockTicketService` + `RiskCapService` (fraud/risk detection), `AgentAccountService` (ledger), `ProductService`, `CustomerService`, `PermissionService`, `QrCodeService`.
- `Models/` — Order → OrderTicket (1:N); Product → ProductPrize, Category; Win (published draw result, `win_number` JSON); Claim; Agent (+AgentAccount ledger, AgentBill, Incentive); RiskCapGroup; CompannySetting (sic — single-row global settings: vat, order_status, max_win_amount, phone_require, country_code); Customer; Banner; User (HasRoles, HasApiTokens, SoftDeletes, `user_type`: agent/staff…).
- `Helpers/helpers.php` (autoloaded): `static_asset()`, `company_setting()` (cached 30 min — **bust cache when settings change**).
- `Http/Middleware/isActive` — logs out non-`active` users. Web group: `auth, verified, isActive`.
- Frontend: `resources/js/pages/<Area>/*.vue` map 1:1 to `Inertia::render('<Area>/<Page>')`. Permission checks in UI via `can()` in `resources/js/helpers/permissions.ts` (reads `auth_permissions` shared prop). Alias `@` → `resources/js`.

## Domain model (read before touching order/win/risk code)
1. Agent sells an **Order** (invoice_no, qty, total_price, `draw_number`, commission %/amount and VAT %/amount snapshotted at sale time) containing N **OrderTickets** (`selected_numbers` JSON, `selected_play_types` JSON: Straight / Rumble / Chance / …).
2. `draw_number` = last *published* `Win` draw_number for the product + 1.
3. Admin publishes a **Win** (`win_number` JSON, `from_time`..`to_time` window, `publish` flag). `CheckWinService` matches tickets whose order is `status='Printed'`, `is_claimed=0`, not risk-held, created inside the Win window. `prize_type === 'bet'` products use Straight/Rumble/Chance rules from `ProductPrize`.
4. Claim flow: `Claim` row + `AgentAccount` ledger posting; payouts bounded by `max_win_amount`.
5. **Risk/Lock tickets:** `LockTicketService` groups tickets by Product+Draw+Agent to detect suspicious always-win groups; tickets can be held/released/cancelled (`OrderTicket.risk_status`); `RiskCapService` caps payable amount per group (prize amount itself never altered, rows are dropped to fit budget).
6. Cancel flow: agent requests cancel → staff `CancelRequestController::acceptCancel`.

## Hard rules
- **Do not modify `.env` or print its values.** Never commit secrets.
- Never change historical migrations; add new ones. Check `database/old_migrations/` is legacy.
- Money/prize/winner/ledger/risk code: read `.claude/rules/domain-money-and-wins.md` first; wrap multi-write flows in `DB::transaction` and lock rows (`lockForUpdate`) where double-claim/double-post is possible.
- Every new web route needs a spatie `can:` middleware and the permission must be added to the seeder (`CreateSuperAdmin`) — see `.claude/rules/security-permissions.md`.
- Follow existing conventions; don't refactor unrelated code in a feature change.

## Known issues observed during initial analysis (not fixed — report, don't silently change)
- `CheckWinService` uses bitwise `&` (`$type->name === 'Straight' & in_array(...)`) where `&&` is intended; `CheckWinByInvoice` dereferences `$invoice` without null check.
- `OrderService::createOrder` isn't transactional (Order + tickets + QR) and uses `$agent->commission` without null check; invoice number = `YmdH` + `random_int` (collision possible; DB unique index is the only guard).
- Missing `can:` middleware on `products`, `slides`, `company-settings`, `orders`, `customers/top-ten`-style routes, payments, reports; `roles.delete` uses `role update` permission; `permissions` route group fine.
- `routes/web.php` GET routes mutate state (`status-change`, `restore`, `histories/delete`, `histories/publish`, `products-permanent-delete`, `login-as-agent`) — CSRF-unsafe pattern.
- Stray import `Pest\ArchPresets\Custom` in `OrderService`; leftover `dd()` comments and `console.log` (`CheckWin/Index.vue`, `ProbableWins.vue`); backup files `OrderController.php-bakup`, `ProbableWins.vue-bakup`; `Order::$fillable` still lists dropped `game_cards`.
- `.github/workflows/tests.yml.disabled` — CI runs lint only; domain logic has zero tests.
- Working tree had uncommitted churn at analysis time (`composer.lock`, `bootstrap/cache/services.php`, deleted `eslint.config.js`, deleted `public/storage/.gitignore`) — verify before committing; `npm run lint` needs `eslint.config.js`.
- Windows/Laragon: git may report "dubious ownership" → `git config --global --add safe.directory H:/laragon/www/taqdheer`.

## Active workflows (plans)
- `.claude/workflows/sales-point-registration.md` — Agent → "Registration & Agreement" (form + approval + generated 1-page agreement PDF). **Implemented (Phases 0-5)**; Phase 6 (QA/release) pending. Read it before touching anything related.
  - Code: `Agent/SalesPointRegistrationController`, `SalesPointRegistrationService` (create/update/approve/reject/regeneratePdf + audit `record()`), `SalesPointAgreementPdfService` (mPDF, Arabic via Tajawal), models `SalesPointRegistration*`, `config/sales_point_agreement.php` (frozen terms), `private` disk (`storage/app/private`, never web-accessible), pages `resources/js/pages/Agent/Registration/*`.
  - Rules: any user with `approve sales point registration` (e.g. Super Admin) may approve/reject ANY registration incl. their own; approved records are immutable; sensitive IDs are encrypted; terms are frozen at submission.
  - Tests need MySQL: `DB_CONNECTION=mysql DB_DATABASE=taqdheer_test php artisan test --filter=SalesPoint` (an old migration is MySQL-only). Run Pint/Prettier only on touched files.

## Claude setup in this repo
- Rules: `.claude/rules/*.md` · Skills: `.claude/skills/*/SKILL.md` · Agents: `.claude/agents/*.md` · Settings: `.claude/settings.json`.
