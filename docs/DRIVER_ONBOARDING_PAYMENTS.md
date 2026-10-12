# Driver verification, starter credit and PayChangu top-ups

The driver flow is: verify the account phone, save personal/vehicle details, upload the required documents, submit, await human review, then receive driver/vehicle approval. Submission is not approval. A submitted application is locked until rejected for corrections or approved. Approved/suspended drivers contact support for document changes; reviewers can reject an application for correction and re-review it after resubmission. Expired identity/licence/insurance/fitness/permit dates block going online and accepting new trips. Existing in-progress trips can still be completed safely.

## Application checklist

Personal details: full legal name, date of birth (18+), nationality, residential address, district, emergency contact name/phone, optional email, consent to verification. Phone identity comes from the authenticated account. Optional gender is supported in the API. Identity/driving details: national ID number/expiry, licence number/class and expiry, optional passport number/expiry. Vehicle: make/model/year/colour/registration plate/chassis or VIN/seats, ownership, owner name if leased/borrowed, and insurance/fitness/transport permit expiry dates.

Required uploads: national ID front/back, clear selfie, driving licence front/back, registration certificate, insurance, vehicle fitness certificate, passenger transport permit, vehicle front/rear/interior photos. Passport photo page is required when a passport number is supplied; otherwise optional. Owner authorization is required for leased/borrowed vehicles. Uploaded optional documents must also be accepted before approval. Each file is limited to 5 MB; JPG/PNG/PDF, with image-only selfie/vehicle photos.

This is the platform onboarding checklist, not an automated government-record, biometric, police/background or insurer verification service. Reviewers must actually compare identity/selfie/name, validate the licence class and records, check vehicle registration/ownership, passenger insurance, fitness, permit and vehicle condition, then attest to the explicit checklist. Every document decision and final driver decision requires an audit reason. Missing/rejected/unreviewed documents or unchecked attestations block approval. Only admin/reviewer can read full application details and decide approval; ops cannot download identity documents. Applicants can access only their own records.

New profiles and uploaded blobs are encrypted using Laravel Crypt and stored privately. APP_KEY is essential for decrypting them; preserve and manage it securely. National ID comparison uses a keyed identity fingerprint with a unique database constraint. Submitted identity cannot be freely changed to claim another trial. Private download responses have no-store and nosniff headers. Sensitive records/filenames are not included in payment payloads. Malware scanning, document retention/deletion automation and external verification integrations remain separate production work.

## Starter credit

Confirmed user decision: one-time MWK 4,500 credit after first successful driver AND vehicle approval, instead of a time-based fee waiver. Configure `DRIVER_TRIAL_CREDIT_MINOR=450000` (100 minor units = MWK 1), in backend/.env. Zero disables future grants. Approval snapshots the granted amount and date and appends a unique `driver-trial:{driver_id}` ledger entry under the driver lock. Approval retries, rejection/resubmission, suspension and reapproval do not grant it twice. Changing configuration affects future first approvals only. Credit is used for the ordinary platform fees and has no expiry in this implementation. It is not recorded as collected cash or earned commission.

## PayChangu setup

Hosted checkout uses the server-side secret key; the supplied public test key is not needed in the frontend. Never commit or print keys. Local test secret is stored only in ignored backend/.env. The app currently has test mode enabled with browser return base `http://localhost:8000`; an actual hosted sandbox session was successfully created, left pending, and credited no money.

Environment names:

```dotenv
PAYCHANGU_ENABLED=true
PAYCHANGU_SECRET_KEY=
PAYCHANGU_WEBHOOK_SECRET=
PAYCHANGU_MODE=test
PAYCHANGU_PUBLIC_URL=http://localhost:8000
PAYCHANGU_MINIMUM_TOPUP_MINOR=10000
PAYCHANGU_MAXIMUM_TOPUP_MINOR=100000000
DRIVER_TRIAL_CREDIT_MINOR=450000
```

