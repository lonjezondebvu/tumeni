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

Known UI limitations: browser surface unavailable in this run, so visual layout and interaction QA were not performed; ops/reviewer API restrictions are enforced but admin navigation is not comprehensively permission-filtered; account role is singular; no live geocoding/map pin, mobile push or fleet tracking. Frontend currently has no separate automated browser suite. README HTTP demo verifies actual server contracts, not the rendered UI.
Language support added: Chichewa default, persistent English/Chichewa header switch, shared translations for all three workspaces, document language/title and accessible/status/error labels. See LANGUAGES.md for implementation and validation.

Pickup and destination now use SearchablePlaceSelect with landmark names, aliases and meeting-point search. Options show meeting points and verification; Arrow keys, Home/End, Enter, Escape and Tab control the list. Typing clears the selected identifier until a configured option is selected. Unknown text cannot be submitted as a landmark; pickup is excluded from destination choices, and manual pickup remains available. Search has localized no-match feedback. No geocoding or API change. Run npm.cmd run test:places (5 filtering tests) and npm.cmd run test:language (8 locale tests) from frontend; rendered keyboard/touch interaction remains unverified.

Translation maintenance now uses plain UTF-8 JSON files under frontend/src/i18n/locales with a disabled _template.json. Enabled files are discovered by the PowerShell generator before dev/build. More than two languages changes the header to a chooser; English/Chichewa retain their button. Missing/blank messages use English. See LANGUAGES.md for the copy/edit/build workflow and validation commands.

Structured driver onboarding and self top-ups now use DriverSetup.tsx; DriverReview.tsx provides reviewer-only full profile/document/checklist decisions. Account and driver pages show persisted drafts, required/conditional upload slots, locked submitted status and review reasons. Documents are encrypted/private and do not use public asset URLs. PayChangu checkout is external; returns never imply payment success, and Check payment verifies server-side. Starter credit is displayed after one-time full approval. See DRIVER_ONBOARDING_PAYMENTS.md for configuration, test/live boundaries and verification evidence.

## Permanent design standard

[DESIGN_PHILOSOPHY.md](DESIGN_PHILOSOPHY.md) is the latest user-supplied governing philosophy: **Quiet confidence. Immediate clarity. Effortless action.** [DESIGN_STANDARD.md](DESIGN_STANDARD.md) supplies the detailed frontend engineering guidance. frontend/AGENTS.md directs contributors to read both for every existing and future page. Preserve the forest-green/off-white identity and current business behaviour; inspect each workflow, choose its primary task, reuse shared tokens/components, and report actual verification.

Shared visual tokens live in frontend/src/tokens.css; style.css consumes them for core palette, typography, control/panel radii, touch targets, layout widths, focus and overlay layers. Responsive thresholds are 700/1000px in style.css because custom properties cannot be used in media-query conditions. Existing semantic HTML, Button and Radix Modal remain the component foundation; no new UI library was introduced.

The first foundation pass adds a translated skip link, current-navigation semantics and an announced connecting state; enlarges secondary touch targets; improves supporting-text and input-border contrast; gives the page mobile bottom-navigation clearance with safe-area support; and ensures modal layers cover open dropdowns. Standard mobile text inputs use 16px to reduce browser zoom on focus. Existing routes and business behaviour remain intact. This is a shared foundation, not a completed page-by-page accessibility audit. Browser, screen-reader, touch and native-speaker review remain required.
## Philosophy applied to existing pages (2026-10-13)

Booking now leads with pickup/destination and See your fare; the optional pickup note is an expandable section that retains its controlled value. Quote confirmation uses Review your ride with the existing pickup, destination, full fare, cash explanation and request action. Account/history/status screens share the compact heading hierarchy. No route, money or booking-state contracts changed.

The driver page puts its active ride or application before earnings/credit in document and keyboard order. Pending/rejected/suspended drivers without an active ride see verification as their main task; approved drivers retain application access in an expandable sidebar section. Go online is disabled when the displayed driver or vehicle approval is missing; Go offline and active trip recovery remain available. Server eligibility/expiry checks remain authoritative. Onboarding uses numbered form sections and an uploaded-required-document count. Top-ups keep server verification and show loading/empty states; shared Button supports accessible busy actions.

Operations uses full-width driver review sections rather than expanding multi-document forms inside a table cell. Application details and documents are grouped; failed initial review loads have an explicit retry. Audit reasons remain visible for write workflows and are hidden on read-only trips/audit views. Existing role enforcement remains on the server; full permission-filtered navigation is still pending.

style.css is consolidated into readable token-based sections for controls, shell, rider, map, overlays, driver and operations, with mobile/tablet adaptation. Forest green, warm white, Lucide and Radix remain. Current breakpoints are 700 and 1000px; narrower layouts stack tasks ahead of supporting content and keep safe-area clearance for bottom navigation. Actual browser overflow/layout/touch/screen-reader review remains pending.

Checks: npm.cmd run test:design renders React HTML with the existing Vite/React dependencies (7 tests). It checks bilingual labelled booking, optional notes, busy-button semantics, retained map controls, rider PIN visibility, driver task ordering/eligibility and full-width operations review. It does not simulate clicks, keyboard events, layout or network/financial effects. Existing language/file/place tests also passed (17 Node tests plus 4 generator rejection checks), and TypeScript/Vite build passed. No new dependency or backend change. Editable catalogs now contain 446 source messages; native-speaker review remains required.