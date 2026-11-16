# SALE7LI — Bilingual Terminology Reference

This is the single source of truth for EN ⇄ AR wording. Every translation file
under `lang/en` and `lang/ar` follows these choices. When adding new strings,
reuse these terms exactly — do not introduce a synonym on one screen.

## Core marketplace nouns

| English | Arabic | Where it is used |
| --- | --- | --- |
| Service | الخدمة | A category a customer can book (Electrical, Plumbing…) |
| Services | الخدمات | The marketplace listing / nav item |
| Service request | طلب خدمة | A booking a customer submits |
| Request | الطلب | Short form of "service request" inside a customer's own context |
| Job | المهمة | The **same** request seen from the professional's side |
| Professional | مقدّم الخدمة | The worker who performs the service (never "عامل") |
| Customer | العميل | The person booking the service |
| Admin / Operations | الإدارة / العمليات | Platform staff; the admin portal is "مركز العمليات" → shortened to "العمليات" |
| Quotation | عرض سعر | The price the professional proposes |
| Appointment | الموعد | The scheduled date/time for a job |
| Payment | الدفع | Paying for a completed/approved job |
| Invoice | الفاتورة | The record generated after payment |
| Review | التقييم | The customer's rating + comment after completion |
| Rating | التقييم | The numeric star value (same Arabic word, context disambiguates) |
| Complaint | الشكوى | A reported issue (schema only in Phase 4) |
| Availability | التوفر | A professional's working hours (schema only in Phase 4) |
| Verification | التوثيق | The admin check that lets a professional take jobs |
| Address | العنوان | A saved customer location |
| Notification | الإشعار | An in-app update |
| Message | الرسالة | A chat message between customer and professional |
| Marketplace | السوق | The public service-discovery area |

## Status vocabulary (`lang/*/status.php`)

### Request / job

| Value | English | Arabic |
| --- | --- | --- |
| `pending` | Pending | قيد الانتظار |
| `awaiting_acceptance` | Awaiting acceptance | بانتظار القبول |
| `accepted` | Accepted | مقبول |
| `scheduled` | Scheduled | مجدول |
| `on_the_way` | On the way | في الطريق |
| `in_progress` | In progress | قيد التنفيذ |
| `completed` | Completed | مكتمل |
| `cancelled` | Cancelled | ملغى |
| `rejected` | Rejected | مرفوض |

### Quotation

| Value | English | Arabic |
| --- | --- | --- |
| `pending` | Awaiting response | بانتظار الرد |
| `accepted` | Accepted | مقبول |
| `rejected` | Rejected | مرفوض |
| `expired` | Expired | منتهٍ |

### Payment

| Value | English | Arabic |
| --- | --- | --- |
| `pending` | Awaiting payment | بانتظار الدفع |
| `paid` | Paid | مدفوع |
| `failed` | Failed | فشل |
| `refunded` | Refunded | مُسترد |
| `partially_refunded` | Partially refunded | مُسترد جزئيًا |

### Verification

| Value | English | Arabic |
| --- | --- | --- |
| `pending` | Pending review | قيد المراجعة |
| `verified` | Verified | موثّق |
| `rejected` | Rejected | مرفوض |
| `suspended` | Suspended | موقوف |

## Common actions

| English | Arabic |
| --- | --- |
| Book a service | احجز خدمة |
| Submit service request | إرسال طلب الخدمة |
| Accept / Decline (job) | قبول / رفض |
| Accept / Reject (quotation) | قبول / رفض |
| Send quotation | إرسال العرض |
| Save status update | حفظ تحديث الحالة |
| Cancel request | إلغاء الطلب |
| Pay :amount | ادفع :amount |
| Submit review | إرسال التقييم |
| Mark all as read | تعليم الكل كمقروء |
| Log in / Create account / Log out | تسجيل الدخول / إنشاء حساب / تسجيل الخروج |
| Switch language | تغيير اللغة |

## Notes on register and tone

- Arabic copy is written in a professional, conversational register — sentence
  style, no marketing hyperbole.
- "مقدّم الخدمة" is always used for the worker role. "عامل" / "محترف" were used
  inconsistently in earlier phases and have been normalized out.
- The admin portal's on-screen name is **العمليات** (Operations); the older
  literal "لوحة المدير" is not used.
- Numbers, prices and dates render with the active locale via
  `Carbon::translatedFormat()` and `number_format()`. Currency stays `$` in both
  locales for Phase 4 (no locale-specific currency formatting yet).
