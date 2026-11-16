<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Appointment;
use App\Models\Complaint;
use App\Models\Conversation;
use App\Models\Favorite;
use App\Models\Message;
use App\Models\Quotation;
use App\Models\Review;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkerAvailability;
use App\Models\WorkerProfile;
use App\Models\WorkerService;
use App\Payments\PaymentCaptureService;
use App\Services\RequestWorkflow;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * SALE7LI seeder.
 *
 * 1. Reference data (8 service categories) — always, idempotent, production-safe.
 * 2. Everything below the production guard is DEMO data for local / staging
 *    exploration only. It uses published passwords and `updateOrCreate`, so it
 *    must never run in production.
 *
 * The demo block builds a deterministic, presentation-ready walkthrough dataset
 * (Phase 31 Step 3): several professionals across categories with real
 * availability, ~15 service requests spread across the last 30 days covering the
 * full request lifecycle, quotations, WHISH-free card payments captured through
 * the real `PaymentCaptureService`, reviews aggregated exactly like
 * `ReviewController`, a couple of complaints, and short synthetic conversations.
 *
 * State transitions go through `RequestWorkflow` so every reputation figure and
 * every analytics value is produced by the application's own code — nothing is
 * fabricated. Only display timestamps (`created_at`, `completed_at`, `paid_at`)
 * are back-dated afterwards, on already-valid rows, so the time-series charts
 * show a spread instead of one spike.
 *
 * Idempotent: `db:seed` twice does not duplicate. The heavy walkthrough is
 * guarded by the presence of `SL-DEMO002`; a re-seed still upserts accounts,
 * profiles, services and availability.
 */
class DatabaseSeeder extends Seeder
{
    private RequestWorkflow $workflow;

    /** @var array<string, ServiceCategory> keyed by slug */
    private array $cats = [];

    private const PROBLEMS = [
        'electrical' => 'Kitchen socket sparks when appliances are plugged in — needs inspection and repair.',
        'plumbing' => 'Bathroom sink drains very slowly and the mixer tap drips constantly.',
        'ac-hvac' => 'Living-room AC cools poorly and makes a rattling noise on start-up.',
        'cleaning' => 'Two-bedroom apartment deep clean ahead of a family visit.',
        'carpentry' => 'Wardrobe door hinge is broken and one drawer runner needs replacing.',
        'painting' => 'Repaint the hallway and touch up scuff marks in the living room.',
        'appliance-repair' => 'Washing machine stops mid-cycle and will not drain.',
        'general-maintenance' => 'A few small jobs: secure a loose handrail, re-seal the kitchen worktop, replace two bulbs.',
    ];

    public function run(): void
    {
        $this->seedCategories();

        // Demo/sample data only — never in production.
        if (app()->environment('production')) {
            return;
        }

        $this->workflow = app(RequestWorkflow::class);
        $this->cats = ServiceCategory::all()->keyBy('slug')->all();

        [$admin, $customer, $worker] = $this->coreAccounts();
        $customers = $this->demoCustomers($customer);
        $workers = $this->demoWorkers($worker);
        $this->favourites($customer, $workers);

        // Heavy walkthrough dataset: built exactly once.
        if (! ServiceRequest::where('reference', 'SL-DEMO002')->exists()) {
            foreach ($this->plan() as $spec) {
                $this->drive($spec, $customers, $workers);
            }
        }

        $this->command?->info('Demo accounts: admin@sale7li.test / Admin@12345; customer@sale7li.test / Customer@12345; worker@sale7li.test / Worker@12345');
    }

    /* ── Reference data ─────────────────────────────────────────────────── */

