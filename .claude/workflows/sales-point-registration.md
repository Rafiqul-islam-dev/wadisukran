# Workflow: Sales Point "Registration & Agreement" (Agent menu)

**Status:** PLAN ONLY — no code implemented yet. Implement phase by phase; tick the boxes as you go.
**Owner:** Rafiqul · **Created:** 2026-11-09
**Design source:** `public/Sample Wadi_Shukran_Sales_Point_Registration_Commission_Agreement.pdf` (2 pages, A4, ReportLab-generated)
**Reference renders (in repo):**
- Page 1 → web form UI: `.claude/workflows/assets/sales-point-registration/sample-page1-form-ui.png`
- Page 2 → generated agreement PDF: `.claude/workflows/assets/sales-point-registration/sample-page2-agreement-pdf.png`

---

## 1. Goal

Add an **Agent → "Registration & Agreement"** menu.

1. **Registration:** staff fill the Sales Point registration form (page-1 design) incl. photo, documents, and both signatures.
2. **Approval:** the registration goes to an approver (status `pending`).
3. **On Approve:** system generates the **Agreement PDF that looks exactly like page 2** of the sample, filled with the registered data + signatures, stores it, and makes it downloadable.

The UI must be same-to-same as page 1; the PDF must be same-to-same as page 2.

## 2. What the sample PDF tells us

### Page 1 = form UI (web)
| # | Section (EN / AR) | Fields |
|---|---|---|
| 1 | Business Information / معلومات | Business Shop Name*, Authorized Person/Owner Name*, Passport Number*, UID Number*, E-ID Number*, Trade License Number*, Email*, Mobile* (+971 flag), WhatsApp* (+971 flag), Full Business Address*, Location Map* (Google Maps link, pin icon), **Authorized Person Photo** (upload box with camera placeholder) |
| 2 | Product Information / معلومات المنتجات | Checkboxes with icons: Chocolate, Attar, Tasbih, Pen, Others + free text/URL (placeholder `https://onlinesouquae.com/`) |
| 3 | Device & Service Allocation / تخصيص الأجهزة والخدمات | Checkboxes: Tab, POS Machine, SIM Card, Active Data; "SIM Card Provided" checkbox; Date of Delivery*; Time of Delivery* |
| 4 | Agreement Terms / شروط الاتفاقية | 6 numbered read-only terms (pink number badges) |
| 5 | Attach Documents / إرفق المستندات | 6 upload cards: Trade License Copy, Passport Copy, Visa Copy, Emirates ID Copy, Business Card, Shop Picture (+ "Attach Documents" bulk button) |
| 6 | Signatures / التوقيعات | Authorized Person signature*, Company Representative signature* (draw with finger/mouse), Date*, Place*, **Generate Agreement PDF** button |

(`*` = required marker in the design. Every label is bilingual EN/AR.)

### Page 2 = the generated agreement (PDF)
Same header + sections 1–4 and Signatures, **without** "Attach Documents" (so everything fits one A4 page), terms rendered in a larger font, **filled with real data** instead of placeholders, signature images in the signature boxes, no buttons ("Generate Agreement PDF" / "Upload" must not appear), photo shown in the photo box.

### Design tokens (extracted from the PDF)
- Page: A4 `595.276 × 841.89 pt`.
- Colors: pink `#e6007e` (badges, accents, button), navy `#243475` / `#123274` / `#27338a` (headers, borders), text `#1d1d1b`, grey `#9d9d9c` (placeholders), light pink row (device row), grey-blue band `#8190b9`-ish (device options row), white.
- Fonts embedded: **Dubai** (Arabic), **Arial / Arial Bold** (Latin), **Nirmala UI**, Times Bold (digits?). Use Dubai + an Arial-metric font (Arimo/Liberation Sans) in the app; Dubai is free to redistribute (confirm license).
- The sample contains **no raster images** — logo, product icons, camera, UAE flag, calendar/clock, document icons are all vector. **No SVG source exists** (owner confirmed) → use the project's company logo and rebuild/crop the icons (Phase 0, D4).

### Inconsistencies in the sample — RESOLVED in §4 / §4.1
1. Terms 1 and 2 near-duplicates → merged (5 terms).
2. Term order differs between page 1 and page 2 → page-2 order.
3. Section badges both "1" → 5 and 6.
4. Typos ("to Keep", spacing) → fixed. Keep label spelling "WhatsApp".
5. Stray empty navy block beside "Generate Agreement PDF" → not reproduced.

