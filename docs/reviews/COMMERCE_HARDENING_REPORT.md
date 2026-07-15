# Commerce Hardening Report — HElbaron LMS

**Date:** 2026-07-15
**Method:** Real authenticated learner session (`student01@demo.helbaron.local`) driving the live commerce API from the browser (same-origin BFF), focused on idempotency, race conditions, and edge cases beyond the functional Commerce QA. Fake payment gateway only.

## Verified — idempotency & race safety (the critical items)

| Scenario | Result | Evidence |
|---|---|---|
| **Webhook replay / duplicate webhook** | ✅ **Idempotent** | Fired `payment.succeeded` for an order, then **replayed the exact same `event_id`**. Both return **200**; the order settles to **paid once** with a single `paid_at` — the replay is a **no-op** (deduped via the unique `PaymentWebhookEvent.event_id`, per `ProcessWebhookAction`). No double-fulfillment, no error. |
| **Double-submit / concurrent checkout** | ✅ **Safe** | Two checkouts fired **concurrently** (`Promise.all`) on one cart: **A → 201 (one order)**, **B → 422 `COMMERCE_CART_EMPTY`**. The cart is consumed transactionally, so a duplicate/racing submit creates **no** second order and cannot double-charge; the cart is emptied (`items: 0`). |
| **Payment success → paid + invoice paid** | ✅ | (Commerce QA) webhook → order `paid`, invoice `paid`. |
| **Payment failure → failed** | ✅ | (Commerce QA) `payment.failed` webhook → order `failed`. |
| **Checkout throttle** | ✅ | `throttle:commerce-checkout` = 10/min (defined in `CommerceServiceProvider`), limiting rapid repeat submissions. |
| **Cart persistence / abandoned-cart recovery** | ✅ (by design) | The cart is **server-side, per-user**. It survives reload, tab close, and browser close, and is still present on the next login — so "recover abandoned cart" is inherent (verified persistence in Commerce QA). |
| **Invalid coupon rejection** | ✅ | Bogus code → 422 `COMMERCE_COUPON_INVALID` (Commerce QA). |
| **Webhook signature** | ✅ | Fake gateway validates an HMAC signature when present; the real `StripeGateway` enforces its own signature verification. |

## Edge cases — not fully testable against seeded data (honest)

- **Expired coupon / inactive coupon / usage-limit (max_redemptions) exhaustion:** the model supports all three (`coupons.ends_at`, `is_active`, `max_redemptions` + `coupon_redemptions` table), but the default seed creates only **active, non-expired** coupons (`DEMO25`, `DEMOSAVE`, `ends_at` +3 months) and no exhausted coupon — so these specific rejection states can't be exercised from seeded data. The generic invalid-coupon path (422) is verified. **Recommendation:** seed one expired + one inactive + one max-redemptions=1 coupon, and add backend feature tests for each rejection reason (the enforcement code exists in `ApplyCouponAction`).
- **Contract expiry:** contracts are **accept-or-pending** (`POST contracts/{id}/accept`); there is no time-based contract-expiry mechanism. If the PRD requires contracts to expire, that's a feature gap, not a defect — flag for product.
- **Payment retry:** no "retry the same order" flow; a failed order is followed by a fresh checkout (new order), which works. Confirm this matches intended UX.
- **Invoice regeneration / order recovery:** invoices are generated at checkout and settled by webhook; there is no learner-facing regenerate/recover endpoint (admin/internal only). Consistent with the earlier scope notes (no invoice-download/refund routes for learners).

## Client-resilience items (architecture-level, not separately scriptable)

- **Refresh during checkout / back button / browser close / unexpected refresh:** checkout is a single idempotent-at-the-cart POST; because the cart is server-side and a second submit returns `CART_EMPTY`, a mid-checkout refresh or back-navigation cannot create a duplicate order or lose the cart. React Query manages in-flight state on the client.
- **Slow API / offline recovery:** the BFF + React Query surface loading/error states; an offline submit fails and can be retried when back online (the cart is intact). A dedicated offline-queue is not implemented (not expected for this flow).
- **Multiple tabs:** the cart is shared server-side, so tabs observe the same cart on refetch; there is no cross-tab live sync (acceptable — refetch reconciles).

## Residual test data
This hardening created a few extra learner orders/transactions (paid + the concurrent-checkout order) for `student01`. Cleared by the host reseed:
```
docker compose exec api php artisan migrate:fresh --seed
```

## Net result
The two highest-risk commerce concerns — **duplicate/replayed payment webhooks** and **double-submit/concurrent checkout** — are **verified safe and idempotent** (single settlement, single order, no double-charge). Coupon-rejection sub-states and a few UX flows (payment retry, contract expiry) are documented as needing seeded fixtures / product decisions rather than being defects.
