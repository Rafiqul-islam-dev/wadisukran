---
name: permission-audit
description: Audit routes for missing or wrong spatie `can:` middleware, GET-based mutations, and IDOR risks; cross-check against the seeded permission list. Read-only report.
---

# Permission & route security audit

1. Dump routes: `php artisan route:list --json` (or read `routes/web.php`, `routes/api.php`, `routes/settings.php`).
2. For each route list: method, URI, controller@action, middleware, permission. Flag:
   - web routes inside `auth` group without any `can:`;
   - mutations (create/update/delete/restore/status/publish/login-as) using GET;
   - permission mismatch (e.g. delete route guarded by `* update`);
   - API routes reachable by any Sanctum token without role/ownership scoping.
3. Parse permission strings used in `can:` and in Vue `can('…')`; compare with `database/seeders/CreateSuperAdmin.php`. Report permissions used but not seeded, and seeded but unused.
4. For controllers taking `{order}`, `{user}`, `{ledger}`, `{invoice_no}`: check agent-scoping (does an agent see another agent's data?).
5. Output a prioritized table (Critical / High / Medium) with `file:line` and a concrete fix. No code edits unless asked.
