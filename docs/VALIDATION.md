# Validation evidence — 2026-10-12 Africa/Johannesburg

## Passing final checks

- `powershell.exe -NoProfile -ExecutionPolicy Bypass -File scripts/changes/004-verify.ps1`: exit 0. `php artisan test --compact`: **25 tests passed, 105 assertions, 20.54 seconds**. Includes 19 cash/domain/API tests, four fake payment event tests and two Laravel starter smoke tests. PostgreSQL 18 on 127.0.0.1:55432/tumeni_test. Separate-process race tests verify one acceptance winner, exactly one completion capture and cancellation/acceptance release integrity.
- Same script: `npm.cmd run build`: TypeScript 5.9.3 check and Vite 8.3.4 build passed, 1627 modules transformed, JS 332.13 kB / 102.69 kB gzip, CSS 24.60 kB / 6.24 kB gzip. Actual resolved React/React DOM 19.3.0, Tailwind 4.3.3. No Android build attempted.
- `powershell.exe -NoProfile -ExecutionPolicy Bypass -File scripts/changes/002-http-demo.ps1`: exit 0, twice. Final retained completed trip 9f2a4613-3a07-4776-90bf-c438e3597ce2 and cancelled trip f6c5a6f5-680c-4aa8-8036-b3088e49ab05. Real HTTP, independent cookie sessions, regenerated CSRF, random development OTP, manual driver/vehicle approval, public quote, idempotent booking, driver online/accept/reserve/arrive/PIN/start/complete retry, rider receipt, admin five-event history/single capture, genuine cancellation release. Demo driver left offline. All identities/transactions are fictional development data.
- Home and bootstrap GET HTTP 200. Missing CSRF token on POST dev/login with established session returned **419**. Bootstrap response Cache-Control: **no-store, private**.
- PowerShell Parser.ParseFile: all four scripts syntax-valid. `php -l` checked 27 project PHP files including domain/controllers/migrations/tests, all passed. Source/docs/scripts checked for unexpected UTF-8 BOM: none found.
- `git check-ignore` confirmed runtime .env, local database, node_modules and compiled app are ignored. Initial dependency installation: Composer reported no advisories, npm reported 0 vulnerabilities. No final online audit was repeated.
- Both domain migrations executed successfully in local development; test schema is migrated freshly per test.

## Earlier failures resolved

- Sandboxed Composer network/cache access failed. Retried with approved network escalation and repository-local cache; Laravel installation succeeded.
- npm.ps1 blocked by PowerShell policy; used npm.cmd without changing machine policy.
- initdb under sandbox account failed restricted token re-execution; approved isolated development cluster setup succeeded, no existing service changes.
- First test run failed on quote expiry due to server timezone interpretation of naive UTC timestamps. Fixed database connection and role timezone to UTC; operating hours/day boundaries remain Africa/Blantyre.
- Repeated migrate:fresh left a standalone trigger function; CREATE OR REPLACE made test schema reconstruction reliable.
- Production-mode test initially stopped at CSRF and then production migration teardown prompt. Added valid test CSRF and restored testing environment in finally; verified production guards separately. Actual HTTP without CSRF remains rejected.
- An overly long inline PowerShell invocation exceeded the Windows command length. No files were changed by that failed invocation; writes were split into smaller PowerShell operations.
- A targeted replacement failed its expected unique-match guard; prior successful independent edits were retained and remaining edits applied with a precise target. No silent truncation.
- Windows PowerShell 5.1 ConvertFrom-Json cannot parse the npm lock empty root property; inspected lock versions with read-only Node instead.

## Not verified

Browser visual/interactive QA: cua browser inventory returned no available apps/browsers, so no screenshots, mobile viewport checks, screen-reader or keyboard end-to-end assessment. No alternative browser automation was used. HTTP tests verify contracts, not rendered React interactions. Live SMS/routing, mobile money, Redis/worker failover, document scanning, retention, sustained load/security audit, actual staff onboarding and physical Android behavior remain unverified/unimplemented as stated in PRODUCTION.md.

The local PHP preview server was stopped after verification; PostgreSQL development cluster remains running. Start PHP using README commands. The full setup script was syntax-checked; its underlying scaffold/install/migration/seed/build/database commands were executed individually during implementation. The composed setup script itself was not rerun after its final signing-secret generation addition.