Use your own values in ignored backend/.env. The webhook signing secret is a separate PayChangu dashboard setting, not the API secret key. localhost is allowed only for non-production test mode. Browser returns can use it; remote PayChangu webhooks cannot reach it. In local testing, complete the sandbox checkout and use Check payment in the driver console; this makes a server-side verification call. Scheduled reconciliation can also verify pending references. The callback return never itself credits the account.

For live activation, use live credentials, `PAYCHANGU_MODE=live`, an actual public HTTPS base URL and the webhook signing secret; production rejects test mode, obvious test keys, HTTP callbacks and missing signing configuration. Do not promote the local demo database or simulated balances into a real-money deployment. Register this webhook URL in the PayChangu dashboard:

`https://YOUR-HOST/api/v1/payments/paychangu/webhook`

Browser success/cancellation return: `/api/v1/payments/paychangu/return`. Only those provider-facing routes are exempt from browser CSRF checks; driver/admin mutation routes remain protected by sessions and CSRF.

## Top-up behavior

Drivers enter a MWK amount and continue to PayChangu. The backend creates an owned top-up intent with a server-generated transaction reference and an idempotency key. Provider checkout is attempted once for that intent; an ambiguous timeout leaves it pending for verification instead of blindly repeating payment creation. The driver can resume a saved checkout link, check payment, and see recent top-ups plus credit history.

Before adding credit, the backend verifies with PayChangu and requires successful status, matching tx_ref, exact expected MWK amount, matching currency and test/live mode, and a provider reference. Provider major amounts are parsed into integer minor units; excess/partial payments are held for review rather than silently credited. The unique ledger reference, unique provider reference and driver/top-up row locks prevent repeated callbacks/manual checks from crediting twice. No value is granted merely because checkout creation, a browser return or a webhook says success. Webhooks authenticate the raw body using HMAC-SHA256 Signature, then verify server-side. No raw card/mobile-money credentials or complete provider payload are stored by this integration.

Cash trip fees still reserve/capture/release as before. PayChangu tops up driver credit; public rider booking remains cash. Rider digital payments and provider payouts are not enabled by this change. Automated refunds/chargebacks and reversal reconciliation are not implemented: operations must investigate those provider events and make explicit audited ledger adjustments; do not assume a top-up is irreversible. Start with sandbox payment acceptance before enabling live collection.

## Commands and verification

From repo root in PowerShell:

```powershell
Set-Location backend
php artisan config:clear
php artisan migrate --force
php artisan serve --host=127.0.0.1 --port=8000
```

In a second terminal, root:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File scripts/changes/004-verify.ps1
powershell.exe -NoProfile -ExecutionPolicy Bypass -File scripts/changes/010-paychangu-sandbox-check.ps1
```

The sandbox check creates/reuses one MWK 100 pending test checkout for a fictional seeded driver; it refuses non-test/production use and does not complete payment. It needs a seeded driver and configured test secret. Run `php artisan paychangu:reconcile` from backend to check pending payments. The task is scheduled every ten minutes; deployment must run Laravel's scheduler (for local use, `php artisan schedule:work`). No scheduler daemon is started automatically.

Migration 000003 is additive and was applied to local tumeni; PHPUnit migrations only use tumeni_test. The old single-document demo fixture cannot approve a driver anymore. 002-http-demo.ps1 now uploads explicitly fictional encrypted document fixtures and exercises submission, per-document review and the full approval checklist before its cash-trip flow; it refuses non-development or non-demo applications. Existing CashTripTest fixture setup explicitly represents verified/current drivers and checks that legacy single-document approval is rejected.

Tests cover full submission/encryption/access, locked pending state, required and conditional documents, expiry validation, identity uniqueness, repeated approval/suspension without duplicate starter grants, hosted-checkout retries/idempotency, ownership, exact amount/reference/currency/mode checks, signed duplicate webhooks, pending results, callback forgery, production test rejection and connection ambiguity. Browser touch/layout and native-speaker review are still pending.

Official references checked during implementation: [hosted checkout](https://developer.paychangu.com/docs/standard-checkout), [transaction verification](https://developer.paychangu.com/docs/transaction-verification), [webhook signatures](https://developer.paychangu.com/docs/webhooks).