    private function seedCategories(): void
    {
        $services = [
            ['Electrical', 'الكهرباء', 'Electrical repairs, installations and troubleshooting.', 'إصلاحات وتمديدات وأعمال كهربائية وتشخيص الأعطال.', '⚡', 35],
            ['Plumbing', 'السباكة', 'Leaks, faucets, pipes, drainage and water systems.', 'تسربات المياه والصنابير والأنابيب والصرف الصحي.', '💧', 30],
            ['AC & HVAC', 'التكييف والتدفئة', 'AC servicing, cleaning, diagnostics and repairs.', 'صيانة وتنظيف وتشخيص وإصلاح أجهزة التكييف.', '❄️', 40],
            ['Cleaning', 'التنظيف', 'Home deep cleaning and recurring cleaning services.', 'تنظيف المنازل والتنظيف العميق والدوري.', '🧹', 25],
            ['Carpentry', 'النجارة', 'Furniture repair, doors, cabinets and custom work.', 'إصلاح الأثاث والأبواب والخزائن وأعمال النجارة.', '🪚', 45],
            ['Painting', 'الدهان', 'Interior touch-ups, rooms and full-home painting.', 'دهان الغرف والجدران وتجديد الطلاء.', '🎨', 50],
            ['Appliance Repair', 'إصلاح الأجهزة', 'Diagnostics and repair for common home appliances.', 'تشخيص وإصلاح الأجهزة المنزلية الشائعة.', '🔧', 35],
            ['General Maintenance', 'الصيانة العامة', 'Practical fixes for everyday home maintenance.', 'حلول عملية لمختلف أعمال الصيانة المنزلية.', '🛠️', 30],
        ];

        foreach ($services as [$name, $ar, $desc, $descAr, $icon, $price]) {
            ServiceCategory::updateOrCreate(
                ['name' => $name],
                ['name_ar' => $ar, 'slug' => str()->slug($name), 'description' => $desc, 'description_ar' => $descAr, 'icon' => $icon, 'base_price' => $price, 'is_active' => true],
            );
        }
    }

    /* ── Accounts ───────────────────────────────────────────────────────── */

    /** @return array{0: User, 1: User, 2: User} admin, primary customer, primary worker */
    private function coreAccounts(): array
    {
        $admin = User::updateOrCreate(['email' => 'admin@sale7li.test'], ['name' => 'SALE7LI Admin', 'password' => 'Admin@12345', 'role' => 'admin', 'status' => 'active', 'locale' => 'en']);
        $customer = User::updateOrCreate(['email' => 'customer@sale7li.test'], ['name' => 'Demo Customer', 'phone' => '+961 70 000 000', 'password' => 'Customer@12345', 'role' => 'customer', 'status' => 'active', 'locale' => 'en']);
        $worker = User::updateOrCreate(['email' => 'worker@sale7li.test'], ['name' => 'Ahmad Hassan', 'phone' => '+961 71 111 111', 'password' => 'Worker@12345', 'role' => 'worker', 'status' => 'active', 'locale' => 'en']);

        foreach ([$admin, $customer, $worker] as $demo) {
            if (! $demo->hasVerifiedEmail()) {
                $demo->markEmailAsVerified();
            }
        }

        if ($customer->wasRecentlyCreated) {
            $customer->forceFill(['created_at' => now()->subDays(30)])->save();
        }

        $this->equipWorker($worker, ['electrical', 'plumbing', 'ac-hvac', 'cleaning'], 'verified', 8, 20,
            'Verified home-service professional with practical field experience across electrical, plumbing and AC work.',
            'محترف خدمات منزلية موثّق يتمتع بخبرة ميدانية في أعمال الكهرباء والسباكة والتكييف.');

        Address::updateOrCreate(
            ['user_id' => $customer->id, 'title' => 'Home'],
            ['street' => 'Hamra Main Street', 'city' => 'Beirut', 'area' => 'Hamra', 'postal_code' => '1103', 'instructions' => 'Call before arriving.', 'is_default' => true],
        );

        // The long-standing standalone "assign me" demo request.
        $address = Address::where('user_id', $customer->id)->where('title', 'Home')->first();
        ServiceRequest::updateOrCreate(
            ['reference' => 'SL-DEMO001'],
            [
                'customer_id' => $customer->id, 'worker_id' => null,
                'service_category_id' => $this->cats['electrical']->id, 'address_id' => $address?->id,
                'problem_description' => 'Demo request: kitchen socket needs inspection and repair.',
                'preferred_date' => now()->addDay()->toDateString(), 'preferred_time' => '10:00',
                'is_emergency' => false, 'status' => 'pending', 'payment_status' => 'pending',
            ],
        );

        return [$admin, $customer, $worker];
    }

