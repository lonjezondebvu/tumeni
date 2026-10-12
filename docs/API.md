# API v1 contract

Base: `/api/v1`. JSON, `Accept: application/json`, same-origin cookie session. GET bootstrap first; keep cookies and send its csrf_token as X-CSRF-TOKEN on **every POST**, including login and verification. Refresh bootstrap after login/logout because the session/token regenerates. Tokens stay in memory. For the development webhook simulator, both admin session/CSRF and raw-body signature are required. There are no production webhook or mobile-token routes yet.

Money fields are integer MWK minor units (100 = MWK 1). Dates are stored UTC; expires_at may be an ISO date or PostgreSQL UTC timestamp. Treat IDs as opaque strings (trip/quote/challenge UUIDs); pagination currently caps history at 100, operations events/ledger at 200. Cursor pagination and event streaming are deferred. Poll active trip every five seconds while foregrounded; stop on terminal state. Never infer acceptance from a timeout.

## Endpoints

| Method / path | Access | Body / result |
|---|---|---|
| GET bootstrap | public | csrf_token, account or null, landmarks, service, support_hours, development |
| POST quotes | public, rate limited | pickup/destination → versioned five-minute quote |
| POST auth/otp | public, development only | phone in +265XXXXXXXXX → challenge_id, development_code |
| POST auth/verify | public, development only | challenge_id, code, first_name → account and regenerated session |
| POST auth/logout | current session | invalidate session; bootstrap for new CSRF |
| POST dev/login | development only | role rider/driver/admin → fictional seeded session |
| POST trips | rider | quote_id, payment_method=cash, optional pickup_note; Idempotency-Key required |
| GET trips | own rider/driver, or admin/ops | trips array, max 100 |
| GET trips/{uuid} | owning rider, assigned driver, admin/ops | trip and current status |
| POST trips/{uuid}/arrive | assigned driver | empty body |
| POST trips/{uuid}/start | assigned driver | pin: four digit string |
| POST trips/{uuid}/complete | assigned driver | empty body, idempotent |
| POST trips/{uuid}/cancel | participant before start | required reason; idempotent release |
| POST trips/{uuid}/interrupt | assigned driver after start | reason; release + timed incident |
| GET driver | driver | eligibility, offers, earnings, available credit, ledger, own review cases |
| POST driver/online | driver | online boolean; approved driver and vehicle required |
| POST driver/offers/{uuid}/accept | eligible driver | empty body; one winner; reserve fee atomically |
| POST driver/offers/{uuid}/decline | driver | empty body; hide this driver’s offer |
| POST driver/application | rider or unapproved driver | vehicle, plate, optional details; saved pending; changes account role to driver |
| POST driver/document | unapproved driver | multipart document PDF/JPEG/PNG, max 5 MB validator |
| GET drivers/{id}/document | own driver/admin/reviewer | private download, no public URL |
| POST incidents | signed-in | optional owned trip_id, category help/already_in_car/road_report/fee_dispute, description |
| GET admin | admin/ops/reviewer | role-filtered operations data |
| POST admin/drivers/{id}/review | admin/reviewer | status approved/rejected/suspended, vehicle_approved boolean, reason |
| POST admin/settings | admin | name, opens, closes HH:mm, enabled, bounds {south,north,west,east}, reason |
| POST admin/pricing | admin | base_minor, per_km_minor, commission_bps 0..10000, reason; creates version |
| POST admin/landmarks | admin/ops | optional id to update; name, aliases[], latitude, longitude, verified, optional meeting_point, reason |
| POST admin/drivers/{id}/credit | admin | signed nonzero amount_minor, unique reference, reason; reference-bound retries |
| POST admin/incidents/{id}/resolve | admin/ops | reason, recorded in append-only audit |
| POST dev/payment-events | development admin only | signed fake event, see below; no live payment |

All admin reasons min five characters. Uploaded documents remain private; application is not an approval. Offer details intentionally omit rider phone and PIN. Driver gets pickup/destination, pickup note, route distance/duration with provider label, fare, payment and net earnings. Pickup ETA unavailable in this slice.

## Quote example

```json
{"pickup":{"landmark_id":1},"destination":{"landmark_id":2}}
```

Manual point accepts latitude/longitude, display_name, optional meeting_point; street address is never required. Example:

