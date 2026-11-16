# SALE7LI Feature Matrix — Build Plan

| Area | Current foundation | Next hardening/extension |
|---|---|---|
| Public homepage | Yes | Replace demo visual blocks with final branded imagery |
| Bilingual EN/AR + RTL | Yes | Translate remaining UI copy completely |
| Authentication | Yes | Password reset + email verification |
| Customer dashboard | Yes | Spending charts + notification widgets |
| Service discovery | Yes — search, category, sort, min-rating, price range, pagination (React on `/professionals` with a full Blade/no-JS fallback); canonical + rel=prev/next on `/professionals` and `/services` | Worker-availability ranking (sort by availability) |
| Booking | Yes, core flow | Persist wizard state + live availability checks |
| Addresses | Yes | Edit/default address UX |
| Request tracking | Yes | Activity event log UI + richer map/location support |
| Worker dashboard | Yes | Calendar + availability editor |
| Worker jobs | Yes | Job discovery algorithm + service area matching |
| Quotations | Yes | Expiry, versioning, customer comparison |
| Mock payments | Yes | Real provider adapter behind a payment service interface |
| Invoices | Yes | Printable/PDF renderer |
| Messaging | Yes — thread pagination, `?after=` delta polling (12s, pauses on hidden tab), React `AttachmentInput` island | Attachment gallery view; WebSockets (out of scope) |
| Notifications | Yes | Event/listener delivery + preferences |
| Reviews | Yes | Category ratings + moderation workflow |
| Favorites | Yes — `FavoriteController` toggle + index, React `FavoriteButton` on `/professionals`, Alpine `favoriteToggle` on the profile page, per-customer isolation | Bulk manage / notes |
| Complaints | Schema | Customer/worker/admin workflows |
| Announcements | Schema | Admin composer + targeted delivery |
| Admin analytics | Basic KPIs | Charts, filters, date ranges, export |
| Security | Core RBAC + ownership checks | Rate limits, upload hardening, audit review |
| Testing | Auth tests scaffold | Full workflow/authorization/browser coverage |
