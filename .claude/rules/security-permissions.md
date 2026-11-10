---
paths:
  - "routes/**/*.php"
  - "app/Http/Controllers/**/*.php"
  - "app/Http/Middleware/**/*.php"
  - "database/seeders/CreateSuperAdmin.php"
---

# Security & permissions

- Auth model: web = session (`auth, verified, isActive`); API = Sanctum bearer (`auth:sanctum`). Authorization = spatie permissions via `can:<permission name>` route middleware (names are space-separated strings, e.g. `can:show lock tickets`).
- **Every new route gets a `can:` middleware**, and the permission string must be added to the permissions list in `database/seeders/CreateSuperAdmin.php` (and surfaced in the Permission UI if relevant). Use the *matching* permission (don't reuse `role update` for delete etc.).
- **State-changing operations must be POST/PUT/PATCH/DELETE** (CSRF-protected), never GET. Existing GET mutators (`status-change`, `restore`, `histories/delete`, `histories/publish`, `login-as-agent`) are legacy debt — don't copy that pattern; flag it when touching them.
- Agents must only see/act on their own data: scope queries by `Auth::id()` / `user_type` unless the user has `search with all agent`. Check IDOR on `{order}`, `{user}`, `{ledger}`, `{invoice_no}` params.
- Mass assignment: Models use `$fillable`; never `->update($request->all())`. Validate then use `validated()`.
- Uploads (banners, product images): validate mime/size, store under `public/uploads|banners` via Storage, never trust client filename.
- Never log or return password hashes, `temp_password`, API tokens, or `.env` values. `Agent.temp_password` / `users.temp_password*` is sensitive — don't expose in Inertia props or API resources.
- `APP_DEBUG` must be false in production; `debugbar` is dev-only.
- Login alert mail (`LoginAlertMail`) and password flows: keep rate limiting (`LoginRequest`) intact.