    /** @return array<string, User> keyed by email (+ 'demo' => primary) */
    private function demoCustomers(User $primary): array
    {
        $specs = [
            ['nadia.sfeir@sale7li.test', 'Nadia Sfeir', '+961 70 111 111', 25, 'Sassine Square', 'Achrafieh'],
            ['omar.fares@sale7li.test', 'Omar Fares', '+961 70 222 222', 17, 'Verdun Street', 'Verdun'],
            ['maya.tannous@sale7li.test', 'Maya Tannous', '+961 70 333 333', 8, 'Armenia Street', 'Mar Mikhael'],
        ];

        $customers = ['demo' => $primary];

        foreach ($specs as [$email, $name, $phone, $ageDays, $street, $area]) {
            $u = User::updateOrCreate(['email' => $email], ['name' => $name, 'phone' => $phone, 'password' => 'Customer@12345', 'role' => 'customer', 'status' => 'active', 'locale' => 'en']);
            if (! $u->hasVerifiedEmail()) {
                $u->markEmailAsVerified();
            }
            if ($u->wasRecentlyCreated) {
                $u->forceFill(['created_at' => now()->subDays($ageDays)])->save();
            }
            Address::updateOrCreate(
                ['user_id' => $u->id, 'title' => 'Home'],
                ['street' => $street, 'city' => 'Beirut', 'area' => $area, 'postal_code' => '1100', 'instructions' => 'Ring the bell.', 'is_default' => true],
            );
            $customers[$email] = $u;
        }

        return $customers;
    }

    /** @return array<string, User> keyed by email (+ 'ahmad' => primary worker) */
    private function demoWorkers(User $primary): array
    {
        $specs = [
            ['layla.karam@sale7li.test', 'Layla Karam', '+961 71 222 222', ['cleaning', 'painting'], 'verified', 6, 15, 'Detail-focused deep-cleaning and interior painting.', 'تنظيف عميق ودهان داخلي مع الاهتمام بالتفاصيل.'],
            ['samir.haddad@sale7li.test', 'Samir Haddad', '+961 71 333 333', ['carpentry', 'general-maintenance'], 'verified', 12, 25, 'Carpentry and all-round home maintenance, 12 years on the job.', 'نجارة وصيانة منزلية شاملة، خبرة 12 عامًا.'],
            ['rania.obeid@sale7li.test', 'Rania Obeid', '+961 71 444 444', ['ac-hvac', 'appliance-repair'], 'verified', 9, 20, 'AC servicing and home-appliance diagnostics and repair.', 'صيانة تكييف وتشخيص وإصلاح الأجهزة المنزلية.'],
            ['khalil.nassar@sale7li.test', 'Khalil Nassar', '+961 71 555 555', ['plumbing'], 'pending', 3, 10, 'Plumbing professional building a track record on SALE7LI.', 'محترف سباكة يبني سجلّه المهني على المنصة.'],
            ['georges.rizk@sale7li.test', 'Georges Rizk', '+961 71 666 666', ['electrical'], 'rejected', 2, 10, 'Electrical application currently under review.', 'طلب انضمام لأعمال الكهرباء قيد المراجعة.'],
        ];

        $workers = ['ahmad' => $primary];

        foreach ($specs as [$email, $name, $phone, $slugs, $verification, $exp, $radius, $bio, $bioAr]) {
            $u = User::updateOrCreate(['email' => $email], ['name' => $name, 'phone' => $phone, 'password' => 'Worker@12345', 'role' => 'worker', 'status' => 'active', 'locale' => 'en']);
            if (! $u->hasVerifiedEmail()) {
                $u->markEmailAsVerified();
            }
            $this->equipWorker($u, $slugs, $verification, $exp, $radius, $bio, $bioAr);
            $workers[$email] = $u;
        }

        return $workers;
    }

    /**
     * Give a worker a profile, an active service catalogue and a Mon–Fri
     * 09:00–17:00 weekly schedule. Reputation counters are left at honest
     * defaults — RequestWorkflow / ReviewController recompute them from real
     * history as demo jobs complete.
     *
     * @param  list<string>  $slugs
     */
    private function equipWorker(User $user, array $slugs, string $verification, int $experienceYears, int $radiusKm, string $bio, string $bioAr): void
    {
        WorkerProfile::updateOrCreate(
            ['user_id' => $user->id],
            ['bio' => $bio, 'bio_ar' => $bioAr, 'experience_years' => $experienceYears, 'verification_status' => $verification, 'service_radius_km' => $radiusKm],
        );

        foreach ($slugs as $slug) {
            $cat = $this->cats[$slug];
            WorkerService::updateOrCreate(
                ['worker_id' => $user->id, 'service_category_id' => $cat->id],
                ['price_from' => $cat->base_price, 'is_active' => true],
            );
        }

        foreach (range(0, 6) as $day) {
            WorkerAvailability::updateOrCreate(
                ['worker_id' => $user->id, 'day_of_week' => $day],
                $day <= 4
                    ? ['start_time' => '09:00', 'end_time' => '17:00', 'is_available' => true]
                    : ['start_time' => null, 'end_time' => null, 'is_available' => false],
            );
        }
    }

