# DEEPSEEK_HANDOVER.md

## 1. START HERE

Purpose: Malawi ride-hailing platform, rider responsive web, future rider/driver Android, admin web and shared Laravel API.
Current milestone: first locally runnable end-to-end cash-trip slice. Run status: **COMPLETED (local milestone); production and Android remain incomplete**.
Timestamp: 2026-10-12 17:54:42 +02:00 Africa/Johannesburg.
Last completed action: final test/build/syntax/HTTP checks passed, documentation reconciled, local PHP preview stopped; PostgreSQL remains running.
Exact next action: read this handover, applicable AGENTS.md and docs/PRODUCTION.md; inspect actual git status; run setup/start commands below, then connect a browser and validate rider signup/quote/trip and admin/driver responsive UI before provider integration.
Read applicable AGENTS.md files before changes (none found during this run). Use PowerShell scripts for ALL project file/folder changes; use UTF-8 without BOM, literal single-quoted here-strings, unique expected-content guards and safe handover replacement. DeepSeek must maintain this same canonical handover at start, checkpoints and end of every run.

## 2. PRODUCT REQUIREMENTS AND CONSTRAINTS

Confirmed: Laravel modular monolith, versioned /api/v1, PostgreSQL, React/TypeScript responsive rider and admin web. Future React Native Android clients share account/API/business rules. One configurable area/car category, immediate cash rides, manual approved drivers/vehicles, optional street addresses, landmarks/meeting points/notes, sign-in at first booking, returning account recovery. UI reference: warm white, charcoal, deep green, large readable text and clear primary action. Honest status: no fabricated drivers/ETAs, no straight-line road pricing. Safety/help accessible; cancellation/GPS alone never proves fraud. “Certainty” is a positioning hypothesis, not market evidence.
Money: integer MWK minor units; reserve cash fee on accept, capture once on completion, release for unfulfilled cancellation; reversals append. Digital commission through settlement without a second credit deduction. Separate trip/payment/ledger/reservation/commission/settlement. Demo MWK 5000/500/4500 is an assumption.
Excluded: pooling, bidding, deliveries, subscriptions, loyalty, surge, nationwide rollout. Android apps and production deployment were not built. Mzuzu is labelled demo only, not a permanent launch choice. Live provider credentials, launch city/pricing/hours, approved retention policy and legal/insurance/payment arrangements remain unresolved.

## 3. REPOSITORY AND ENVIRONMENT

Initial repository: empty main, no commits, no uncommitted work; no AGENTS.md found along applicable parent paths. During work an external commit appeared: latest observed **a7ffe8b first set up**, main tracking origin/main. Agent did not create that commit or push. Preserve it and current documentation changes; inspect git status anew.
Structure: backend/app/Domain (TripService, routing/payment interfaces/adapters/inbox), backend/app/Http (controller/privacy middleware), backend/routes/ride.php, backend/database/migrations and seeders; frontend/src (React); docs; scripts/changes; canonical root handover.
Observed: PHP 8.3.31, Composer 2.10.3, Node 24.19.0, npm 11.17.0, PostgreSQL 18. Installed Laravel 13.35.0, React/React DOM 19.3.0, Vite 8.3.4, TypeScript 5.9.3, Tailwind 4.3.3. Read exact locks rather than trusting a stale summary. PHP pdo_pgsql available, pdo_sqlite unavailable. No Redis/Docker observed.
Owned local PostgreSQL: .local/pgdata, 127.0.0.1:55432, role tumeni, databases tumeni and tumeni_test, UTC. Trust authentication on loopback is local/demo only. Existing installed service was not modified. Local PHP preview port 8000 stopped at handoff. No browser surfaces available.
Environment names: APP_ENV, APP_KEY, APP_DEBUG, APP_URL, DB_CONNECTION/HOST/PORT/DATABASE/USERNAME/PASSWORD, SESSION_DRIVER/SECURE_COOKIE/SAME_SITE, CACHE_STORE, QUEUE_CONNECTION, DEVELOPMENT_ADAPTERS, SUPPORT_HOURS, DEV_PAYMENT_WEBHOOK_SECRET. No secrets belong here. backend/.env ignored; .env.example has no secrets; signing-secret example blank. Setup script can generate it locally. File sessions/cache and synchronous queue locally.
Exact PowerShell commands, repository root:
```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File scripts/changes/003-setup.ps1
Set-Location backend
php artisan serve --host=127.0.0.1 --port=8000
```
Open http://127.0.0.1:8000/. Second terminal root:
```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File scripts/changes/004-verify.ps1
powershell.exe -NoProfile -ExecutionPolicy Bypass -File scripts/changes/002-http-demo.ps1
```
Optional frontend: Set-Location frontend; npm.cmd run dev (printed URL with /app/). Do not use npm.ps1 or weaken machine policy. PostgreSQL binary path is documented in README and 001 script. Stop DB from root via pg_ctl -D "$PWD/.local/pgdata" stop. Setup never resets existing data; DemoSeeder only seeds empty accounts.

