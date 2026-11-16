# Phase 22 — Marketplace & Messaging Hardening

Status: **implemented** · Type: hardening / closure (no new product features)

Phase 22 closes the highest‑value deferred items from Phases 20–21 and records
the phase on disk so it no longer lives only in conversation history.

## Objectives

1. Close deferred marketplace hardening from Phases 20–21 **only where justified**.
2. Add a genuine no‑JS / mobile fallback for the filter drawer.
3. Verify and lock in the existing messaging polling path (no WebSockets).
4. Correct stale documentation to match the implementation.
5. Preserve every Phase 19–21 contract and behaviour.

## Scope (what was done)

### A1 — Named `marketplace` rate limiter
`GET /api/marketplace/workers` moved from the inline `throttle:60,1` to a named
`throttle:marketplace` limiter (`AppServiceProvider::configureRateLimiters`),
consistent with the other seven named limiters. **Same 60/min ceiling**, keyed
per‑user when authenticated and per‑IP otherwise. Regression test added for the
429 path (`Retry-After` header on the 61st request).

### A2 — `/services` SEO / pagination
`/services` now emits the same clean `<link rel="canonical">` + `rel="prev"` /
`rel="next"` as `/professionals`. The URL‑assembly logic that was a private
method on `WorkerController` is now the shared `App\Marketplace\PaginationSeo`
helper used by both controllers (one implementation, not a copy). `/services`
canonical carries only the params that page honours — `q`, `category`,
`min_price`, `max_price`, non‑default `sort` — and **never** `min_rating`
(unsupported there) or tracking params (`utm_source`, `gclid`, …). `page=1`
canonicalises to the bare filtered path; the clamped page is used for over‑range
requests.

### A3 — URL normalisation edge cases
Reviewed the four Phase 20/21 items; **one** fixed, three accepted:

| Item | Decision |
|---|---|
| `?min_price=0` lingering in the URL | **Fixed** — a minimum price of 0 (or negative) is "no lower bound" and is dropped in `DiscoveryFilters`, so it never enters the URL / canonical. `max_price=0` ("free only") is still kept. |
| Duplicate scalar params on `/explore` | **Accepted** — standard PHP `$request->query()` behaviour; no legitimate client sends duplicate scalars; fixing it means re‑parsing the raw query string for no real benefit. |
| Internal XHR sends `sort=recommended` | **Accepted** — internal fetch URL only, not user‑visible, not crawled; the server coerces it to the default and returns an identical result. Purely cosmetic. |
| Blade paginator body links keep tracking params | **Accepted** — the authoritative pagination signals are the clean `<head>` `<link rel>` tags (Phase 20 / A2). Stripping params from `previousPageUrl()`/`nextPageUrl()` would touch the shared vendor paginator and the protected `MarketplaceQuery::paginate` output. |

### A4 — Price display
`49.90 → 49.9` is **accepted, not a bug**: the values are mathematically
identical, no product requirement mandates fixed 2‑decimal display, and the
decimal normalisation contract is unchanged. `49.99` is preserved exactly.

### B — No‑JS / mobile filter drawer
`resources/js/app.js` adds `html.js`; `resources/css/app.css` adds a
`html:not(.js) [data-filter-panel]` rule that returns the mobile filter panel to
normal document flow (static, full‑width, no transform) and hides the
Alpine‑only open/close buttons. **JS‑on behaviour is byte‑for‑byte unchanged**
(the rule never matches once `app.js` runs); `lg+` is unaffected (already a
static sidebar). EN/AR, RTL and the always‑server‑rendered filter fields are
untouched.

### C — Messaging / chat polling
No code change. Verified: `chatPoll` is registered before the single
`Alpine.start()` (the Phase 21 fix), uses the existing `?after=${lastId}` delta
endpoint, keeps the 12s interval, guards against duplicate loops
(`if (!this.timer)`), and errors are caught. Added `AlpineBootstrapTest` to lock
the factory‑before‑`start()` ordering permanently, plus a static check of the
`?after=` / single‑interval / 12s contract.

### D — Documentation
`docs/FEATURE_MATRIX.md`: corrected Favorites (was "Schema" — it is fully
implemented), Service discovery, and Messaging rows to match reality. This file
created.

