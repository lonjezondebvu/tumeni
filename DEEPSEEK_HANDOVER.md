# DEEPSEEK HANDOVER

## 1. START HERE
Purpose: Malawi ride-hailing modular monolith. Milestone: locally runnable cash-trip vertical slice. Status: IN PROGRESS, 2026-10-12 Africa/Johannesburg.
Last action: inspected empty repository and installed runtimes. Next: scaffold Laravel and record contracts.
Read applicable AGENTS.md files (none found). Use PowerShell for all file/folder changes. Maintain this handover every run.

## 2. PRODUCT REQUIREMENTS AND CONSTRAINTS
Rider responsive web, future rider/driver Android, admin web; shared /api/v1 backend. Honest availability, explicit demo routing/OTP; cash first; integer MWK minor units; fee reserve/capture/release with immutable ledger. No surge, pooling, delivery or nationwide claims. Mzuzu is demo only. Reference image: off-white, charcoal, green, clear action hierarchy. Production transport, insurance, privacy and payment arrangements remain unverified.

## 3. REPOSITORY AND ENVIRONMENT
Empty main branch without commits; no pre-existing work. PHP 8.3.31, Composer 2.10.3, Node 24.19.0, npm 11.17.0; PostgreSQL 18 service running. PHP pdo_pgsql available, sqlite unavailable. Redis not observed. npm.cmd required because npm.ps1 execution is disabled. All commands from PowerShell. Setup commands pending scaffold.

## 4. IMPLEMENTATION STATUS
All modules not started. No migrations or tests executed.

## 5. ARCHITECTURE AND CONTRACTS
Planned Laravel modular monolith, PostgreSQL transactions and row locks; same-origin React/TS frontend with session/CSRF. Mobile revocable tokens documented separately. Explicit transitions and separate payment/credit states. Demo adapters production-disabled.

## 6. CHANGES IN THE LATEST RUN
Created docs and scripts/changes directories; this handover. No dependencies yet.

## 7. VALIDATION
Observed php -v, composer --version, node --version, npm.cmd --version, php -m. git status clean; no commits. npm.ps1 blocked by execution policy; use npm.cmd. No application tests yet.

## 8. KNOWN ISSUES AND BLOCKERS
PostgreSQL application access not yet checked. No provider credentials. Android apps not started.

## 9. NEXT ACTIONS
Scaffold backend, configure isolated development PostgreSQL, implement domain/API/tests, React UI, docs. Verify concurrency and exact-once ledger against PostgreSQL. Do not overwrite unrelated work.

## 10. RUN HISTORY
2026-10-12: initial run started.

## SUCCESSOR AGENT INSTRUCTIONS
You are continuing an existing project. Read this document and applicable AGENTS.md files before making changes. Verify claims against the repository. Preserve uncommitted work. Continue from the recorded next action instead of rebuilding completed modules.
Use PowerShell scripts for all project file and folder changes, and run development commands from PowerShell. Follow the documented encoding and replacement safeguards.
Update DEEPSEEK_HANDOVER.md at the start, at significant checkpoints and before ending every run, including blocked or unsuccessful runs. Record actual test results and distinguish working code from scaffolding and assumptions.
Do not copy secrets, private identity documents or real customer data into the handover. Record environment variable names and secure configuration locations only.