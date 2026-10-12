# Tumeni

A simpler way to ride. A local Malawi ride-hailing foundation with a Laravel 13 API, PostgreSQL 18 and responsive React 19 / TypeScript web client. The first cash-trip vertical slice works against the real database and API. The supplied design reference informed the warm white, charcoal and deep green interface.

## Run on Windows PowerShell

Prerequisites actually observed: PHP 8.3.31 (pdo_pgsql, mbstring, openssl, fileinfo), Composer 2.10.3, Node 24.19.0, npm 11.17.0 and PostgreSQL binaries at `C:\Program Files\PostgreSQL\18\bin`. Version locks are committed as source files. Laravel 13 supports the installed PHP 8.3; React 19.2 and Vite 8 support this Node runtime. See [architecture](docs/ARCHITECTURE.md).

From the repository root:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File scripts/changes/003-setup.ps1
Set-Location backend
php artisan serve --host=127.0.0.1 --port=8000
```

Open **http://127.0.0.1:8000/**. Setup is safe to rerun: it preserves `.env`, existing databases and demo state. It uses `composer install`, `npm.cmd ci`, migrations and an empty-database-only demo seeder. `Bypass` applies only to that PowerShell process; no machine policy changes. Use `npm.cmd`, because this machine blocks `npm.ps1`. Network permission may be needed for dependency downloads. No Docker is required.

The setup script starts a separate repository-local PostgreSQL cluster at **127.0.0.1:55432**, databases `tumeni` and `tumeni_test`, role `tumeni`, timezone UTC. It does not modify the installed PostgreSQL service. This disposable local cluster uses loopback trust authentication: any process on your machine can access it. Never expose it or use it for real people or production. Change the binary path in `001-local-postgres.ps1` for other PostgreSQL installations. Database files, logs, credentials and caches are ignored by Git.

Stop the PHP server with Ctrl+C. Stop the owned development database:

```powershell
& 'C:\Program Files\PostgreSQL\18\bin\pg_ctl.exe' -D "$PWD/.local/pgdata" stop
```

Run that database command from the repository root. Start it again by rerunning `001-local-postgres.ps1` through PowerShell as above. Do not delete `.local` unless you intentionally want to discard all local demo data.

Optional frontend hot reload, second terminal:

```powershell
Set-Location frontend
npm.cmd run dev
```

Open the printed Vite URL including `/app/`; it proxies `/api` to port 8000. The normal single-server workflow serves the compiled frontend from `backend/public/app`. Rebuild after frontend edits with `npm.cmd run build` from `frontend`.

## Demonstrate the milestone

At the bottom of the page, expand **Development workspace**. These explicit local shortcuts change the current browser session. Use separate browser profiles for simultaneous roles, or switch roles sequentially. Every action uses `/api/v1`; no client-side fake trip state.

1. Open admin workspace. Enter a reason, open Peter’s private fixture document, approve driver and vehicle. Demo applications start pending. Approval is manual.
2. Open rider workspace (or sign out and use a new phone OTP). Choose Mzuzu University → Mzuzu Market, add a pickup note and see the complete **MWK 5,000** demo quote. Price exploration works signed out.
3. For first signup, enter a first name and `+265` phone number. Enter the explicitly labelled development code shown on screen. No SMS is sent. Request the ride; note the rider’s PIN.
4. Open driver workspace. Go online, inspect the cash fare, fee **MWK 500** and net **MWK 4,500** before operating costs, then accept. Credit decreases only by the fee reservation.
5. Mark arrival, enter the rider PIN, start and complete. Completion captures the fee exactly once. Duplicate completion returns the existing result.
6. Open rider workspace → Trips → completed trip for the receipt. Active trips also recover after refresh. In separate profiles, rider polling sends the rider to the receipt history when the trip completes.
7. Driver credit history shows the reserve and capture; earnings show the completed net amount. Admin Trips, Credit and Audit show the stored history.
8. Repeat with another quote; accept then cancel before pickup. Reserved credit is released once. No pre-pickup cancellation charge applies in this milestone.

The HTTP demonstration automates the same workflow using separate cookie sessions, real CSRF tokens and OTP. It retains fictional completed/cancelled trips and audit records and leaves the demo driver offline:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File scripts/changes/002-http-demo.ps1
```

## Verify

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File scripts/changes/004-verify.ps1
```

This runs `php artisan test --compact` against **tumeni_test**, then TypeScript checking and Vite build. Tests recreate the test database schema; never point it at a database containing valuable data. The race tests start independent PHP processes against PostgreSQL, checking concurrent acceptance, completion and cancellation/acceptance. No SQLite substitution or mocked locking. See [validation](docs/VALIDATION.md) for exact observed results and browser QA limits.

## What works and what remains

Implemented: session/CSRF verification, public demo quotes, booking idempotency, explicit trip transitions, manual driver/vehicle review, eligibility, driver offers, PIN checking and lockout, cash fee reservations/capture/release, immutable ledger/event/audit records, rider history/receipts, driver earnings/credit, private document upload/access, support incidents with due dates, admin service hours/bounds, versioned pricing, landmarks and manual adjustments with reasons.

Development adapters: OTP returns a random development code; routing uses explicit fixture distances between three Mzuzu landmarks; the map is illustrative and has no moving cars or live arrival times. Both adapters and demo login/seeding reject production use. Unroutable coordinates return unavailable. Availability means an actual approved online demo driver must accept. Mobile-money booking is rejected; no successful live payment is simulated.

Not production-ready: real SMS/routing/payment adapters, device-token authentication, Android applications, push/background location, automated dispatch ranking, Redis deployment, actual staff onboarding/MFA, document malware scanning and multi-document approval workflow, enforced retention/deletion, accessibility/visual browser QA, monitored support SLAs, reconciliation, operations and local launch permissions. These are documented gaps, not completed features. No production deployment was requested or performed.

Next milestone: replace development routing and SMS, add staff identity/MFA and privacy retention jobs, then implement device tokens and the first tested Android client. Mobile money should follow provider integration, signed callback/reconciliation tests and settlement review.

## Documentation

- [API contract and examples](docs/API.md)
- [Architecture and state machine](docs/ARCHITECTURE.md)
- [Credit and settlement rules](docs/MONEY.md)
- [Frontend handoff](docs/FRONTEND.md)
- [Android integration plan](docs/ANDROID.md)
- [Production readiness and local launch checklist](docs/PRODUCTION.md)
- [Validation evidence](docs/VALIDATION.md)
- [Canonical continuation handover](DEEPSEEK_HANDOVER.md)

Use PowerShell for project file/folder changes. Read and maintain `DEEPSEEK_HANDOVER.md` at the start, significant checkpoints and end of every run.