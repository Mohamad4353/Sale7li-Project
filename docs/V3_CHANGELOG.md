# SALE7LI V3 — Engineering Changelog

## Baseline
V3 is based on the uploaded SALE7LI V2 bilingual project. The V2 source remains untouched; V3 is a separate build artifact.

## Fixed in V3
- Rebuilt the customer request detail Blade view to remove the fragile one-line conditional structure that caused the `unexpected token "endif"` parse error.
- Added strict phone-number validation to registration and professional-profile updates. Letters are rejected at both HTML and server-validation levels.
- Added password visibility controls to login and registration forms.
- Made the support email a real `mailto:` link.
- Fixed `WorkerProfile` localized bio accessor naming so Laravel can resolve `localized_bio` correctly.
- Added missing `completion_notes` and `completion_images` fields to `ServiceRequest` mass assignment/casts.
- Hardened worker job status transitions so invalid jumps return a validation error instead of triggering a workflow exception/500.
- Added final-total capture and appointment completion handling when a job is completed.
- Added worker qualification checks to admin assignment: active + verified + matching service category.
- Added notifications and activity logging when an admin assigns a professional.
- Added collision-safe request reference generation.
- Improved bilingual presentation for key authentication, customer request, worker job, and worker profile screens.
- Added regression coverage for valid and invalid phone registration.

## Important testing note
The development archive does not include `vendor/`, so this environment cannot execute Laravel's PHPUnit suite without installing Composer dependencies. PHP syntax linting passes across the application source.

## Fixed in this pass (post-V3 audit)
This pass re-audited the "V3" build against the original QA brief and found the reported production blocker was still present, plus several other real issues:

- **Root cause of `BadMethodCallException: Call to undefined method App\Models\User::workerServices()`**: `App\Models\User` never defined this relationship even though `Admin\RequestController::assign()` and `services/show.blade.php` both call it. Added `User::workerServices(): HasMany` (worker_id → `worker_services`). This was the exact blocking exception named in the QA brief; the fix targets the underlying model relationship rather than catching/hiding the exception.
- **App-wide missing CSS**: `.card`, `.input`, and `.badge` were referenced by nearly every view (every form, every dashboard card, every status pill — dozens of usages) but were never defined in `resources/css/app.css`. This meant, once assets were actually compiled, forms and cards throughout the entire app would render unstyled. Added proper component definitions consistent with the existing design system (`btn-primary`, `alert-success`, etc.).
- **Missing review flow in the UI**: `Review` model, migration, `Customer\ReviewController::store()`, and the `customer.reviews.store` route were all fully built, but no view anywhere linked to them, so a customer had no way to leave a review after a completed job. Added the rating form + submitted-review display to `customer/requests/show.blade.php`.
- **Admin assignment UX gap**: the "assign professional" dropdown on `admin/requests/index` listed *all* active + verified workers regardless of whether they actually offered the requested service category, so the UI let an admin pick someone the backend would then reject with a validation error. Filtered the list server-side per request so only qualified professionals appear (and disabled the control with a clear message when none are qualified).
- **Localization gaps closed**: `services/index`, `services/show`, `booking/create`, `customer/addresses/index`, and `worker/dashboard` were fully hardcoded in English despite being customer- and worker-facing pages. All five are now fully bilingual (EN/AR) following the `$ar` pattern already used elsewhere in the app.
- **Admin verification quick-select** on the admin dashboard didn't reflect a worker's current verification status (always displayed "Pending" regardless of actual state). Fixed to pre-select the true current status.

## Known scope decisions / not changed
- The admin portal (`admin/dashboard`, `admin/users/index`, `admin/services/index`) remains English-only. This is a common, defensible choice for internal back-office tooling, but the QA brief technically asks for full bilingual coverage everywhere — flag if you want these translated too.
- System-generated notification text (`App\Services\RequestWorkflow::notify()`) is stored in English only; making stored notification copy bilingual would require either storing a translation key instead of rendered text, or duplicating messages per-locale, which is a larger data-model change than a UI fix and was left out of this pass.
- `WorkerAvailability` has a model/migration but no controller or UI — matches the "Feature Matrix" above, which already lists this as a future extension, not a current-scope regression.
- This pass could not run `composer install` (no `packagist.org` egress in this environment), so `php artisan route:list`, `php artisan migrate:fresh --seed`, and `php artisan test` were **not** executed here. Every `.php` file was syntax-checked with `php -l` and passed. Run the artisan commands yourself with real Composer/Node access before signing off on V3.
