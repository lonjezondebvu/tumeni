# Money, driver credit and settlement

All monetary values are integers in minor units with currency MWK. **100 minor units = MWK 1**. Demo: fare 500000, fee 50000, driver amount 450000. UI divides for formatting only; business arithmetic uses PHP integers/intdiv. No independent token conversion.

Pricing versions are append-only. Fare = base_minor + ceiling(per_km_minor * declared road_distance_m / 1000). Fee = floor(fare * commission_bps / 10000). No surge. Quote and accepted trip save full version/currency/fare/fee/net snapshot. Prices cannot be supplied by clients or silently changed mid-trip. Quote expires after five minutes; matching offer after ten.

Available credit is SUM(credit_ledger.amount_minor) by driver, under the driver row lock for credit-affecting actions. No unexplained mutable balance column.

| Event | Available credit change | Revenue/other record |
|---|---:|---|
| Development top-up/manual funding | positive | Credit liability, not earned revenue |
| Cash acceptance reservation | negative disclosed fee | Unique fee_reservations reserved; no commission earned yet |
| Cash completion capture | zero | Reservation captured, unique commission earned, driver cash-retained settlement |
| Genuine pre-pickup cancellation | positive reserved fee | Reservation released; no commission |
| Interrupted trip in this milestone | positive reserved fee | No earned commission; incident due in 48 hours |
| Reversal/manual correction | signed new entry | Required reason, unique reference and audit record |

Capture has zero ledger amount because reserve already reduced spendable credit. Reserve/capture/release entries preserve the chain of events; do not subtract again at completion. A second completion or cancellation does not add another entry. Driver credit may never be reduced below zero by a manual adjustment or acceptance. Captured commission never counts the same fee twice. Do not sum top-ups as revenue.

Cash receipt status is `cash_due_to_driver`: completion does not prove physical cash collection. Driver earns fare minus fee before operating costs. Today earnings use completed trips in the Africa/Blantyre day. Cash settlement is a record that funds are retained directly by the driver, not a payout instruction.

Separate tables: trips (fare snapshot, payment status), credit_ledger (MWK credit movements), fee_reservations, commissions (MWK earned fee), settlements (driver amount; currency inherited from unique trip snapshot). `trip_events` and `audit_records` show who acted and why. Reservation transition and trip completion are one database transaction. Future payouts require their own idempotent records; never reinterpret credit entries as provider payments.

Digital payments are **disabled in public booking**. A tested internal branch accounts for digital commission through settlement without reserving or deducting credit. It marks provider settlement pending and never reports a successful payment. This branch is architecture preparation, not a working mobile-money integration. DevelopmentPayment/PaymentEvents provide an explicitly fake, production-disabled HMAC-signed event simulator with immutable inbox, duplicate binding and sequence/terminal-state ordering. It accepts only internal digital fixtures through the admin-only dev endpoint, marks statuses development_* and never moves money, completes trips or deducts credit.

Before enabling digital: implement a live PaymentProvider against the actual provider signature and event-ordering contract; extend the tested development inbox to namespaced provider/event references and provider reconciliation; add refund/reversal records, settlement and payout idempotency. Test each with the real database and provider sandbox. Do not enable a payment option because a fake adapter can return success.
PayChangu driver self-top-ups and one-time starter credit are implemented; see DRIVER_ONBOARDING_PAYMENTS.md. Confirmed starter credit: MWK 4500, configurable DRIVER_TRIAL_CREDIT_MINOR=450000, granted once at first full approval. Verified top-ups append topup ledger entries only after exact reference/amount/currency/mode checks. These credits are not earned platform commission. Public rider payment stays cash. Real sandbox checkout created successfully; settlement/payment completion and production activation are not claimed. Refund/chargeback reversals require operator investigation and explicit audited ledger adjustments.
