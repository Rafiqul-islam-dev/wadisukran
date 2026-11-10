---
paths:
  - "app/**/*.php"
  - "routes/**/*.php"
  - "database/**/*.php"
---

# Backend (Laravel 12) rules

- Business logic goes in `app/Services/*`, injected via constructor. Controllers: validate → call service → return Inertia/JSON. Do **not** add more code to `Order/OrderController.php` (2200 lines); extract.
- Validation: prefer `app/Http/Requests/*` FormRequests for web; API controllers currently use inline `Validator::make` — match the surrounding file but never skip validation.
- API responses (`/api/v1`): JSON with `message`; use `app/Http/Resources/*` for shaped output; status codes 403 (disabled/forbidden), 422 (validation).
- Queries: eager load (`with`) to avoid N+1; use `withTrashed()` for `product` relations when reading historical orders (Product is soft-deleted). Avoid raw SQL unless required; bind parameters (`whereRaw('? BETWEEN a AND b', [..])`), never interpolate request input.
- Dates: app uses `Carbon`; ranges are `startOfDay()`..`endOfDay()`. Win windows compare against `orders.created_at`.
- Settings: read via `company_setting()` (cached 30 min). If you write `CompannySetting`, `cache()->forget('company_setting')`.
- Migrations: new file per change, never edit existing ones; add nullable/defaulted columns for live tables; use `Schema::hasColumn` guards only when the repo already does. Keep `down()` working.
- Soft deletes: `User`, `Agent`, `Product` use them — restore/permanent-delete flows exist; don't hard delete.
- Formatting: run `vendor/bin/pint` on touched PHP files. No `dd()/dump()`; no commented-out code blocks left behind.
- Only `DB::transaction` where multiple writes must succeed together (order create, claim + ledger, risk hold/release).
