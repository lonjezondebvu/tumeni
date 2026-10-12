# Production readiness and deferred milestones

This is a local, fictional-data cash-trip slice. No production availability, emergency-response, legal-compliance or Android-completion claim is made. “Certainty” remains a positioning hypothesis for user research.

## Blocking technical work

- Live SMS provider abstraction/delivery and abuse protection; staff onboarding with MFA; secure recovery for lost/reassigned numbers, sessions/device revocation. Demo endpoints must stay disabled in production, and demo seeds must never enter production databases.
- Real road-routing/geocoding adapter and supported service boundary; road ETA ranking, route validation, operating-hour changes between quote/request, stale driver eligibility/location expiry. No silent straight-line fallback.
- Driver identity, driving and vehicle document checklist with multiple private documents, resubmission/version history, malware scanning, reviewer access audit and human eligibility policy. One slot in this slice is not sufficient for launch review.
- Mobile money: signed provider callback, inbox ordering/dedup, reconciliation, refunds, provider settlement/payouts and sandbox validation. Development fake adapter only; public digital booking disabled. Add currencies to every future settlement/payout record rather than relying on trip snapshot inheritance.
- Redis cache/coordination/queues, authenticated event outbox/subscriptions, notifications and expiry scheduler; resilient worker recovery. Current database money/trip mutations are synchronous and do not depend on Redis.
- Deployment secrets, separate least-privilege database roles, backups/restore drill, TLS/secure cookies, APP_DEBUG=false, monitoring and PII-safe logs; production environment smoke test. Local PostgreSQL trust auth is never production configuration.
- Browser/device visual and accessibility QA; API cursor pagination and performance review; sustained concurrent load, callback races and financial reconciliation. Compile and smoke tests are insufficient for launch.
- Incident escalation, staffing and enforced review deadlines. Current 48-hour due date is visible but no automated escalation or SLA monitoring is implemented. Do not promise guaranteed emergency response.

## Location collection and retention design

Actual implementation: user-triggered one-shot rider location for pickup only, optional manual input, stored chosen trip points/notes; no continuous/background rider tracking, no ingestion after cancellation, no production background driver tracking. Immutable event/audit reasons may contain staff/user text; do not include documents, PINs or full phones in reasons.

Proposed policy to approve before real data: exact trip coordinates and pickup notes retained no more than 90 days after terminal trip unless an incident/legal hold is specifically recorded; operational driver location samples at most 24 hours outside incident hold; documents limited to approval/required retention purpose and access logged; financial/audit retention based on verified local requirements with location/identity minimized. Account deletion/anonymization must preserve lawful financial records without retaining unnecessary personal data. **No pruning/hold/export/deletion jobs are implemented.** Dates are proposed engineering defaults, not legal findings. Implement and verify them before accepting real users/documents. Prevent API/log caches of personal trip data (current API Cache-Control no-store implemented).

## Local launch checklist (unverified)

- [ ] Obtain local advice and confirm applicable transport/operator permissions, vehicle and driver licensing, inspections and service-area limits with relevant authorities.
- [ ] Confirm commercial passenger/ride-hailing insurance, coverage/exclusions, claims handling and incident procedures.
- [ ] Confirm privacy/data-protection duties, notices/consent, data-controller arrangements, processor contracts, cross-border hosting, retention, rights handling and breach response.
- [ ] Confirm mobile-money provider contracts, collection/settlement authority, reconciliation, refunds/disputes, tax/accounting treatment and safeguarding of driver credit.
- [ ] Document fare/fee disclosure, free pre-pickup cancellation policy for the slice, exceptional ending policy, driver dispute deadlines, support hours and truthful emergency guidance.
- [ ] Validate pickup landmarks/entrances with local users; test location-denied/offline flows, language/literacy/accessibility, cash handling and actual road travel times.
- [ ] Run a small authorized pilot with approved real drivers only after technical and local permission gates pass.

No web legal research was used to assert local compliance. Checklist items require current authoritative verification and local professional review before launch.

## Ordered next milestones

1. Real SMS/routing, staff auth/MFA, complete document/retention workflows and browser QA. Acceptance: provider failures honest; production cannot invoke demo paths; documented private-data lifecycle tested.
2. Revocable device tokens, shared API client and first React Native Android rider flow; physical-device recovery/retry tests.
3. Driver Android foreground/background location, real dispatch and FCM/outbox, approved privacy/permission behavior.
4. Mobile-money sandbox, verified callbacks, settlement/refund/reconciliation audit and provider agreement. Enable public digital option only after this gate.
5. Operational pilot with support staffing, launch permissions/insurance, monitoring and backup recovery proven.

Excluded first milestone: pooling, bidding, deliveries, subscriptions, loyalty, surge or nationwide rollout. Human-review road reports never rewrite routing automatically. Cancellation/GPS alone never proves fraud or a paid journey.