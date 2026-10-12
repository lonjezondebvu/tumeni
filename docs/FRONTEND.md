# Frontend handoff

React 19/TypeScript, Vite 8, Tailwind 4, Lucide icons, Radix dialog with locally owned shadcn-style primitives. Main entry main.tsx; Rider.tsx for booking/OTP/status/history/help/account; Consoles.tsx for driver; Admin.tsx for operations; api.ts for transport and contracts; Map.tsx for explicitly illustrative SVG; style.css for responsive design. Build: npm.cmd run build in frontend. Output ignored backend/public/app.

| Screen | APIs and permissions | Failure/empty states |
|---|---|---|
| Destination/pickup | bootstrap/quotes, public | service/bounds/hours or route unavailable; retry; location denied → manual coordinates |
| Quote | cash fare snapshot, public | stale quote returns conflict on request; go back for new price |
| Signup | OTP/verify, development only | expired/incorrect/rate-limited code; change number/request code |
| Matching | own active trip, rider | no fake cars/ETA; expiry/cancellation redirects to history with message |
| Assigned/arrived/in progress | own trip polling | known driver/vehicle, rider-only PIN, help; no cancel after start |
| Trips/receipt | own history | signed-out signin, no trips empty state, total fare/payment explanation |
| Account/apply | own session/application | first name only; saved application pending human review |
| Driver home/offer | driver/online/offers | pending eligibility, insufficient credit, no offers, expired/conflicting offer |
| Driver lifecycle | assigned trip actions | required arrival/PIN, lockout, invalid transitions, reason for interruption |
| Driver credit | own ledger/earnings/reviews | append-only transactions; review due dates |
| Admin | role-bound APIs | required reasons; document review; server rejects unauthorized roles |

Customers see one primary action and plain language. Development workspace is separate and explicitly labelled. Payment choice stays cash; no fake mobile-money option. Demo pickup/destination names/coordinates are seeded; service name/bounds/hours and landmark directory are configurable. All map geometry is illustrative, not navigable or derived from actual roads. Manual coordinates are accepted for quote input but fixture routing cannot serve them; no map-pin geographic selection is implemented. Do not claim geocoding/live map completeness.

Modal is focus-managed, labelled, supports Escape and returns focus via Radix. Forms have associated labels, HTML constraints, server validation, focus outlines and disabled pending actions. Bottom navigation on mobile, large booking/ride buttons, off-white and deep green. Keyboard/screen-reader/contrast device audit still required; compile success is not visual verification. Some secondary controls are smaller than primary buttons and should be checked against target accessibility criteria.

Transport uses same-origin credentials, in-memory CSRF and JSON error messages. Only booking retry key and pickup note go in sessionStorage; no auth bearer token storage. Bootstrap after session changes refreshes CSRF. Foreground polling every five seconds recovers active trips and receipt state; background document visibility pauses polls. Errors are dismissible with retry; no optimistic financial state. Backend owns prices and net earnings. The current poll implementation repeats bootstrap/history/role queries; reduce with dedicated incremental endpoints/outbox when scale requires.

Known UI limitations: browser surface unavailable in this run, so visual layout and interaction QA were not performed; private document upload currently one slot; application saves vehicle/details but does not rehydrate a full multistep form; ops/reviewer API restrictions are enforced but admin navigation is not comprehensively permission-filtered; account role is singular; no live geocoding/map pin, mobile push or fleet tracking. Frontend currently has no separate automated browser suite. README HTTP demo verifies actual server contracts, not the rendered UI.