## Explicit exclusions (unchanged / not touched)

Real payment provider adapters · WHISH / payment‑method changes · payment
capture / commission / refund / `Money` / reconciliation changes · invoice PDF ·
quotation expiry/versioning/comparison · WebSockets · announcements ·
review‑category system · complaint‑system expansion · admin analytics expansion ·
new product features · large React refactors · architectural rewrites · any
database migration or schema change.

## Protected areas (frozen, confirmed untouched)

`app/Payments/*`, `PaymentCaptureService`, `CommissionService`, `Money`,
`RefundService`, `ReconciliationService`, WHISH / payment‑method handling,
payment statuses / calculations, `app/Services/RequestWorkflow.php`, existing
payment controllers, quotation/payment financial calculations, the confirm‑dialog
implementation and `confirmDialog` / `favoriteToggle` / `chatPoll` factories, the
`chatPoll`‑before‑`Alpine.start()` ordering, React/Blade coexistence, the
`/professionals` canonical route, `/explore` 301, the
`DiscoveryFilters → MarketplaceQuery → MarketplaceCard/Props` contract, and all
authorization / RBAC / security controls.

## Acceptance criteria

- [x] Marketplace hardening implemented only where justified (A1 done, A2 done, A3 one fix + three documented, A4 accepted).
- [x] `/services` SEO/pagination reviewed and corrected (canonical + rel=prev/next via shared `PaginationSeo`).
- [x] Marketplace URL edge cases each fixed or explicitly accepted/documented.
- [x] No‑JS mobile filters work (CSS fallback; JS‑on unchanged).
- [x] Alpine confirmation behaviour intact (no change; `AlpineBootstrapTest` guards it).
- [x] Favorite behaviour intact (no change).
- [x] Chat polling intact (no change; regression test added).
- [x] Chat polling has automated coverage (`MessagingPollingTest` + new `AlpineBootstrapTest`).
- [x] Live two‑session polling limitation documented (below).
- [x] `FEATURE_MATRIX.md` reflects actual state.
- [x] `docs/PHASE_22.md` exists.
- [x] No unrelated refactors; no financial/payment changes; no destructive DB op.
- [x] Full automated suite green; Payments 183/183; Vitest ≥ 32; build clean.
- [x] The four pre‑existing unrelated Pint failures untouched.

## Test requirements / coverage added

- `MarketplaceApiTest::test_the_named_marketplace_limiter_blocks_the_61st_request_in_a_minute` (429 path).
- `MarketplaceContractTest::test_the_page_normalises_out_of_range_price_exactly_like_the_api` (updated for A3) and `…::test_min_price_zero_is_treated_as_no_lower_bound_and_never_enters_the_url` (new).
- `MarketplaceApiTest::test_a_negative_or_zero_min_price_is_dropped_not_a_422` (updated for A3).
- `ServiceSeoTest` (new) — canonical / prev / next on `/services`, rating + tracking params excluded, single canonical.
- `FilterDrawerTest` (new) — no‑JS hooks present, Alpine slide‑over preserved, `app.js`/`app.css` wiring.
- `AlpineBootstrapTest` (new) — `Alpine.start()` once and last; `?after=` / single‑interval / 12s poll contract.

## Known limitations

1. **Live two‑browser‑session chat polling** is still a manual‑QA item — a
   second concurrent session was not available. The `?after=` endpoint and the
   `chatPoll` factory are verified in isolation; the end‑to‑end "message appears
   in the other tab within 12s" check has not been performed and is not claimed.
2. **Browser rendering of the no‑JS filter fallback** at 320 / 375 / 390 px in
   EN and AR/RTL has not been visually verified (no browser automation in this
   environment). The fallback is a plain in‑flow block, but pixel‑level polish
   at each breakpoint remains a human browser‑QA check.
3. Accepted A3 cosmetics (duplicate `/explore` scalars, internal XHR
   `sort=recommended`, paginator body‑link tracking params) and A4
   (`49.90 → 49.9`) are intentionally not changed.
4. The four pre‑existing Pint style failures in unrelated test files
   (`ComponentRenderTest`, `Marketplace/MarketplaceAuthorizationTest`,
   `Worker/ServiceCatalogTest`, `Worker/WorkerSecurityTest`) remain out of scope.