    /** @param  array<string, User>  $workers */
    private function favourites(User $customer, array $workers): void
    {
        foreach (['layla.karam@sale7li.test', 'samir.haddad@sale7li.test', 'rania.obeid@sale7li.test'] as $email) {
            Favorite::firstOrCreate(['customer_id' => $customer->id, 'worker_id' => $workers[$email]->id]);
        }
    }

    /* ── Walkthrough requests ───────────────────────────────────────────── */

    /**
     * ref, customerKey, workerKey|null, categorySlug, targetState, ageDays, opts
     *
     * @return list<array{0:string,1:string,2:?string,3:string,4:string,5:int,6:array<string,mixed>}>
     */
    private function plan(): array
    {
        return [
            ['SL-DEMO002', 'demo', 'ahmad', 'plumbing', 'completed', 24, ['pay' => true, 'review' => 5, 'chat' => true, 'complaint' => 'resolved']],
            ['SL-DEMO003', 'demo', 'ahmad', 'ac-hvac', 'completed', 15, ['pay' => true]],
            ['SL-DEMO004', 'demo', 'ahmad', 'electrical', 'in_progress', 5, ['pay' => true, 'chat' => true]],
            ['SL-DEMO005', 'demo', 'layla.karam@sale7li.test', 'cleaning', 'scheduled', 3, ['quote' => 'pending']],
            ['SL-DEMO006', 'demo', 'samir.haddad@sale7li.test', 'carpentry', 'accepted', 2, []],
            ['SL-DEMO007', 'demo', null, 'painting', 'pending', 1, ['emergency' => true]],
            ['SL-DEMO008', 'demo', 'ahmad', 'appliance-repair', 'cancelled', 9, ['cancelBy' => 'customer', 'reason' => 'schedule_changed']],
            ['SL-DEMO009', 'nadia.sfeir@sale7li.test', 'rania.obeid@sale7li.test', 'ac-hvac', 'completed', 20, ['pay' => true, 'review' => 4]],
            ['SL-DEMO010', 'nadia.sfeir@sale7li.test', 'samir.haddad@sale7li.test', 'general-maintenance', 'completed', 12, ['pay' => true, 'review' => 5]],
            ['SL-DEMO011', 'omar.fares@sale7li.test', 'layla.karam@sale7li.test', 'painting', 'in_progress', 6, ['quote' => 'accepted']],
            ['SL-DEMO012', 'omar.fares@sale7li.test', null, 'plumbing', 'pending', 4, []],
            ['SL-DEMO013', 'maya.tannous@sale7li.test', 'ahmad', 'cleaning', 'completed', 7, ['pay' => true, 'review' => 4]],
            ['SL-DEMO014', 'maya.tannous@sale7li.test', 'rania.obeid@sale7li.test', 'appliance-repair', 'cancelled', 11, ['cancelBy' => 'worker', 'complaint' => 'open']],
            ['SL-DEMO015', 'nadia.sfeir@sale7li.test', null, 'carpentry', 'rejected', 13, ['rejectBy' => 'admin']],
        ];
    }