## 4. IMPLEMENTATION STATUS

- Cash domain/API: complete and verified for milestone. TripService and RideController; quotes/trips/events/reservations/ledger/commissions/settlements; locking, idempotency, insufficient credit, eligibility, PIN rate limits, cancellation, interruption/expiry.
- Rider web: implemented/typechecked/built, HTTP contracts verified; rendered UI NOT browser-tested. Public quote, landmarks/manual pickup/location-denial path, pickup note, cash price, development phone verification, request, honest matching, driver/vehicle/PIN, cancellation/help, active recovery, history/receipt. Illustrative map only; no live map pin/geocoder.
- Driver development console: implemented/build verified, real API exercised via HTTP. Human approval/online/offers/accept/decline/arrival/PIN/start/complete/interrupt; earnings/credit/review due dates. Explicitly not Android/background location. Application saves vehicle/details; one private document slot; full document checklist and application rehydration partial.
- Admin web/API: manual driver+vehicle review/private download; service bounds/hours; versioned fare/commission; landmark aliases/verification; trips/events/ledger/support/manual adjustments and audit reasons. API role restrictions implemented; reviewer/ops navigation permission filtering partial.
- Authentication: development OTP with hashed/random/expiring/consumable code; sessions+CSRF; no web localStorage bearer auth. Production OTP/login/routing unavailable until real providers. Device tokens/recovery/MFA not started.
- Payments: cash working. PaymentProvider/DevelopmentPayment/PaymentEvents plus payment_events immutable inbox implement HMAC signature/duplicate/order handling for internal fake digital events. Production-disabled, admin-only dev endpoint, explicit development_* status and no money movement. Public digital booking rejected; live mobile money/payout/reconciliation not implemented.
- Support: incident creation/resolution audit, already-in-car/road/fee report categories, visible 48-hour due dates. Escalation/SLA automation not implemented. No guaranteed emergency claim.
- Android: documentation only, not scaffolded/built/tested. Redis/outbox/push/background location/retention enforcement: not started.

## 5. ARCHITECTURE AND CONTRACTS

docs/API.md is endpoint/body/error reference; docs/ARCHITECTURE.md explains boundaries/auth/locking; docs/MONEY.md details ledger; docs/FRONTEND.md handoff; docs/ANDROID.md future integration.
Same-origin React SPA, Laravel web middleware for /api/v1 session+CSRF, accounts identity table. Starter users/cache/jobs scaffolding remains unused by domain. Roles rider/driver/reviewer/ops/admin; resource ownership checked. PIN hash plus encrypted rider display copy; never exposed to drivers/staff. Private document storage under backend/storage/app/private.
States: matching→assigned→driver_arrived→in_progress→completed; pre-start cancelled, matching expired, in-progress interrupted with reason/review. requested is atomically represented as matching event; driver_arriving recognized but not emitted by console. Payment/fee statuses separate. Five-minute quote, ten-minute matching; expiry on reads, no scheduler yet. Driver offers polling, no reachability ranking/ETA claims.
Integer minor units, MWK explicit pricing/ledger/commission; settlements inherit trip currency. Reserve negative available-credit ledger, capture zero (no double subtraction), release positive. Unique references/per-trip records, append-only triggers. Rider and driver partial unique active-trip indexes. Transactions retry deadlocks up to five times. UTC connections; Africa/Blantyre operating hours/earning days. No async monetary effects.
Development routing explicitly declared fixture distances; unsupported coordinates return unavailable. Fake payment signature verified before effects, immutable event ID/hash binding, version ordering, cash rejected, no automatic trip/commission/credit effects. Real providers deferred. No continuous rider tracking or driver location ingestion. Exact locations currently retained indefinitely; enforcement blocks production.

## 6. CHANGES IN THE LATEST RUN

Created Laravel backend and React frontend from empty repo; two domain migrations plus framework scaffolding; DemoSeeder with fictional pending drivers, 3 demo landmarks, demonstration pricing/credit/private review fixture. Both migrations executed. Dependencies locked; compiled assets/vendors/caches/local DB/secrets ignored.
PowerShell-only filesystem changes: guarded inline scripts, reusable scripts/changes/001-local-postgres.ps1, 002-http-demo.ps1, 003-setup.ps1, 004-verify.ps1. No Bash/Python/apply_patch editing. Generators/managers/tests run from PowerShell. Handover uses .tmp then safe Move-Item replacement. Root README and 7 docs created; source/UI/contracts/production gap documentation reconciled.
No unfinished partial source write remains. Failed oversized command made no changes; failed unique replacement stopped safely after earlier independent writes; corrected. Agent made no commits, merges, pushes, production deployments or external messages.

