# SALE7LI Architecture

## Roles
- Customer: discovers services, creates requests, selects address/time, tracks work, accepts quotations, pays in the demo flow, reviews professionals.
- Worker: maintains professional profile, receives/accepts assigned work, schedules jobs, updates the service state, submits quotations, records completion evidence, communicates with the customer.
- Admin: monitors KPIs, manages users, verifies workers, manages service categories, assigns requests.

## Request state machine
`pending -> accepted -> scheduled -> on_the_way -> in_progress -> completed`

Terminal states: `cancelled`, `rejected`.

The transition is enforced by `App\Services\RequestWorkflow`; controllers should not directly invent arbitrary status transitions.

## Authorization principle
Every customer/worker request action is checked against the authenticated user's relationship to the request. Admin actions are protected with the `role:admin` middleware. This is intended to prevent IDOR-style access through guessed IDs.

## Data model
```mermaid
erDiagram
  USERS ||--o| WORKER_PROFILES : has
  USERS ||--o{ ADDRESSES : owns
  USERS ||--o{ SERVICE_REQUESTS : creates
  USERS ||--o{ SERVICE_REQUESTS : receives
  SERVICE_CATEGORIES ||--o{ SERVICE_REQUESTS : classifies
  SERVICE_CATEGORIES ||--o{ WORKER_SERVICES : offers
  USERS ||--o{ WORKER_SERVICES : provides
  SERVICE_REQUESTS ||--o| APPOINTMENTS : schedules
  SERVICE_REQUESTS ||--o{ QUOTATIONS : receives
  SERVICE_REQUESTS ||--o| PAYMENTS : paid_by
  SERVICE_REQUESTS ||--o| INVOICES : billed_as
  SERVICE_REQUESTS ||--o| REVIEWS : reviewed_as
  SERVICE_REQUESTS ||--o{ COMPLAINTS : may_have
  SERVICE_REQUESTS ||--o| CONVERSATIONS : has
  CONVERSATIONS ||--o{ MESSAGES : contains
  USERS ||--o{ NOTIFICATIONS : receives
  USERS ||--o{ REVIEWS : writes
  USERS ||--o{ REVIEWS : receives
```

## Bilingual UI
Locale is stored on the user and mirrored into the session. The `SetLocale` web middleware sets the application locale on every request. All public service categories carry both English and Arabic labels/descriptions. The root layout switches `dir` between `ltr` and `rtl`.

## Payment boundary
The current controller deliberately implements a mock/test payment transaction. It creates a payment record and an invoice but does not collect or persist card numbers, CVV, expiry or other raw card data.
