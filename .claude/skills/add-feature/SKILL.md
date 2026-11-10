---
name: add-feature
description: Add a new admin feature end-to-end in this Laravel + Inertia/Vue project (migration, model, service, controller, route with permission, seeder permission, Vue page, test). Use when asked to add a new module/page/CRUD/report.
---

# Add a feature (Laravel 12 + Inertia + Vue 3)

Follow in order; skip steps that don't apply. Read `CLAUDE.md` and `.claude/rules/` first.

1. **Clarify scope** in one or two lines (who uses it: admin/staff/agent? API or web?).
2. **Migration** — new file in `database/migrations` (`php artisan make:migration`). Never edit old ones. Nullable/default for new columns.
3. **Model** — `$fillable`, `$casts`, relations; SoftDeletes only if the sibling models use it.
4. **Service** — logic in `app/Services/<Name>Service.php`; constructor-injected; `DB::transaction` for multi-write.
5. **FormRequest** — `app/Http/Requests/<Name>Request.php` (web). API: inline validator like sibling controllers.
6. **Controller** — `app/Http/Controllers/<Area>/`, thin; returns `Inertia::render('<Area>/<Page>', [...])`.
7. **Route** — `routes/web.php` inside the `auth, verified, isActive` group with `->middleware('can:<permission>')` on **every** route; mutations via POST/PUT/DELETE only.
8. **Permission** — add the string(s) to `database/seeders/CreateSuperAdmin.php`; tell the user to run the seeder/assign via the Permission UI (don't run `db:seed` yourself).
9. **Vue page** — `resources/js/pages/<Area>/<Page>.vue`, `<script setup lang="ts">`, reuse `components/ui/*`, Ziggy `route()`, `can()` for UI gating; add sidebar entry in `components/AppSidebar.vue` if navigable.
10. **Test** — Pest feature test: unauthorized (403), authorized happy path, validation failure.
11. **Verify** — `vendor/bin/pint`, `php artisan test`, `npm run lint`, `npm run build` (or `npx vue-tsc --noEmit`). Report what you ran and what failed.

Output: list of files created/changed and the permission strings to assign.