## 7. VALIDATION

Final `004-verify.ps1`: exit 0, **25 tests / 105 assertions passed (20.54s)**; 19 cash/API/domain, 4 fake callbacks and 2 scaffold smoke tests. Real PostgreSQL, separate PHP processes for acceptance/completion/cancel races. TypeScript and Vite build passed (332.13kB JS, 102.69 gzip; 24.60kB CSS, 6.24 gzip). `002-http-demo.ps1` twice passed full cookie/CSRF/OTP/cash lifecycle and cancellation; final demo driver offline. Final completed trip 9f2a4613-3a07-4776-90bf-c438e3597ce2, cancelled f6c5a6f5-680c-4aa8-8036-b3088e49ab05; fictional records retained.
HTTP home/bootstrap 200; tokenless established-session POST rejected 419; API Cache-Control no-store/private. Four PS scripts parser-valid; php -l on 27 PHP files passed; source UTF-8 BOM checks passed; ignored secrets/runtime artifacts checked. Initial Composer/npm audits no advisories. Full composed setup script syntax checked, underlying operations executed individually; final setup signing-secret branch not rerun. Browser inventory empty, NO visual/interactive/mobile/screen-reader QA. See docs/VALIDATION.md exact commands/failure history/limits. No Android/live providers/Redis/load/production tests.

## 8. KNOWN ISSUES AND BLOCKERS

No connected browser: reproduce cua.getState returning apps=[]/browsers=[]; cannot visually verify layouts. Do not claim screenshots/UI QA. Live credentials/adapters, private multi-document review, device token auth, retention/deletion and operational/local readiness block production. Frontend docs list known UI limitations. Main implementation remains a local slice, not a full public platform.
Resolved environment issues: Composer network/cache required approved escalation; initdb sandbox token failure resolved via approved local cluster setup; npm.cmd bypasses blocked wrapper; UTC role/connection fixed false quote expiry; CREATE OR REPLACE trigger function fixed repeated fresh migration; production test restores testing environment before teardown. PowerShell5 JSON parser cannot handle npm lock empty root property: inspect using read-only Node.
Do not rerun migrations/fresh tests against tumeni; tests target tumeni_test and recreate it. Local trust auth only fictional data. No live phone/documents/payment accounts used. No blocker remains for the local cash milestone.

## 9. NEXT ACTIONS

1. Read AGENTS/handover/docs and inspect git status. Preserve external commit/current work. Start from existing modules, no rescaﬀold.
2. Connect browser, run responsive/keyboard/signup/receipt/admin/driver UI verification; fix observed issues with PowerShell scripts, then npm.cmd run build and relevant tests.
3. Split growing RideController into auth/driver/operations/support boundaries without moving business rules into clients. Add full driver document workflow and application recovery.
4. Configure real routing+SMS, staff MFA/onboarding and approved retention jobs; verify fail-closed adapter behavior, stale-driver handling and privacy cleanup before real data.
5. Device tokens/revocation and Android API client/physical-device milestone. Implement actual provider mobile money/reconciliation only after contract/sandbox verification.
Acceptance for next milestone: UI browser checks documented; real provider errors honest; staff access/recovery/privacy lifecycle tested; no development path available in production. Required checks: 004-verify.ps1, HTTP demo on fictional local DB, provider sandbox/security cases and browser accessibility checks. Deployments/real paid services/sharing real data require explicit scope/authorization; do not infer them from this local request. Never overwrite domain/ledger or replace tested PostgreSQL with mocked/SQLite locking.

## 10. RUN HISTORY

2026-10-12 Africa/Johannesburg: initial IN PROGRESS run in empty repository. Scaffolded and implemented first cash vertical slice; verified PostgreSQL concurrency and real session/CSRF HTTP flow. Added fake signed payment-event boundary, contracts/docs and PowerShell workflows. Final 25 tests/105 assertions and frontend build passed. Completed local milestone; retained explicit production/Android/browser QA gaps. Preview stopped; local development database retained/running.

## SUCCESSOR AGENT INSTRUCTIONS

You are continuing an existing project. Read this document and applicable AGENTS.md files before making changes. Verify claims against the repository. Preserve uncommitted work. Continue from the recorded next action instead of rebuilding completed modules.

Use PowerShell scripts for all project file and folder changes, and run development commands from PowerShell. Follow the documented encoding and replacement safeguards.

Update DEEPSEEK_HANDOVER.md at the start, at significant checkpoints and before ending every run, including blocked or unsuccessful runs. Record actual test results and distinguish working code from scaffolding and assumptions.

Do not copy secrets, private identity documents or real customer data into the handover. Record environment variable names and secure configuration locations only.