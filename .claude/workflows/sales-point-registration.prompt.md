# Ready-to-use prompts — Sales Point "Registration & Agreement"

Plan file: `.claude/workflows/sales-point-registration.md` (decisions D1–D5 are already final there).
Paste a prompt below into Claude Code when you are ready. Run phases **one at a time** (`/clear` between phases is fine — the plan file keeps the state via checkboxes).

---

## A) Master prompt (recommended — runs the whole plan phase by phase)

```
Tumi ei project er Expert Laravel + Vue/Inertia developer. Kaj: Agent menu-te "REGISTRATION & AGREEMENT" feature implement kora.

Age porbe (serial e):
1. CLAUDE.md
2. .claude/workflows/sales-point-registration.md — eta final plan, ei plan onujayi kaj korbe. §4 Decisions ar §4.1 Terms final.
3. .claude/rules/*.md (backend-laravel, frontend-vue-inertia, security-permissions, testing)
4. Design reference image duto Read tool diye dekho (same-to-same korte hobe):
   - .claude/workflows/assets/sales-point-registration/sample-page1-form-ui.png  -> web form UI
   - .claude/workflows/assets/sales-point-registration/sample-page2-agreement-pdf.png -> approve-er por generate howa PDF

FINAL decisions (owner already confirmed — notun kore proshno korba na):
- Approve korle Agent account auto toiri HOBE NA. "Create agent from registration" feature banaba na, agent_user_id column o rakhba na.
- Je user-er ei menu-r access (permission) ache, shey-i form fill korte parbe. Staff-only restriction nai. Menu gate hobe nijer permission diye (`show sales point registrations`), `show agent list` diye na — sidebar-er Agent group ke ei menu-r permission thakleo dekhano lagbe.
- Terms plan-er §4.1 e fixed (5 ta term, page-2 order, duplicate merge, typo fix). Oi text-i config/sales_point_agreement.php te rakho, arbitrary kichu add/change korba na, Arabic term-translation banaba na.
- SVG file nai. Logo = project-er company logo: web e shared prop `company_setting.logo_url` (dekho AppLogoIcon.vue), PDF e `public_path(company_setting()->logo)` (dekho resources/views/pdf/agent_bill.blade.php), fallback public/asset/shukran.png. Baki icon (camera, paperclip, calendar, clock, pin, UAE flag, doc icon, chocolate/attar/tasbih/pen) nijer inline SVG/component ba sample theke high-dpi crop kore resources/ e copy kore banao, design-er moto dekhte hobe.
- `public/Sample Wadi_Shukran_...Agreement.pdf` ke move/delete/edit korba na (owner pore nijei delete korbe). Code e kokhono oi file runtime e reference korba na — dorkari shob kichu repo-r vitore copy kore nao.
- Section badge: 1,2,3,4 tarpor 5 Attach Documents, 6 Signatures. Label shob EN/AR (static). PDF = ek A4 page, documents append hobe na, user-er Arabic input o jeno thik moto render hoy.
- Creator ke approver hoite block korar rule ekhon off (config flag `sales_point.block_self_approval` default false).

Kaj-er niyom:
- Phase 0 -> 1 -> 2 -> 3 -> 4 -> 5 -> 6 serial e koro. Ek phase shesh hole STOP kore ki korle, ki verify korle, kon file change holo bolo, amar "continue" er jonno wait koro.
- Prottek phase shesh hole workflow file-er checkbox tick koro ar §10 Decision log update koro.
- Phase 1 e PDF engine spike kore (mPDF vs DomPDF + image label) result o rendered PNG compare dekhao; engine final korar age amar approval nao.
- Existing convention follow koro: logic service e, FormRequest, thin controller, Pint/Prettier, notun migration file (purono migration edit korba na).
- Security: passport/Emirates ID/trade license scan kokhono public_path ba `public`/`local` disk e rakhba na (config/filesystems.php e dui disk-er root public_path()). Notun `private` disk (storage/app/private), ID number e encrypted cast, permission-guarded streaming controller.
- Prottek route e `can:` middleware; state change shudhu POST/PUT/DELETE; notun permission gulo database/seeders/CreateSuperAdmin.php te add koro. Route group `agents/registrations` ke existing `can:show agent list` group-er bhitore rakhba na.
- `db:seed`, `migrate:fresh`, `.env` run/edit korba na. `php artisan migrate` chalate hole age amake bolo.
- Unrelated code refactor korba na. OrderController, money/win/risk logic touch korba na.
- Shesh e vendor/bin/pint, php artisan test, npm run lint, npm run build chalao, real output report koro (fail hole fail-i bolo). Tarpor laravel-reviewer, vue-inertia-reviewer, migration-reviewer agent diye review nao.

Ekhon shuru koro: step 1–4 poro, tarpor Phase 0 start koro.
```