    /**
     * @param  array{0:string,1:string,2:?string,3:string,4:string,5:int,6:array<string,mixed>}  $spec
     * @param  array<string, User>  $customers
     * @param  array<string, User>  $workers
     */
    private function drive(array $spec, array $customers, array $workers): void
    {
        [$ref, $custKey, $workerKey, $slug, $target, $ageDays, $opts] = $spec + [6 => []];

        $customer = $customers[$custKey];
        $category = $this->cats[$slug];
        $address = $customer->addresses()->where('is_default', true)->first() ?? $customer->addresses()->first();
        $createdAt = now()->subDays($ageDays)->setTime(10, 0);
        $completedAt = null;

        $req = ServiceRequest::create([
            'reference' => $ref,
            'customer_id' => $customer->id,
            'worker_id' => null,
            'service_category_id' => $category->id,
            'address_id' => $address?->id,
            'problem_description' => self::PROBLEMS[$slug],
            'problem_images' => [],
            'preferred_date' => $createdAt->copy()->addDays(2)->toDateString(),
            'preferred_time' => '10:00',
            'is_emergency' => (bool) ($opts['emergency'] ?? false),
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        $worker = $workerKey !== null ? $workers[$workerKey] : null;

        // --- unassigned terminal states -------------------------------------
        if ($worker === null) {
            if ($target === 'rejected') {
                $req = $this->workflow->transition($req, 'rejected', $opts['rejectBy'] ?? 'admin', null);
                $completedAt = $createdAt->copy()->addHours(6);
            } elseif ($target === 'cancelled') {
                $req = $this->workflow->transition($req, 'cancelled', $opts['cancelBy'] ?? 'customer', $opts['reason'] ?? null);
                $completedAt = $createdAt->copy()->addDay();
            }
            // 'pending' stays as created.
            $this->stamp($req, $createdAt, $completedAt);

            return;
        }

        // --- assigned + accepted ------------------------------------------
        $req = $this->workflow->assign($req, $worker);
        $req = $this->workflow->workerAccept($req, $worker);

        if ($target === 'cancelled') {
            $req = $this->workflow->transition($req, 'cancelled', $opts['cancelBy'] ?? 'customer', $opts['reason'] ?? null);
            $this->stamp($req, $createdAt, $createdAt->copy()->addDays(2));
            $this->afterState($req, $customer, $worker, $opts, $createdAt);

            return;
        }

        if ($target !== 'accepted') {
            // --- quotation ----------------------------------------------------
            $quoteState = $opts['quote'] ?? 'accepted';
            $this->addQuote($req, $worker, $category, $quoteState);

            // --- payment (real capture path) --------------------------------
            if (($opts['pay'] ?? false) && $quoteState === 'accepted') {
                app(PaymentCaptureService::class)->capture($req->fresh(), [
                    'transaction_reference' => 'TEST-'.substr($ref, 3),
                    'payment_method' => 'card',
                ]);
                $req->refresh();
                $req->payment?->forceFill(['created_at' => $createdAt->copy()->addDay(), 'paid_at' => $createdAt->copy()->addDay()])->save();
                $req->invoice?->forceFill(['created_at' => $createdAt->copy()->addDay()])->save();
            }

            // --- schedule (mirrors JobController@status) --------------------
            $scheduledFor = $target === 'scheduled' ? now()->addDay() : $createdAt->copy()->addDay();
            Appointment::updateOrCreate(
                ['service_request_id' => $req->id],
                ['worker_id' => $worker->id, 'scheduled_date' => $scheduledFor->toDateString(), 'scheduled_time' => '10:00', 'status' => 'scheduled'],
            );
            $req = $this->workflow->transition($req, 'scheduled');

            if ($target !== 'scheduled') {
                $req = $this->workflow->transition($req, 'on_the_way');
                $req = $this->workflow->transition($req, 'in_progress');

                if ($target === 'completed') {
                    $req->refresh();
                    $req->update(['completion_notes' => 'Work completed and tested with the customer present.', 'completion_images' => []]);
                    $req->appointment?->update(['status' => 'completed']);
                    $req = $this->workflow->transition($req, 'completed');
                    $completedAt = $createdAt->copy()->addDays(3);
                }
            }
        }

        $this->stamp($req, $createdAt, $completedAt);
        $this->afterState($req, $customer, $worker, $opts, $createdAt);
    }

    /** @param  array<string, mixed>  $opts */
    private function afterState(ServiceRequest $req, User $customer, User $worker, array $opts, Carbon $createdAt): void
    {
        if ($opts['chat'] ?? false) {
            $this->addChat($req, $customer, $worker, $createdAt);
        }
        if (($opts['review'] ?? null) !== null && $req->status === 'completed') {
            $this->addReview($req, $customer, $worker, (int) $opts['review']);
        }
        if (($opts['complaint'] ?? null) !== null) {
            $this->addComplaint($req, $customer, (string) $opts['complaint']);
        }
    }

    /* ── Sub-records ────────────────────────────────────────────────────── */

    private function stamp(ServiceRequest $req, Carbon $createdAt, ?Carbon $endAt = null): void
    {
        // Display timestamps only — the row is already in a valid state.
        $attributes = ['created_at' => $createdAt, 'updated_at' => $endAt ?? $createdAt];
        if ($endAt !== null && $req->status === 'completed') {
            $attributes['completed_at'] = $endAt;
        }
        $req->forceFill($attributes)->save();
    }

    private function addQuote(ServiceRequest $req, User $worker, ServiceCategory $category, string $state): void
    {
        $base = number_format((float) $category->base_price, 2, '.', '');
        $additional = '15.00';
        $total = number_format((float) $base + 15, 2, '.', '');

        $quote = Quotation::create([
            'service_request_id' => $req->id,
            'worker_id' => $worker->id,
            'base_price' => $base,
            'additional_charges' => $additional,
            'discount' => 0,
            'tax' => 0,
            'total' => $total,
            'notes' => 'Includes parts and labour. Quote valid for 7 days.',
            'status' => 'pending',
            'responded_at' => null,
        ]);

        if ($state === 'accepted') {
            // Mirrors Customer\QuotationController::accept.
            $quote->update(['status' => 'accepted', 'responded_at' => now()]);
            $req->update(['quoted_total' => $quote->total]);
        }
    }

    private function addReview(ServiceRequest $req, User $customer, User $worker, int $stars): void
    {
        $comments = [
            5 => 'Excellent work — punctual, tidy, and everything was fixed properly.',
            4 => 'Good job overall. Arrived a little late but the work was solid and well explained.',
        ];

        Review::firstOrCreate(
            ['service_request_id' => $req->id],
            [
                'customer_id' => $customer->id,
                'worker_id' => $worker->id,
                'rating' => $stars,
                'comment' => $comments[$stars] ?? 'Reliable and professional.',
                'photos' => [],
            ],
        );

        // Mirrors Customer\ReviewController::store aggregation.
        $profile = $worker->workerProfile;
        $profile->update([
            'rating_count' => Review::where('worker_id', $worker->id)->count(),
            'rating_average' => round((float) Review::where('worker_id', $worker->id)->avg('rating'), 2),
        ]);
    }

    private function addChat(ServiceRequest $req, User $customer, User $worker, Carbon $createdAt): void
    {
        $conversation = Conversation::firstOrCreate(
            ['service_request_id' => $req->id],
            ['customer_id' => $req->customer_id, 'worker_id' => $req->worker_id],
        );

        $lines = [
            [$worker->id, 'Hi — I can come by around 10:00 as scheduled. Does that still work for you?'],
            [$customer->id, 'Yes, 10:00 is perfect. The building entrance code is on the intercom.'],
            [$worker->id, 'Thanks, noted. I have the parts with me.'],
            [$customer->id, 'Great, see you then.'],
        ];

        $base = $createdAt->copy()->addDay();
        foreach ($lines as $i => [$senderId, $body]) {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $senderId,
                'body' => $body,
                'attachments' => [],
                'read_at' => $i < 3 ? $base->copy()->addMinutes(($i + 1) * 25) : null,
            ]);
            $message->forceFill([
                'created_at' => $base->copy()->addMinutes($i * 20),
                'updated_at' => $base->copy()->addMinutes($i * 20),
            ])->save();
        }
    }

    private function addComplaint(ServiceRequest $req, User $customer, string $state): void
    {
        $presets = [
            'resolved' => [
                'category' => 'billing', 'priority' => 'low', 'status' => 'resolved',
                'description' => 'I was not sure about an extra charge that appeared on the quote.',
                'internal_notes' => 'Clarified the additional-charges line with the customer by phone. No adjustment needed — marked resolved.',
            ],
            'open' => [
                'category' => 'no_show', 'priority' => 'high', 'status' => 'open',
                'description' => 'The professional did not arrive within the agreed window and did not call ahead.',
                'internal_notes' => null,
            ],
        ];

        $preset = $presets[$state];

        Complaint::firstOrCreate(
            ['service_request_id' => $req->id, 'reporter_id' => $customer->id],
            [
                'category' => $preset['category'],
                'priority' => $preset['priority'],
                'status' => $preset['status'],
                'description' => $preset['description'],
                'internal_notes' => $preset['internal_notes'],
            ],
        );
    }
}