```json
{"pickup":{"display_name":"School gate","latitude":-11.44,"longitude":34.0,"meeting_point":"Side entrance"},"destination":{"landmark_id":2}}
```

Manual coordinates without a supported route return 422 unavailable in the development adapter. Landmark IDs are resolved server-side so clients cannot pair fixture IDs with fabricated coordinates. Bounds/hours are enforced at quote time. Demo fixture 1↔2 is 6000m/720s and yields:

```json
{"id":"<quote-uuid>","pricing":{"version":1,"currency":"MWK","fare_minor":500000,"fee_minor":50000,"driver_minor":450000,"commission_bps":1000},"route":{"distance_m":6000,"duration_s":720,"provider":"development_fixture","label":"Demo route · not live road conditions"},"payment_methods":["cash"],"expires_at":"<five-minutes-from-now>"}
```

Actual response also includes normalized pickup/destination. These are illustrative distances and fares, not validated production routes/pricing.

## Book and recover

POST trips with Idempotency-Key: a UUID generated once per booking intent. Persist only that reference across network retries, reuse the same body and key, and ask for fresh intent when the user changes quote/note.

```json
{"quote_id":"<quote-uuid>","payment_method":"cash","pickup_note":"Beside the main gate"}
```

Response:

```json
{"id":"<trip-uuid>","state":"matching","payment_method":"cash","payment_status":"unpaid","fee_state":"none","driver":null,"pickup_pin":"<rider-only-four-digits>","pricing":{"version":1,"currency":"MWK","fare_minor":500000,"fee_minor":50000,"driver_minor":450000}}
```

It also contains route, locations and timestamps. Same key/same payload returns the original trip, including if the quote has since expired. Changed payload/key collision returns 409. A rider cannot have two active trips. Quote is single-use. On account recovery GET trips and find active state; do not create a new booking automatically.

Accept returns assigned; arrival driver_arrived; correct PIN in_progress; completion completed. Cash completion fee_state=captured/payment_status=cash_due_to_driver. It does not certify that cash changed hands. Cancellation pre-pickup releases reserved fee. Terminal trips do not return PIN. Incorrect PIN 422; fifth attempt and further attempts for five minutes 429. Start cannot bypass arrival. Driver cancellation after start 409: use interrupt.

## Errors and retry

```json
{"message":"Insufficient driver credit.","code":"invalid_request"}
```

Validation errors add `errors: {field: [message]}`. Common status: 401 sign in; 403 role/resource denied; 404 unavailable resource; 409 state/expiry/idempotency conflict; 419 CSRF mismatch; 422 unavailable route/invalid data/credit; 429 rate limited; 503 unavailable adapter. 5xx responses suppress internals. API responses are no-store. OTP max 3/phone per 10 minutes, 10/IP per 10 minutes; verify max 5/challenge plus IP limit. Booking 20/account/minute. Other authenticated mutations max 60/account/minute. Rate-limit retry-after detail is deferred; frontend keeps explicit retry controls.

On 419 refresh bootstrap once, then let the user retry; do not silently replay an unknown financial action with a new idempotency key. For network timeouts reconcile GET trips/status before retrying. Completion and cancellation are state-idempotent. Credit adjustments use caller reference, bound to driver/amount. No production API tokens in localStorage.

## Development payment callback simulator

Mobile-money booking remains disabled. Internally prepared digital trips can receive fake events at dev/payment-events; cash trips reject them. Configure DEV_PAYMENT_WEBHOOK_SECRET with a random >=32-character local value in backend/.env; never commit or log it. Compute X-Development-Signature as lowercase hexadecimal HMAC-SHA256 of **exact raw JSON bytes** using that secret.

```json
{"event_id":"fake-provider-event-001","trip_id":"<internal-digital-trip-uuid>","sequence":1,"status":"paid","amount_minor":500000,"currency":"MWK"}
```

Statuses pending/paid/failed/refunded, integer monotonic sequence. Signature checked before effects; amount/currency bound to quote; immutable event_id inbox deduplicates and rejects changed payload reuse. Older sequence is recorded but ignored. Paid/refunded do not regress to pending/failed. Response explicitly development=true and explains no money moved. Trip payment status prefixes `development_`; callbacks never complete a journey, deduct credit, create commission or initiate settlement. Duplicate/out-of-order events are tested against PostgreSQL. Live integrations need their own provider signature/ordering rules and reconciliation; this is not a live webhook endpoint.