---
paths:
  - "resources/js/**/*.{vue,ts}"
  - "resources/css/**/*.css"
---

# Frontend (Vue 3 + TS + Inertia 2 + Tailwind 4)

- Use `<script setup lang="ts">`. Pages live in `resources/js/pages/<Area>/<Page>.vue` and match `Inertia::render('<Area>/<Page>')` exactly (case-sensitive on Linux production!).
- Import alias `@/…` → `resources/js`. Layouts in `resources/js/Layouts` (note capital L; `AppLayout.vue`). Reuse `components/ui/*` (shadcn-vue/reka-ui) before adding new primitives; don't edit generated ui components unless necessary.
- Routing: use Ziggy `route('name')` with the names defined in `routes/web.php`; don't hard-code URLs.
- Forms/requests: Inertia `router`/`useForm`; show errors with `InputError`; toasts via `vue-sonner`.
- Permissions in UI: `can('permission name')` from `@/helpers/permissions` to hide controls — this is cosmetic only; the server `can:` middleware is the real check.
- Type props with `defineProps<{…}>()`; avoid `any`. Run `npx vue-tsc --noEmit` mentally/for real when changing types.
- Style: Tailwind utility classes, `cn()` from `@/lib/utils` for conditionals. Prettier: 4 spaces, single quotes, width 150. Run `npm run format` / `npm run lint`.
- Big pages (`Orders/Index.vue`, `ProbableWins.vue`, `Product/Index.vue`, `LockTickets.vue` ~700–1000 lines): prefer extracting sub-components/composables over appending more code.
- No `console.log` left in committed code. Don't commit `*.vue-bakup` files.
- Printing/PDF: print views use `vue3-print-nb`; server PDFs use DomPDF Blade views — keep the two formats consistent.