## 3. Lifecycle

```
(New) ──save──▶ PENDING ──approve──▶ APPROVED  (PDF generated + stored)
                   │
                   └─reject(reason)─▶ REJECTED ──edit & resubmit──▶ PENDING
```
- Only `pending` can be approved/rejected. `approved` is **immutable** (the PDF is a signed legal document); changes = new registration or an explicit "revoke" feature (out of scope).
- Approve runs in one `DB::transaction` + `lockForUpdate()`; if PDF generation fails the whole approve rolls back (stays `pending`).
- Segregation of duties (recommended): creator cannot approve their own registration (config flag).

## 4. Decisions

### Confirmed by the owner (2026-11-09) — FINAL, do not re-ask
| # | Decision |
|---|---|
| D1 | Approving **does NOT create** an Agent (User+Agent) account. No auto-creation. (The optional "Create Agent from registration" button in old Phase 5 is **dropped** unless the owner asks later.) |
| D2 | **Anyone who has access to the menu fills the form.** Access = the permission `show sales point registrations` / `create sales point registration`; no "staff only" restriction. The menu must be gated by *its own* permission, **not** by `show agent list`, so a user can be given only this menu. The logged-in user is `created_by`. |
| D3 | **Terms fixed** (see §4.1). |
| D4 | Assets: **no SVG source exists.** Use the project's **company logo** (`company_setting()->logo`, stored under `public/`; fallback `public/asset/shukran.png`). Other icons are rebuilt (see §5.7). |
| D5 | The sample PDF **stays in `public/`** — the owner will delete it later. Therefore the implementation must **never read it at runtime**: everything needed (reference renders, extracted/rebuilt assets) is copied into the repo (`.claude/workflows/assets/…`, `resources/…`). **Do not move or delete the sample PDF.** |

