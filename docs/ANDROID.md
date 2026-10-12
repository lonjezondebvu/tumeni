# Android integration plan (not built)

No APK, React Native project, device-token endpoint, push notification integration or background-location service has been built or tested. The shared API and server rules are the foundation, with a web development driver console exercising them.

## Proposed workspace

```text
mobile/
  rider/       React Native TypeScript Android project
  driver/      separate Android app and permission scope
packages/
  api-client/  generated types/client from a future OpenAPI contract
  ui-tokens/   colours, spacing and typography only
backend/       authoritative pricing, transitions, roles, ledger
frontend/      responsive rider and operations clients
```

Do not duplicate authorization, fare or settlement rules in React Native. Extract API TypeScript types when contracts stabilize; currently they live in frontend/src/api.ts and docs/API.md. Use one account identity and trip database for all clients. Phone number plus provider verification recovers the same account; define recovery for SIM loss/reassignment before launch.

## Device authentication to implement

Separate `/api/v1/mobile/auth/otp`, verify, tokens and revoke routes, backed by Sanctum or equivalent hashed opaque revocable device tokens. Tokens limited to device/account/role, named devices, expiry and last-used metadata. Server stores only token hash; Android stores secret in Keystore-backed secure storage. Revoke current/all devices and staff-compromised devices. Staff web should use stronger auth/MFA, not rider OTP. Mobile uses Authorization Bearer over TLS without web CSRF; web keeps HttpOnly secure SameSite session + CSRF. Never weaken web cookies to share bearer token logic. Test revocation and role/resource denial before enabling apps.

## Required contracts

Bootstrap/service/landmarks, quote, OTP/verification, account, booking with Idempotency-Key, list/read active trip, receipt/history, cancel/support; driver application/private uploads/status, online, offers, accept/decline, arrive/PIN/start/complete/interrupt, earnings/credit/reviews. Existing route inventory: docs/API.md. Mobile auth and device/push registration endpoints are future work. Current role model separates rider and driver; multi-role accounts need an explicit migration/permission design, not client-side switching.

## Events, retries and recovery

Initial fallback: foreground polling every 5s, stop on terminal state, exponential backoff with jitter when offline and respect 429. GET trips after token recovery/app resume to reattach active trip. Reconcile uncertain requests before retry; reuse same booking intent UUID and payload. Completion/accept/cancel retries reconcile state, never create a parallel financial request. Preserve pending actions in encrypted local storage and clear after acknowledgement; do not automatically replay old arrival/start actions in a new trip.

Future subscriptions: authenticated account/trip channels, event IDs and monotonic trip revision; reconnect using last event cursor then REST reconcile. Transactional outbox written with state change prevents lost messages. FCM integration points: offer created/withdrawn/expired, assignment, arrival, cancellation, completion/support status. Push is a hint to refetch authorized state, not a source of pricing/PIN truth. Do not include phone, exact rider coordinates, documents or PIN in lock-screen push payloads. There is no outbox or broadcasting implementation yet.

## Location and Android permission requirements

Rider app asks foreground location only when choosing pickup; manual landmark/coordinate option remains available after denial. No rider tracking after cancellation. Driver app requests foreground permissions before online and explains purpose. A production background driver-location implementation requires Android version-specific foreground service/location permissions, visible ongoing notification, consent, battery constraints, accuracy/staleness handling, service lifecycle and Play policy review. Denied/revoked permission must force honest unavailable status; no fabricated locations or ETAs. Confirm exact target SDK requirements from official Android docs when implementing; these details change.

Current driver console is not a background-location solution. Current API has no location-ingestion endpoints. Proposed ingestion must bind authenticated driver/device, online/active-trip status, bounded rate and retention policy; stale position must stop new offers. Route reachable pickup estimates via provider interface; do not rank by straight-line distance disguised as road time.

Next acceptance gate: verified token lifecycle and shared account/trip recovery; one Android rider booking through real API; driver app lifecycle on physical devices under offline/reconnect/background/revocation scenarios; separate permissions and server authorization tests; provider sandbox integration before public release.