---

## B) Per-phase prompts

> Prottek prompter shurute add koro: *"CLAUDE.md ar .claude/workflows/sales-point-registration.md age poro. §4 decisions ar §4.1 terms final, notun proshno korba na. Sample PDF touch korba na."*

### Phase 0 — Prep & assets
```
Plan-er Phase 0 koro (D1–D5 final). Business code likhba na. Kaj:
(1) company logo ke web ar PDF duita jonno kivabe pawa jay confirm koro (company_setting()->logo, logo_url, fallback public/asset/shukran.png) ar kon file ache dekho;
(2) sample PDF theke high-dpi render kore icon crop / nijer inline SVG diye icon set banao (camera, paperclip, upload, calendar, clock, pin, doc, UAE flag, chocolate, attar, tasbih, pen) -> resources/ e rakho, reference image-er sathe milao;
(3) Dubai + Arimo/Liberation Sans font TTF resources/fonts/ e rakhar jonno license check kore bolo ki lagbe (internet theke download korar age amake bolo);
(4) sample PDF public/ e-i thakbe, touch korba na.
Sheshe ki ki toiri holo ar kon asset ekhono missing seta list dao.
```

### Phase 1 — PDF engine spike
```
Plan-er Phase 1 koro. Isolated kore mPDF diye resources/views/pdf/sales_point_agreement.blade.php banao hard-coded dummy data diye, sample-page2-agreement-pdf.png er same-to-same: header (company logo + pink/navy title band), color #e6007e / #243475, section bar, bilingual label, 5 terms (plan §4.1), signature box, photo box, ek A4 page. Arabic shaping check koro. PDF ke PNG render kore (scratchpad e pdfjs + @napi-rs/canvas) reference-er sathe compare koro, difference list koro. Final: mPDF vs DomPDF recommendation + screenshot. Controller/migration ekhono banaba na.
```

### Phase 2 — Backend foundation
```
Plan-er Phase 2 koro: private disk, 2 ta migration (agent_user_id chara), models, config/sales_point_agreement.php (§4.1 terms), FormRequests, SalesPointRegistrationService, controller + routes (nijer group, nijer can: middleware), permissions seeder e add, Pest test (403/200, validation, store with files, private file access control). migrate chalate hole age amake bolo. Sheshe migration-reviewer ar laravel-reviewer chalao.
```

### Phase 3 — Frontend form + list
```
Plan-er Phase 3 koro: AppSidebar e Agent group e "Registration & Agreement" (group visibility children-er permission dekhe), resources/js/pages/Agent/Registration/{Index,Form,Create,Edit}.vue, components/registration/*, labels.ts. sample-page1-form-ui.png er same-to-same design; badge 1–6. Inertia useForm + forceFormData, processing disable, error show, signature pad touch-e kaj korbe. npm run lint, vue-tsc, build chalao; vue-inertia-reviewer chalao.
```

### Phase 4 — Approval + PDF
```
Plan-er Phase 4 koro: approve / reject / regenerate-pdf / pdf endpoints, SalesPointAgreementPdfService (Phase 1 engine), DB::transaction + lockForUpdate + state re-check + terms snapshot + PDF fail e rollback, Show.vue te approve/reject panel + PDF preview/download. Pest: double-approve, non-pending approve, exactly 1 page PDF, rollback, approved immutable, download blocked for pending, IDOR. Generated PDF ke PNG render kore sample page-2 er sathe compare kore dekhao.
```

### Phase 5 — Polish
```
Plan-er Phase 5 koro (Agent-creation feature dropped). Audit trail, optional notification, pending counter. CLAUDE.md ar workflow file update koro.
```

### Phase 6 — QA & release
```
Plan-er Phase 6 koro: pint, php artisan test, npm run lint, npm run build; laravel-reviewer + vue-inertia-reviewer + migration-reviewer + permission-audit skill; production checklist (storage/app/private writable, PDF temp dir, seeder permissions, role assignment). Findings ranked list dao, unrelated fix korba na.
```

---

## C) Short version (porer incomplete phase continue korate)
```
CLAUDE.md ar .claude/workflows/sales-point-registration.md poro, checkbox dekhe porer incomplete Phase ta koro, shesh hole checkbox tick koro ar summary dao. Plan-er baire jaba na; §4 decisions final.
```