### Defaults (not contradicted by the owner — keep)
| # | Decision |
|---|---|
| D6 | PDF = 1 A4 page like page 2; uploaded documents are **not** appended (viewable in admin only). |
| D7 | Separate permissions for create / update / approve / download (see §5.5). Creator-cannot-approve rule: **not enforced** (the owner said "whoever has menu access"), but implement as a config flag `sales_point.block_self_approval` default `false`. |
| D8 | Form UI uses static bilingual EN/AR labels (as design), no language switch. |
| D9 | User-entered values may be Arabic → PDF engine must shape Arabic (drives §5.1). |
| D10 | Section badges on the web form: 1,2,3,4 then **5 Attach Documents, 6 Signatures** (fixes the sample's duplicate "1"). |

### 4.1 Final Terms text (replaces the sample's inconsistent terms)
Fixes applied: page-2 order, duplicate #1/#2 merged, typos/spacing corrected ("Keep"→"keep", "Sales Point/Authorized" → "Sales Point / Authorized", "trust" ligature artefact ignored), wording otherwise unchanged. Stored in `config/sales_point_agreement.php` with `terms_version = 1`; each approved registration snapshots the exact text used.

1. The Sales Point / Authorized Person agrees to promote and sell Wadi Shukran products as per company policy and guidelines.
2. The Sales Point / Authorized Person agrees to keep the provided device(s) in good condition and use them only for Wadi Shukran business.
3. The Sales Point / Authorized Person agrees to provide accurate information and cooperate with the company at all times.
4. Any loss, damage or misuse of company property (device, SIM, etc.) will be the responsibility of the Sales Point / Authorized Person.
5. If the Sales Point / Authorized Person takes or receives a SIM Card from Wadi Shukran, all responsibility and liability for that SIM Card shall remain entirely with the Sales Point / Authorized Person from the exact date and time of delivery until the SIM Card is finally returned to or collected by Wadi Shukran. Any misuse, unauthorized use, unlawful use, loss, or improper use of the SIM Card during this period shall be the sole responsibility of the Sales Point / Authorized Person. This Agreement is also based on trust (amanah) and breaching it equals to breach of trust.

→ **5 terms** (sample had 6 because #1/#2 were duplicates). Arabic translations are **not** in the sample terms — do not invent legal Arabic text; keep terms English-only (the "AGREEMENT TERMS / شروط الاتفاقية" title stays bilingual). If the owner wants the 6-term original back, change only the config file.

## 5. Technical approach

### 5.1 PDF engine — the main risk
Project uses **barryvdh/laravel-dompdf 3.1**. **DomPDF has no Arabic shaping / RTL support**, so bilingual labels would render disconnected and reversed. Options:

| Option | Fidelity | Arabic | Cost |
|---|---|---|---|
| **A. mPDF 8.x** (HTML/CSS → PDF, built-in Arabic shaping + RTL, TTF embedding) | High | ✅ labels *and* user input | +1 composer dep; needs writable tmp dir |
| B. DomPDF + static Arabic labels pre-rendered as SVG/PNG | High for static labels | ❌ user-entered Arabic breaks | no new dep; more assets |
| C. FPDI overlay on a clean blank copy of page 2 | Pixel-perfect | ✅ (static layer is vector) | needs a *clean template PDF without placeholder text* from the designer; fragile coordinates |
| D. Headless Chrome (Browsershot) | Perfect | ✅ | needs Node+Chrome on the cPanel host — rejected |

**Recommendation: A (mPDF)**, validated by a **time-boxed spike in Phase 1** (render the page-2 layout with dummy data, diff against the sample). Fallback: C if the designer supplies a clean template, else B.
Fonts: bundle Dubai + Arimo TTFs under `resources/fonts/`.

### 5.2 Storage & privacy (important)
- `config/filesystems.php` disks `local` and `public` both have `root => public_path()` → anything stored there is **publicly reachable by URL**. Passport/Emirates ID/trade-license scans must **not** go there.
- Add a new **`private` disk** (`storage_path('app/private')`) for: documents, photo, signatures, generated PDFs. Serve only via authenticated controller actions with permission + (for agents) ownership checks.
- Encrypt ID numbers at rest with Eloquent `encrypted` cast (`passport_no`, `uid_no`, `eid_no`); not searchable by these (search by ref/shop/owner/phone/email only).
- Uploads: validate mime + extension + max size (images jpg/png/webp, docs + pdf), random filenames, never trust client name; signatures arrive as PNG data-URL → validate prefix/size, decode and **re-encode with GD** before saving.

### 5.3 Data model (new migrations only — never edit old ones)
`sales_point_registrations`
- `id`, `reference_no` (unique, e.g. `SPR-0001`), `status` (`pending|approved|rejected`)
- Business: `shop_name`, `owner_name`, `passport_no`*, `uid_no`*, `eid_no`*, `trade_license_no`, `email`, `mobile_country_code`, `mobile`, `whatsapp_country_code`, `whatsapp`, `address` (text), `location_map` (string URL)
- `photo_path` (nullable)
- Products: `products` JSON (`{chocolate,attar,tasbih,pen,others}`), `products_other_text`
- Devices: `dev_tab`, `dev_pos_machine`, `dev_sim_card`, `dev_active_data`, `sim_card_provided` (bools), `delivery_date`, `delivery_time`
- Signatures: `person_signature_path`, `company_signature_path`, `signed_date`, `signed_place`
- Workflow: `created_by`, `approved_by`, `approved_at`, `rejected_by`, `rejected_at`, `reject_reason`, `terms_version`, `terms_snapshot` JSON (exact text used → PDF reproducible), `pdf_path`, `pdf_generated_at`, timestamps, softDeletes
- Indexes: `status`, `created_at`, `email`, `mobile`, `trade_license_no`.

`sales_point_registration_documents`: `id`, `registration_id` FK (cascade), `type` enum(`trade_license,passport,visa,emirates_id,business_card,shop_picture`), `path`, `original_name`, `mime`, `size`, timestamps; unique(`registration_id`,`type`).

(`*` encrypted cast → column type `text`.)

### 5.4 Backend structure (follows existing conventions)
- Models: `SalesPointRegistration`, `SalesPointRegistrationDocument` (`$fillable`, `$casts`, relations, SoftDeletes).
- Controller: `app/Http/Controllers/Agent/SalesPointRegistrationController.php` (thin).
- FormRequests: `StoreSalesPointRegistrationRequest`, `UpdateSalesPointRegistrationRequest`, `RejectSalesPointRegistrationRequest`.
- Services: `SalesPointRegistrationService` (create/update/approve/reject in transactions, file handling), `SalesPointAgreementPdfService` (builds the PDF).
- Terms: `config/sales_point_agreement.php` (versioned list, bilingual if needed).
- View: `resources/views/pdf/sales_point_agreement.blade.php` (page-2 layout; engine per §5.1).
- Validation highlights: required fields per `*`; email format; UAE mobile format; Emirates ID `784-YYYY-NNNNNNN-N`; `location_map` must be a URL (prefer google/goo.gl/maps host); `delivery_date` valid, `delivery_time` HH:MM; photo/doc size caps; both signatures required; duplicate warning on trade license/email.

### 5.5 Routes (all mutations non-GET; every route has `can:`)
Inside the existing `auth, verified, isActive` group in `routes/web.php`, prefix `agents/registrations`, name `agents.registrations.*`:

| Method | URI | Action | Permission |
|---|---|---|---|
| GET | `/` | index (tabs: All/Pending/Approved/Rejected, search, pagination) | `show sales point registrations` |
| GET | `/create` | form (page-1 design) | `create sales point registration` |
| POST | `/store` | save → `pending` | `create sales point registration` |
| GET | `/{registration}` | show (read-only form + docs + signatures + approve/reject panel) | `show sales point registrations` |
| GET | `/{registration}/edit` | edit (only `pending`/`rejected`) | `update sales point registration` |
| POST | `/update/{registration}` | update (re-submits rejected → pending) | `update sales point registration` |
| POST | `/{registration}/approve` | approve + generate PDF | `approve sales point registration` |
| POST | `/{registration}/reject` | reject with reason | `approve sales point registration` |
| POST | `/{registration}/regenerate-pdf` | rebuild PDF from stored snapshot | `approve sales point registration` |
| GET | `/{registration}/pdf` | stream/download PDF (approved only) | `download sales point agreement` |
| GET | `/{registration}/files/{kind}` | stream photo/document/signature from private disk | `show sales point registrations` |
| DELETE | `/delete/{registration}` | soft delete (only non-approved) | `delete sales point registration` |

**Permissions to add** to `database/seeders/CreateSuperAdmin.php` (Super Admin gets them via `syncPermissions`; assign others via Permission UI): `show sales point registrations`, `create sales point registration`, `update sales point registration`, `delete sales point registration`, `approve sales point registration`, `download sales point agreement`.

### 5.6 Frontend structure
- Sidebar: in `resources/js/components/AppSidebar.vue`, add to the **Agent** group: `{ title: 'Registration & Agreement', href: '/agents/registrations', icon: FileSignature, permission: 'show sales point registrations' }`. The Agent group itself is currently gated by `show agent list` (`AppSidebar.vue` line ~41), so make the group visible when the user has **any** of its children's permissions (D2: a user may have only this menu). Check how `NavMain.vue` filters children before changing.
- The route group must **not** sit under the existing `agents` prefix group that has `middleware('can:show agent list')` — register `agents/registrations` as its own group (declare it before `agents/{...}` wildcard routes) with its own `can:` middlewares.
- Pages (`resources/js/pages/Agent/Registration/`): `Index.vue` (list + status tabs + approve/reject quick actions), `Form.vue` shared by `Create.vue`/`Edit.vue` (page-1 design), `Show.vue` (read-only + Approve/Reject + PDF download/preview).
- Components (`resources/js/components/registration/`): `SectionCard` (numbered badge + bilingual title bar), `BilingualLabel`, `PhoneInput` (flag + +971), `PhotoUpload`, `DocumentUploadCard`, `ProductCheckbox` (icon + label), `SignaturePad` (canvas, pointer events, clear button, exports PNG) — prefer the small `signature_pad` npm lib.
- Reuse `components/ui/*` (Input, Checkbox, Button, Dialog, Tabs, Table), `useForm` (Inertia) with `forceFormData`, `vue-sonner` toasts, `can()` gating (server `can:` is the real check), disable submit while `processing`.
- Static bilingual labels in one file `resources/js/pages/Agent/Registration/labels.ts` (so EN/AR text isn't scattered).
- Tailwind theme tokens for pink/navy added in the page/component classes (don't change global theme).
- Print/preview: Show page embeds the stored PDF (iframe/object) after approval.

## 6. Phases (tick off as you go)

### Phase 0 — Prep & assets (decisions D1–D5 already made — see §4, do not re-ask)
- **Phase 0 status (2026-11-10): done except Dubai font license decision.** Logo verified (web `company_setting.logo_url` shared via `AppServiceProvider`; PDF `public_path(company_setting()->logo)`; uploads in `public/uploads/company/` are 586×721 and 2330×2350 portrait/square PNGs; fallback `public/asset/shukran.png` 280×130). Icons cropped (6× scale) into `resources/images/registration/`: chocolate, attar, tasbih, pen, camera, flag_uae, calendar_icon, clock_icon, pin_icon, paperclip_white (known nits to tidy in Phase 3: attar crop has a sliver of neighbour on the edge, clock crop has a grey line at bottom, flag crop bottom band). Liberation Sans Regular/Bold (OFL) copied to `resources/fonts/`. Dubai TTFs exist only in `C:\Windows\Fonts` (Microsoft-licensed) — NOT copied, awaiting owner decision.
- [x] **Logo:** use `company_setting()->logo` (file under `public/`, same pattern as `resources/views/pdf/agent_bill.blade.php`: `public_path(company_setting()->logo)`); fallback `public/asset/shukran.png`. For the form header (web) use the same logo URL (`company_setting.logo_url` shared prop, see `AppLogoIcon.vue`). Header band (pink/navy shape + Arabic/English title) is built with CSS/inline SVG, not an image.
- [x] **Icons (no SVG source available):** build small inline SVG components ourselves — camera, paperclip, upload, calendar, clock, map-pin, file/document, UAE flag (simple 4-band flag), checkbox. Product icons (chocolate, attar, tasbih, pen): crop from the sample at high dpi into PNG files under `resources/images/registration/` (copy, don't reference the sample) **or** draw simple SVGs; pick whichever looks closest to `sample-page1-form-ui.png`. lucide icons may be used only where visually equivalent.
- [ ] **Fonts (Liberation Sans done; Dubai pending license/owner OK):** Dubai (Arabic) + Arimo/Liberation Sans (Arial-metric) TTFs → `resources/fonts/` (verify licenses). Needed by the PDF engine.
- [x] Copy everything the implementation needs out of the sample into the repo (renders already in `.claude/workflows/assets/…`). **Leave the sample PDF in `public/`; do not move/delete it** — owner will delete it; code must not depend on it.

### Phase 1 — PDF spike (go/no-go on engine)
- **Phase 1 status (2026-11-10): spike done → RECOMMEND mPDF 8.3. Waiting for owner approval to add `mpdf/mpdf` to composer.json.**
  - Spike (scratchpad, project `composer.json` untouched): `.claude/workflows/assets/sales-point-registration/spike/mpdf-spike-build.php` (+ `mpdf-spike-render.png`, `mpdf-spike-out.pdf`). Renders **exactly 1 A4 page**, Arabic shaped + RTL correctly (labels and Arabic user input), logo, icons, signatures, photo, 5 terms.
  - **Arabic font decision (owner: open-license):** **Tajawal** (SIL OFL) in `resources/fonts/` + Liberation Sans. Noto Sans Arabic was rejected: mPDF crashes on its GPOS table (`GPOS Lookup Type 5, Format 3 not supported`) and without OTL some words mis-order. Dubai not used (Microsoft licence).
  - mPDF learnings for Phase 4: (1) `position:absolute` works **only for top-level elements** — no wrapper div; (2) `<img>` must be wrapped in an absolute div (inline-absolute img is ignored); (3) number badges via nested table cell + `border-radius`; (4) ✓ needs `font-family:dejavusans`; (5) wrap Arabic runs in `<span class="ar">` (helper `ar()` in spike) so user input is shaped; (6) needs writable `tempDir`.
  - Remaining visual diffs vs page 2 (fix in Phase 4): header Arabic title wraps onto 2 lines (reduce size/width); header pink cap shape + slanted section tabs are approximated; bilingual labels wrap in a few narrow spots (Business Shop Name, Address, Location Map, Date of Delivery) → widen/reposition; product icons slightly small and selected-tick overlays added by us (sample has none); Upload button/photo caption position; fonts (Tajawal vs Dubai) slightly different glyphs; no auto-shrink yet for long values.
- [ ] Install mPDF in a branch; build `sales_point_agreement.blade.php` with **hard-coded dummy data** replicating page 2.
- [ ] Render → PNG (we already have a pdfjs renderer approach) and compare with `sample-page2-agreement-pdf.png`: layout, Arabic shaping, colors, fonts, one-page fit.
- [ ] Decide A/B/C (§5.1). Record decision here.
- Acceptance: visually indistinguishable at 100% zoom; Arabic correct; single A4 page; works with long names/addresses (truncate/shrink strategy defined).

### Phase 2 — Backend foundation
- **Phase 2 status (2026-11-10): DONE — 18 Pest tests pass, laravel-reviewer + migration-reviewer run and fixed. `php artisan migrate` NOT yet run on the dev DB (needs owner OK).**
  - Review fixes applied: private disk `throw=true`; `location_map` http(s) only; no regex on big signature payload + GD check; blank-signature rejection; signature errors reported on the right field (ValidationException); state conflicts (`DomainException`) -> 409; safe orphan-file cleanup; CSP sandbox header on streamed files; stronger private-root test.
  - Deliberately NOT changed (carry to later phases): (a) `edit` shows decrypted IDs to users with only `update` permission — consistent with D2 (anyone with menu access), revisit if owner wants masking; (b) `$fillable` still lists workflow fields — in Phase 4 never `fill($request->...)`, set approve/reject fields explicitly; (c) duplicate trade-licence/email warning (§5.4) -> Phase 3 UI + a light lookup; (d) audit log of ID/file access -> Phase 5; (e) optional photo `dimensions` cap + Emirates ID normalisation; (f) composite index (status, created_at) is a nice-to-have, skipped (no migration edit needed pre-deploy, can still change before first migrate); (g) add `ext-gd` to composer.json before deploy (needs `composer update --lock`).
  - Reviewer finding rejected as incorrect: country-code NULL on insert — the service already coalesces `?? '+971'`.
  - Added: `mpdf/mpdf` 8.3.1 (composer.json/lock), `private` disk, `config/sales_point_agreement.php` (final terms, `block_self_approval`, document types), migration `2026_11_10_100000_create_sales_point_registrations_table.php` (2 tables, no `agent_user_id`), models `SalesPointRegistration` / `SalesPointRegistrationDocument`, `SalesPointRegistrationRequest` (shared create/update), `SalesPointRegistrationService`, `Agent/SalesPointRegistrationController`, routes `agents/registrations/*` (own `can:` group, before the `agents` group), 6 permissions in `CreateSuperAdmin`, Pest test (16 tests).
  - Approve/reject/regenerate/pdf endpoints + `download`/`approve` permissions usage are Phase 4 (permissions already seeded).
  - **Testing note:** the full suite cannot run on sqlite because an OLD migration (`2026_05_06_000002_change_risk_cap_groups_draw_number_to_string`) uses MySQL-only `MODIFY`. Run feature tests against a throw-away MySQL DB: `DB_CONNECTION=mysql DB_DATABASE=taqdheer_test php artisan test --filter=SalesPointRegistrationTest` (DB `taqdheer_test` created locally; never point tests at `taqdheer`). Result: 16 passed.
  - Frontend contract for Phase 3: send booleans as `1`/`0`; `products[chocolate|attar|tasbih|pen|others]`; files as `photo` and `documents[<type>]`; signatures as PNG data-URL strings `person_signature`, `company_signature` (optional on update); POST (multipart) to `agents.registrations.store` / `update/{id}`; photo/doc/signature images are shown via `agents.registrations.file` (never public URLs).
- [x] `private` filesystem disk.
- [x] Migrations (2 tables), models, config terms file, permissions in seeder.
- [x] FormRequests + `SalesPointRegistrationService` (store/update, file + signature handling, reference_no generation inside a transaction/lock like `Agent::max('serial')` pattern but race-safe).
- [x] Controller + routes (index/create/store/show/edit/update/files/delete).
- Acceptance: Pest feature tests pass for permissions (403/200), validation failures, successful store with files, private-file access control.

### Phase 3 — Frontend: registration form (page 1) + list
- **Phase 3 status (2026-11-10): DONE, awaiting owner visual sign-off.** Built: sidebar entry (Agent group now visible when ANY child permission is held - `NavMain.vue` `canSee`), `Index/Create/Edit/Show/Form.vue`, `components/registration/*` (SectionCard, BilingualLabel, PhoneInput, PhotoUpload, DocumentUploadCard, ProductCheckbox, dependency-free SignaturePad), `labels.ts`. Verified: `vue-tsc` no errors in the new files, `npm run build` OK, Prettier applied, 18 Pest tests pass, and a real headless-Edge end-to-end run on an isolated `taqdheer_test` DB (empty submit -> 16 field errors; full submit with photo + passport doc + two drawn signatures -> redirected to the Show page; files streamed from the private disk; toast shown). Screenshots reviewed: create (1000px + 1440px), index, show. Known gaps (Phase 3.5 / later): duplicate trade-licence/email warning, product icon crops still rough (attar edge sliver, clock grey line), Tajawal vs Dubai glyphs, header band is CSS-approximated, tablet/mobile layout only eyeballed at 1000px, approve/reject UI is Phase 4. Testing note: after `npm run build` Inertia's asset version changes - tests send `X-Inertia-Version` via `sprInertia()`.
  - vue-inertia-reviewer run; FIXED: local-date default (was UTC), double success toast, reactive props in Show + keyed Edit, hidden country-code errors, scroll/focus to first error, client-side size check + Remove + same-file re-pick, search-debounce cleanup/sync, PDF served as attachment (CSP sandbox only for images), `stale products_other_text`, sidebar OR-rule opt-in only for the Agent group (`showIfAnyChild`). NOT done (decide later): a11y pass (focus ring on custom checkbox, aria-invalid/describedby, canvas label, icon-button aria-labels), `Inertia::encryptHistory()` for ID props, strict types instead of `any`, unsaved-changes confirm on Cancel, signature pad resize/right-click nits, Arabic part of the Business section title, active-menu highlight on sub-pages. Re-verified after fixes: build OK, 18 tests pass, headless e2e (empty submit -> 16 errors; full submit -> Show page). Pre-existing TS error in NavMain.vue (auth_permissions unknown) is not mine.
- [x] Sidebar entry, `Index.vue`, `Form.vue` (Create/Edit), components from §5.6.
- [x] Same-to-same visual check against `sample-page1-form-ui.png` (desktop + tablet width; signature pad works with touch).
- Acceptance: can create a registration end-to-end; required validation errors shown per field; no horizontal scroll on mobile; `npm run lint` + `vue-tsc` clean.

### Phase 4 — Approval + PDF generation
- **Phase 4 status (2026-11-10): DONE.** Routes: POST `{id}/approve`, `{id}/reject`, `{id}/regenerate-pdf` (`approve sales point registration`), GET `{id}/pdf` (`download sales point agreement`, inline; `?download=1` = attachment). `SalesPointAgreementPdfService` (mPDF, page-2 design, one A4 page; implemented as a PHP builder class instead of a Blade view) + `SalesPointRegistrationService::approve/reject/regeneratePdf` (transaction + `lockForUpdate` + state re-check; PDF failure => stays pending, no orphan file; approval stamp on the first PDF). Owner rule: Super Admin (any holder of the approve permission) can approve/reject ANY registration incl. their own (`block_self_approval` false, env `SALES_POINT_BLOCK_SELF_APPROVAL`). Terms are frozen at submit/re-submit (not at approval). Reviewer fixes: terms freeze, stamp, photo max 4000x4000 px, `ENT_SUBSTITUTE` + bidi/format-char strip, value truncation with ellipsis, null-guard on layout, regenerate audit `Log::info`. Verified: 37 Pest tests pass (taqdheer_test DB), real headless-browser run (Super Admin approves own submission -> PDF preview; reject needs reason), rendered PDF compared with sample page 2 (`agreement_preview3`), HTML/URL injection probe printed as text only. Show.vue: Approve/Reject dialogs, PDF iframe, Download, Regenerate. Remaining nits: fonts Tajawal vs Dubai, header cap CSS-approximated, truncated long values (full value stays on the record), regenerate overwrites the old file (logged only).
- [ ] `approve` / `reject` / `regenerate-pdf` / `pdf` endpoints; `SalesPointAgreementPdfService`; transactional approve with `lockForUpdate`, state re-check, terms snapshot.
- [ ] `Show.vue` approve/reject panel (reject needs reason; confirm dialog on approve), PDF preview/download.
- Acceptance tests: double-approve rejected; approve of non-pending rejected; PDF exists, starts with `%PDF`, **exactly 1 page**; rollback if PDF fails; approved record not editable; creator≠approver rule (if enabled); download blocked for pending.

### Phase 5 — Polish & integration
- **Phase 5 status (2026-11-10): DONE (core).** Audit trail: table `sales_point_registration_events` (created|updated|approved|rejected|pdf_regenerated|pdf_viewed|pdf_downloaded|document_viewed|deleted; user, IP, meta), written inside the same transaction as the change, shown as an "Activity" card on Show. Duplicate hint: `GET agents/registrations/duplicates` (`can:create sales point registration`, returns only ref/shop/status/which field matched) shown as a non-blocking warning in the form on blur. Tests: 42 pass. Local dev DB migrated. SKIPPED on purpose (optional): email notifications, dashboard pending counter. Not browser-verified after this step (tests + vue-tsc + build only).
- [x] ~~"Create Agent from this registration"~~ — **dropped (D1)**; do not build; drop `agent_user_id` column from the migration.
- [ ] Notifications (optional): email approver on new pending, email sales point the signed PDF.
- [x] Audit trail (who created/approved/downloaded, when).
- [ ] Dashboard counter of pending registrations (optional).
- [ ] Update `CLAUDE.md` domain section + this workflow status.

### Phase 6 — QA & release
- [ ] `vendor/bin/pint`, `php artisan test`, `npm run lint`, `npm run build`.
- [ ] Manual cross-check: PDF in Adobe/Chrome/mobile viewers (Arabic fonts embedded).
- [ ] Migration safety review with the `migration-reviewer` agent; code review with `laravel-reviewer` + `vue-inertia-reviewer`; permission review with the `permission-audit` skill.
- [ ] Production: ensure `storage/app/private` + mPDF tmp dir writable (`deploy.sh` already chmods `storage`); run `php artisan migrate --force` (deploy.sh does) and run seeder entry for new permissions; assign permissions to roles.

## 7. Test plan (Pest — see `.claude/rules/testing.md`)
- Feature: auth/permission matrix for every route; store validation (each required field, EID/phone/URL formats, file types/sizes); update only in allowed states; approve/reject transitions; double-submit/double-approve; IDOR on `{registration}` files; private file not reachable by public URL.
- Unit: reference number generator, terms snapshot, signature sanitizer.
- PDF: page count == 1, text contains shop/owner values, Arabic labels present, file saved on private disk; **visual regression** script renders PDF → PNG and diffs against the stored reference (tolerance) — run manually before release.

## 8. Risks & mitigations
| Risk | Mitigation |
|---|---|
| Arabic rendering in DomPDF | mPDF (or pre-rendered label images) decided in Phase 1 spike |
| Same-to-same fidelity without source artwork | Get original assets in Phase 0; fallback high-dpi extraction |
| Sensitive PII exposure (passport/EID) | private disk, encrypted casts, permission-guarded streaming, no public URLs, no logging of values |
| Long text overflows the single page | fixed-size cells + truncate/`text-overflow`/auto-shrink rules defined in Phase 1; test with worst-case data |
| Approve race / double PDF | transaction + `lockForUpdate` + state re-check |
| Production host differences (cPanel, `public/` doc-root, `static_asset()` prefix) | never use `static_asset()` for private files; stream via controller; verify temp/write dirs |
| `company_setting()`/cache not involved, but sidebar permission gate (`show agent list`) may hide the menu for approvers | decide gating in Phase 3 |

## 9. Out of scope (for now)
Public self-registration link, e-signature legal compliance (OTP/Emirates ID verification), multi-language PDF switch, revoking/renewing agreements, bulk import, editing an approved agreement.

## 10. Decision log
- 2026-11-09 owner: D1 no auto Agent creation · D2 anyone with menu access fills the form · D3 terms fixed (§4.1, 5 terms) · D4 use project logo, no SVGs, rebuild icons · D5 sample PDF stays in `public/` (owner deletes later; never read at runtime).
- 2026-11-10 owner: Arabic font must be open-license → Tajawal (OFL) + Liberation Sans; Dubai dropped.
- PDF engine: mPDF 8.3.1 (approved by owner 2026-11-10).
