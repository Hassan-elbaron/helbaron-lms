# Commerce QA Report — HElbaron LMS

**Date:** 2026-07-15
**Method:** Real browser session (Claude-in-Chrome) signed in as the seeded learner **Ahmed Kamal (`student01@demo.helbaron.local`, role: student)**. Cart/coupon/checkout/payment flows were exercised end-to-end against the running local stack through the same-origin BFF (`/api/backend/*` → API `/api/v1/*`), i.e. real authenticated requests from the browser, plus the rendered commerce UI. Payments use the built-in **Fake gateway** (`COMMERCE_PAYMENT_PROVIDER=fake`); no real processor was contacted.

**Test product:** "Demo Negotiation Mastery" (169.00 SAR). **Currency:** SAR (amounts stored in minor units). **Coupons:** seeded `DEMO25` (25%), `DEMOSAVE` (50.00 SAR fixed).

## Results — verified PASS

| # | Scenario | Result | Evidence |
|---|---|---|---|
| 1 | Add course to cart | ✅ | `POST cart {product}` → 200, items=1, subtotal=16900 (169.00). |
| 2 | Duplicate item prevention | ✅ | Adding the same product again → 200, items still **1** (no duplicate line, no qty inflation). |
| 3 | Cart read / persistence after reload | ✅ | Cart is **server-side** (per-user), so `GET cart` returns the same cart across reloads; verified items persist. |
| 4 | Remove item | ✅ | `DELETE cart/items/{product}` → 200, items=0. |
| 5 | Clear cart | ✅ | `DELETE cart` → 200, "Cart cleared." |
| 6 | Valid coupon (percentage) | ✅ | `DEMO25` → discount 4225 (42.25 = 25% of 169.00), total 12675 (126.75). |
| 7 | Valid coupon (fixed) | ✅ | `DEMOSAVE` → discount 5000 (50.00), total 11900 (119.00). |
| 8 | Invalid coupon | ✅ | Bogus code → **422 `COMMERCE_COUPON_INVALID` "The coupon is invalid."** (no discount applied). |
| 9 | Price recalculation | ✅ | Subtotal/discount/total recompute correctly for both coupon types. |
| 10 | Currency | ✅ | Cart + order in **SAR**; consistent across cart, order, invoice. |
| 11 | Checkout | ✅ | `POST checkout` → **201**, returns `{order, contract_id, payment}`. |
| 12 | Order creation | ✅ | Order created with status **pending**, total matches cart (11900). |
| 13 | Invoice creation | ✅ | Order carries an invoice (`hasInvoice: true`); invoice status transitions to **paid** on payment (below). |
| 14 | Contract creation | ✅ | Checkout creates a **pending** contract linked to the order. |
| 15 | Contract acceptance | ✅ | `POST contracts/{id}/accept` → 200, status → **accepted**, `accepted_at` timestamp set. |
| 16 | Fake payment success | ✅ | `POST payment/webhook {type:"payment.succeeded", order_reference}` → 200 "ok"; order → **paid** (`paid_at` set), **invoice → paid**. |
| 17 | Fake payment failure | ✅ | Fresh order + `payment.failed` webhook → order → **failed**. |
| 18 | Orders history | ✅ | `GET orders` returns the learner's orders across states: **pending → paid**, plus seeded **paid** and **cancelled**, and the test **failed** order. UI `/orders` page renders ("Your orders — purchase history and invoices"). |
| 19 | Contracts history | ✅ | `GET contracts` returns the learner's contracts with status. |
| 20 | Order states coverage | ✅ | Observed **pending, paid, failed, cancelled** across seeded + test orders. |

## Findings / capabilities not exposed (honest, code-confirmed)

These are **not defects per se** — they are scope observations. Where the brief asked for a behaviour that the product does not surface, that is recorded here rather than marked pass.

- **Contract rejection:** only `POST contracts/{id}/accept` exists — there is **no reject endpoint**. A contract is either accepted or left pending; explicit learner-initiated rejection is not implemented. *Recommendation: add a reject/decline action if the PRD requires it, else document accept-or-abandon as intended.*
- **Invoice download:** invoices are created and linked to orders, but there is **no invoice-download route** in `commerce.php` (no PDF endpoint). The learner can see invoice status but cannot download a document via the API. *Recommendation: add an invoice PDF/download endpoint if required.*
- **Refunds:** `RefundOrderAction` + `FakeGateway::refund()` exist, but there is **no learner-facing or public refund route**. Refunds are internal/admin-only (event-driven). *Recommendation: confirm refunds are admin-only by design; there is no learner "request refund" flow.*
- **Tax / VAT:** the cart/order money model is `subtotal → discount → total` only — there is **no tax or VAT line** in the cart response. VAT is not itemised. *Recommendation: if KSA/UAE VAT must appear on invoices, add a tax component to the pricing model + invoice.*
- **Expired / usage-limited coupon:** the seeder creates only non-expired coupons (`ends_at` = +3 months) and does not seed an exhausted-redemption coupon, so those specific rejection states could not be exercised against seeded data. The generic invalid-coupon rejection (422) **is** verified, and the coupon model has `ends_at`, `max_redemptions`, and `is_active` fields plus a `coupon_redemptions` table, so the enforcement paths exist in code. *Recommendation: seed one expired and one exhausted coupon to make these testable, and add feature tests.*
- **Payment retry:** there is no "retry payment on the same order" flow; after a failed order the learner re-checks-out (creating a new order), which was verified to work. *Recommendation: confirm this matches intended UX.*

## Security / negative paths (code-confirmed)

- **Unauthorized checkout / cart:** `cart`, `checkout`, `orders`, `contracts` all sit inside the `auth:sanctum` middleware group in `commerce.php`, so an unauthenticated request returns **401** (the whole group is gated). The public routes are only `GET products` and `POST payment/webhook`.
- **Expired session during checkout:** a 401 from the BFF causes the auth-context to clear the session marker and redirect to `/login` (verified in the earlier auth work), so an expired session mid-checkout degrades safely rather than trapping the user.
- **Checkout throttle:** `POST checkout` is rate-limited (`throttle:commerce-checkout`), protecting against rapid repeat submissions.
- **Webhook signature + idempotency:** the Fake gateway verifies an HMAC signature when one is present (`fake-signature=HMAC_SHA256(payload, whsec_fake)`), and `ProcessWebhookAction` **dedupes by `event_id`** via a unique `PaymentWebhookEvent` record, so webhook replays are a no-op. (Note: when **no** signature header is sent the Fake gateway skips verification — acceptable for the local/test `fake` provider; the real `StripeGateway` path enforces its own signature.)

## Checkout result pages
`/checkout`, `/checkout/success`, `/checkout/failed`, `/cart`, `/orders`, `/contracts` all exist as routes. `/orders` was rendered in-browser and shows the account orders view. (These pages are auth-gated and fetch client-side; on the slow dev backend they show a brief "Loading…" state before data renders.)

## Residual test data (restore instructions)
This QA created test orders/contracts for `student01@demo.helbaron.local`: one **paid** order (DEMOSAVE, 119.00), one **failed** order, an **accepted** contract, and associated invoices/transactions; the learner's cart was left empty. Restore the demo DB to its seeded state on the host:
```
docker compose exec api php artisan migrate:fresh --seed
```
