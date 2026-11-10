---
name: refactor-big-file
description: Plan and perform behaviour-preserving extraction from oversized files (OrderController ~2200 lines, large Vue pages). Use only when the user asks to refactor/split.
---

# Behaviour-preserving extraction

1. Read the whole target file; list its responsibilities (queries, PDF building, formatting, validation, side effects).
2. Propose the split (target class/component names, methods moved) and get a go-ahead if it touches > 3 files.
3. Move code **verbatim** first (no logic edits), keep public method signatures and route names stable. Backend → `app/Services/*` or `app/Http/Resources/*`; frontend → `resources/js/components/<Area>/*` + `composables/*`.
4. Add a characterization test (or manual route checklist) before moving, re-run after.
5. Delete dead code only if proven unused (`grep`); `OrderController.php-bakup` and `ProbableWins.vue-bakup` are backups — ask before removing.
6. Run `vendor/bin/pint`, `npm run lint`, `php artisan test`, `npm run build`. Report diffs summary.
