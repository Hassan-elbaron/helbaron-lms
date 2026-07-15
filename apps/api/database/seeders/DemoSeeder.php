<?php

namespace Database\Seeders;

use App\Contexts\Analytics\Enums\Granularity;
use App\Contexts\Analytics\Models\MetricSnapshot;
use App\Contexts\Commerce\Actions\Payment\RefundOrderAction;
use App\Contexts\Commerce\Database\Seeders\CommerceSeeder;
use App\Contexts\Commerce\Enums\CouponScope;
use App\Contexts\Commerce\Enums\CouponType;
use App\Contexts\Commerce\Enums\InvoiceStatus;
use App\Contexts\Commerce\Enums\OrderStatus;
use App\Contexts\Commerce\Enums\ProductStatus;
use App\Contexts\Commerce\Enums\ProductType;
use App\Contexts\Commerce\Enums\TransactionStatus;
use App\Contexts\Commerce\Enums\TransactionType;
use App\Contexts\Commerce\Models\Coupon;
use App\Contexts\Commerce\Models\Order;
use App\Contexts\Commerce\Models\Product;
use App\Contexts\Learning\Actions\Engagement\ToggleBookmarkAction;
use App\Contexts\Learning\Actions\Engagement\UpsertLessonNoteAction;
use App\Contexts\Learning\Actions\Enrollment\GrantEnrollmentAction;
use App\Contexts\Learning\Actions\Progress\RecordLessonProgressAction;
use App\Contexts\Learning\Enums\EnrollmentSource;
use App\Contexts\Learning\Enums\EnrollmentStatus;
use App\Contexts\Learning\Enums\LessonProgressStatus;
use App\Domains\Authoring\Enums\LessonType;
use App\Domains\Authoring\Enums\PublishState;
use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Models\LessonMedia;
use App\Domains\Authoring\Models\Section;
use App\Domains\Catalog\Enums\CourseStatus;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\Course;
use App\Domains\Catalog\Models\CourseLanguage;
use App\Domains\Catalog\Models\CourseLevel;
use App\Domains\Catalog\Models\CourseTag;
use App\Domains\Certification\Actions\ReissueCertificateAction;
use App\Domains\Certification\Actions\RevokeCertificateAction;
use App\Domains\Certification\Database\Seeders\CertificationSeeder;
use App\Domains\Certification\Enums\CertificateStatus;
use App\Domains\Certification\Models\Certificate;
use App\Domains\Crm\Database\Seeders\CrmSeeder;
use App\Domains\Crm\Enums\ActivityType;
use App\Domains\Crm\Enums\ConsultingRequestStatus;
use App\Domains\Crm\Enums\LeadStatus;
use App\Domains\Crm\Enums\MemberRole;
use App\Domains\Crm\Enums\MemberStatus;
use App\Domains\Crm\Enums\OpportunityStatus;
use App\Domains\Crm\Models\Company;
use App\Domains\Crm\Models\ConsultingRequest;
use App\Domains\Crm\Models\Lead;
use App\Domains\Crm\Models\Organization;
use App\Domains\Crm\Models\OrganizationMember;
use App\Domains\Crm\Models\Pipeline;
use App\Domains\Live\Actions\Session\ScheduleSessionAction;
use App\Domains\Live\Enums\AttendanceSource;
use App\Domains\Live\Enums\LiveSessionStatus;
use App\Domains\Live\Enums\RecordingStatus;
use App\Domains\Live\Enums\RegistrationStatus;
use App\Domains\Live\Models\LiveCourse;
use App\Domains\Live\Models\LiveSession;
use App\Platform\Homepage\Models\HomepageSection;
use App\Platform\Identity\Database\Seeders\RolePermissionSeeder;
use App\Platform\Identity\Enums\Role;
use App\Platform\Identity\Models\User;
use App\Platform\Notifications\Actions\SendNotificationAction;
use App\Platform\Notifications\Database\Seeders\NotificationsSeeder;
use App\Platform\Notifications\Enums\NotificationCategory;
use App\Platform\Notifications\Models\Notification;
use App\Platform\Notifications\Models\NotificationTemplate;
use App\Platform\Shared\Audit\AuditLogger;
use App\Platform\Shared\Enums\Visibility;
use App\Platform\Shared\Helpers\Slug;
use App\Platform\Shared\Helpers\Uuid;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds a rich, deterministic, production-safe DEMO dataset across every product area, at a
 * configurable SCALE (config('demo.scale') selects a profile from config('demo.profiles')).
 *
 * Guarantees:
 *   - GATED: never runs in `production` or when demo mode is disabled (safe under db:seed too).
 *   - DETERMINISTIC: RNG seeded from config('demo.seed'); the same DEMO_SEED reproduces the dataset.
 *   - IDEMPOTENT: keyed writes use firstOrCreate / insertOrIgnore on stable business keys (email
 *     domain, `demo-` slug, `DEMO` coupon code, user+course, org slug). High-volume leaf areas are
 *     GUARDED BY COUNT: if the demo-scoped count already meets the profile target, the area is
 *     skipped, so a re-run at the same scale is a no-op there (no uncontrolled duplicates).
 *
 * Scaling strategy: the entitlement / progress / certificate / refund / live / notification seams
 * are driven through the real domain Actions for a representative SUBSET (so events, metrics and
 * certificates genuinely fire), while the BULK volume is generated with chunked, event-free
 * insert()/insertOrIgnore() (the repo's seeding convention) using valid enum values and FKs.
 *
 * Unsupported-by-repo items are represented honestly, never fabricated: there are NO Quiz /
 * Assignment / Review entities (quizzes are lessons of LessonType::QuizPlaceholder; assignments and
 * reviews are skipped), and there is NO analytics events table (250k+ "events" are represented as
 * aggregate MetricSnapshot values across 365 days, not literal rows). No table or column is added.
 */
class DemoSeeder extends Seeder
{
    private string $emailDomain;

    private string $currency;

    private int $seed;

    private bool $externalMedia;

    private string $passwordHash;

    /** @var array<string, int> role name => id */
    private array $roleIds = [];

    private string $userMorph;

    /** @var array<string, mixed> active scale profile (config('demo.profiles.<scale>')) */
    private array $p;

    private int $actionStudents = 8;

    /** @var Collection<int, User> */
    private Collection $instructors;

    /** @var Collection<int, User> */
    private Collection $students;

    /** @var Collection<int, Course> */
    private Collection $courses;

    /** @var array<int, array<string, mixed>> blueprint for each course in $this->courses (same order) */
    private array $courseBp = [];

    /** @var array<int, list<int>> published lesson ids by course id */
    private array $lessonsByCourse = [];

    /**
     * @var list<array{title:string,subtitle:string,category:string,level:string,featured:bool,priced:bool,price:int,sale:int|null}>
     */
    private array $courseBlueprints = [
        ['title' => 'Project Management Foundations', 'subtitle' => 'Plan, execute, and deliver projects with confidence.', 'category' => 'project-management', 'level' => 'Beginner', 'featured' => true, 'priced' => true, 'price' => 19900, 'sale' => 14900],
        ['title' => 'Agile & Scrum in Practice', 'subtitle' => 'Run agile teams that ship value every sprint.', 'category' => 'agile-mindset', 'level' => 'Intermediate', 'featured' => false, 'priced' => true, 'price' => 24900, 'sale' => null],
        ['title' => 'Business Development Essentials', 'subtitle' => 'Build pipeline, partnerships, and sustainable revenue.', 'category' => 'business-development', 'level' => 'Beginner', 'featured' => false, 'priced' => true, 'price' => 17900, 'sale' => null],
        ['title' => 'Competitive Business Strategy', 'subtitle' => 'Frameworks to position and win in the MENA market.', 'category' => 'business-strategies', 'level' => 'Advanced', 'featured' => true, 'priced' => true, 'price' => 29900, 'sale' => 22900],
        ['title' => 'From Idea to Startup', 'subtitle' => 'Validate, build, and launch your first venture.', 'category' => 'entrepreneurship', 'level' => 'Beginner', 'featured' => false, 'priced' => false, 'price' => 0, 'sale' => null],
        ['title' => 'Essential Business Skills', 'subtitle' => 'Communication, negotiation, and personal productivity.', 'category' => 'business-skills', 'level' => 'Beginner', 'featured' => false, 'priced' => false, 'price' => 0, 'sale' => null],
        ['title' => 'Leadership for New Managers', 'subtitle' => 'Lead people, not just tasks - your first 90 days.', 'category' => 'leadership', 'level' => 'Intermediate', 'featured' => true, 'priced' => true, 'price' => 21900, 'sale' => null],
        ['title' => 'Modern Marketing Strategy', 'subtitle' => 'Positioning, funnels, and growth for MENA brands.', 'category' => 'marketing-strategies', 'level' => 'Intermediate', 'featured' => false, 'priced' => true, 'price' => 19900, 'sale' => null],
        ['title' => 'Sales Management Playbook', 'subtitle' => 'Coach your reps, forecast, and hit the number.', 'category' => 'sales-management', 'level' => 'Intermediate', 'featured' => false, 'priced' => true, 'price' => 18900, 'sale' => 12900],
        ['title' => 'Finance & Analysis for Managers', 'subtitle' => 'Read the numbers and make better decisions.', 'category' => 'finance-analysis', 'level' => 'Beginner', 'featured' => false, 'priced' => true, 'price' => 20900, 'sale' => null],
        ['title' => 'Business AI for Decision Makers', 'subtitle' => 'Apply AI to real business problems, responsibly.', 'category' => 'business-ai', 'level' => 'Intermediate', 'featured' => true, 'priced' => true, 'price' => 27900, 'sale' => null],
        ['title' => 'Investment & Trading Basics', 'subtitle' => 'Markets, risk, and portfolio fundamentals.', 'category' => 'investment-trading', 'level' => 'Beginner', 'featured' => false, 'priced' => false, 'price' => 0, 'sale' => null],
        ['title' => 'Negotiation Mastery', 'subtitle' => 'Reach better agreements under pressure.', 'category' => 'business-skills', 'level' => 'Intermediate', 'featured' => false, 'priced' => true, 'price' => 16900, 'sale' => null],
        ['title' => 'Data Storytelling for Teams', 'subtitle' => 'Turn dashboards into decisions people act on.', 'category' => 'business-ai', 'level' => 'Beginner', 'featured' => false, 'priced' => false, 'price' => 0, 'sale' => null],
    ];

    /** @var array<string, string> vertical slug => display name */
    private array $categories = [
        'project-management' => 'Project Management',
        'agile-mindset' => 'Agile Mindset',
        'business-development' => 'Business Development',
        'business-strategies' => 'Business Strategies',
        'entrepreneurship' => 'Entrepreneurship',
        'business-skills' => 'Business Skills',
        'leadership' => 'Leadership',
        'marketing-strategies' => 'Marketing Strategies',
        'sales-management' => 'Sales Management',
        'finance-analysis' => 'Finance & Analysis',
        'business-ai' => 'Business AI',
        'investment-trading' => 'Investment & Trading',
    ];

    /** @var list<string> cohort/edition suffixes for course variants (index 0 = no suffix) */
    private array $cohorts = ['', ' — Spring Cohort', ' — Autumn Cohort', ' — Winter Cohort'];

    public function __construct()
    {
        $this->instructors = new Collection;
        $this->students = new Collection;
        $this->courses = new Collection;
    }

    public function run(): void
    {
        $this->execute(false);
    }

    /**
     * @return array<string, int>
     */
    public function seedDemo(bool $reset = false): array
    {
        return $this->execute($reset);
    }

    /**
     * @return array<string, int>
     */
    private function execute(bool $reset): array
    {
        if (app()->environment('production') || config('demo.enabled') !== true) {
            return [];
        }

        $this->emailDomain = (string) config('demo.markers.email_domain');
        $this->currency = (string) config('demo.markers.currency');
        $this->seed = (int) config('demo.seed');
        $this->externalMedia = (bool) config('demo.external_media');

        $scale = (string) config('demo.scale', 'showcase');
        /** @var array<string, mixed>|null $profile */
        $profile = config('demo.profiles.'.$scale);
        $this->p = is_array($profile) ? $profile : (array) config('demo.profiles.showcase');

        $this->seedRng();

        if ($reset) {
            $this->purge();
        }

        $this->call([
            RolePermissionSeeder::class,
            CertificationSeeder::class,
            NotificationsSeeder::class,
            CrmSeeder::class,
        ]);

        $this->seedUsers();
        $this->seedTaxonomyAndCourses();

        $this->call([CommerceSeeder::class]);

        $this->seedCommerceCatalog();
        $this->cacheLessons();
        $this->seedEnrollmentsAndProgress();
        $this->seedCertificates();
        $this->seedEngagement();
        $this->seedOrders();
        $this->seedOrganizations();
        $this->seedLive();
        $this->seedCrm();
        $this->seedNotifications();
        $this->seedMetrics();
        $this->seedAudit();
        $this->seedMarketingMedia();

        return $this->summary();
    }

    private function seedRng(): void
    {
        mt_srand($this->seed);
        fake()->seed($this->seed);
    }

    // ----- deterministic helpers -----

    private function ri(int $min, int $max): int
    {
        return $max <= $min ? $min : $min + mt_rand(0, $max - $min);
    }

    /**
     * @param  list<mixed>  $items
     */
    private function pick(array $items): mixed
    {
        return $items[mt_rand(0, count($items) - 1)];
    }

    /**
     * @return array{0:int,1:int}
     */
    private function pr(string $key): array
    {
        /** @var array{0:int,1:int} $v */
        $v = $this->p[$key] ?? [1, 1];

        return [(int) $v[0], (int) $v[1]];
    }

    private function pint(string $key, int $default = 0): int
    {
        return (int) ($this->p[$key] ?? $default);
    }

    private function deterministicValue(string $key, int $min, int $max): int
    {
        $span = max(1, $max - $min + 1);

        return $min + (int) (crc32($key) % $span);
    }

    // ----- media helpers (royalty-free Unsplash images + public educational video refs) -----

    /**
     * Deterministically pick one element from a list using a stable key (re-runs are identical).
     *
     * @param  list<string>  $pool
     */
    private function pickStable(array $pool, string $key): ?string
    {
        if ($pool === []) {
            return null;
        }

        return $pool[crc32($key) % count($pool)];
    }

    /**
     * @return array<string, mixed>
     */
    private function imageManifest(): array
    {
        /** @var array<string, mixed> $images */
        $images = (array) config('demo.media.images', []);

        return $images;
    }

    private function buildImageUrl(string $id, string $paramsKey): string
    {
        $images = $this->imageManifest();

        return (string) ($images['base'] ?? 'https://images.unsplash.com/photo-').$id.(string) ($images[$paramsKey] ?? '');
    }

    /** Deterministic Unsplash course-cover URL for a vertical (falls back across all covers). */
    private function coverUrl(string $category, string $key): ?string
    {
        if (! $this->externalMedia) {
            return null;
        }
        /** @var array<string, mixed> $covers */
        $covers = (array) ($this->imageManifest()['covers'] ?? []);
        /** @var list<string> $pool */
        $pool = array_values(array_map('strval', (array) ($covers[$category] ?? [])));
        if ($pool === []) {
            foreach ($covers as $set) {
                foreach ((array) $set as $id) {
                    $pool[] = (string) $id;
                }
            }
        }
        $id = $this->pickStable($pool, $key);

        return $id === null ? null : $this->buildImageUrl($id, 'cover_params');
    }

    private function avatarUrl(string $key): ?string
    {
        if (! $this->externalMedia) {
            return null;
        }
        /** @var list<string> $pool */
        $pool = array_values(array_map('strval', (array) ($this->imageManifest()['avatars'] ?? [])));
        $id = $this->pickStable($pool, $key);

        return $id === null ? null : $this->buildImageUrl($id, 'avatar_params');
    }

    private function logoUrl(string $key): ?string
    {
        if (! $this->externalMedia) {
            return null;
        }
        /** @var list<string> $pool */
        $pool = array_values(array_map('strval', (array) ($this->imageManifest()['logos'] ?? [])));
        $id = $this->pickStable($pool, $key);

        return $id === null ? null : $this->buildImageUrl($id, 'logo_params');
    }

    /**
     * A deterministic public educational video reference drawn from the manifest pool.
     *
     * @return array{provider:string, video_id:string, url:?string, embed_url:?string, title:?string, attribution:string}
     */
    private function videoRef(string $key): array
    {
        /** @var list<array<string, mixed>> $videos */
        $videos = array_values((array) config('demo.media.videos', []));
        if ($videos === []) {
            return ['provider' => 'youtube', 'video_id' => 'REPLACE_ME', 'url' => null, 'embed_url' => null, 'title' => null, 'attribution' => 'Placeholder demo reference.'];
        }
        /** @var array<string, mixed> $v */
        $v = $videos[crc32($key) % count($videos)];

        return [
            'provider' => (string) ($v['provider'] ?? 'youtube'),
            'video_id' => (string) ($v['video_id'] ?? 'REPLACE_ME'),
            'url' => isset($v['url']) ? (string) $v['url'] : null,
            'embed_url' => isset($v['embed_url']) ? (string) $v['embed_url'] : null,
            'title' => isset($v['title']) ? (string) $v['title'] : null,
            'attribution' => (string) ($v['attribution'] ?? 'Public educational talk referenced for demo purposes.'),
        ];
    }

    /**
     * Chunked, event-free bulk insert (the repo's high-volume seeding convention).
     *
     * @param  list<array<string, mixed>>  $rows
     */
    private function bulk(string $table, array $rows, bool $ignore = true, int $chunk = 1000): void
    {
        if ($rows === []) {
            return;
        }
        foreach (array_chunk($rows, $chunk) as $slice) {
            if ($ignore) {
                DB::table($table)->insertOrIgnore($slice);
            } else {
                DB::table($table)->insert($slice);
            }
        }
    }

    /**
     * @return list<int>
     */
    private function demoUserIds(): array
    {
        return DB::table('users')->where('email', 'like', '%@'.$this->emailDomain)->pluck('id')->all();
    }

    // ----- Users -----

    private function seedUsers(): void
    {
        // Hash the shared demo password ONCE (bcrypt is expensive) and cache role ids + the user
        // morph type so role assignment is a cheap pivot insert rather than a per-user spatie call
        // that reloads the whole permission cache — both are critical for enterprise-scale runtime.
        $this->passwordHash = Hash::make((string) config('demo.markers.password'));
        $this->roleIds = DB::table('roles')->where('guard_name', 'web')->pluck('id', 'name')->map(fn ($id): int => (int) $id)->all();
        $this->userMorph = (new User)->getMorphClass();

        /** @var list<array{string,string,string}> $instructorPool */
        $instructorPool = [
            ['Yara Adel', 'yara.adel', 'PMP-certified program lead, 12 years across MENA delivery.'],
            ['Omar Farouk', 'omar.farouk', 'Leadership coach and former regional operations director.'],
            ['Nour Hassan', 'nour.hassan', 'AI product strategist helping teams ship with data.'],
            ['Laila Mansour', 'laila.mansour', 'Growth marketer for MENA consumer brands.'],
            ['Karim Saleh', 'karim.saleh', 'CFA charterholder, finance and analysis educator.'],
            ['Huda Rashid', 'huda.rashid', 'Agile transformation consultant and certified Scrum trainer.'],
            ['Tariq Nabil', 'tariq.nabil', 'Ex-founder turned entrepreneurship mentor and angel investor.'],
            ['Salma Idris', 'salma.idris', 'B2B sales leader who has built teams from zero to scale.'],
            ['Rami Fouad', 'rami.fouad', 'Corporate strategist advising boards on market entry.'],
            ['Dina Wahba', 'dina.wahba', 'People-operations expert focused on manager enablement.'],
            ['Hani Mostafa', 'hani.mostafa', 'Data-visualization specialist and analytics storyteller.'],
            ['Mona Kassem', 'mona.kassem', 'Negotiation trainer with a legal and procurement background.'],
            ['Ziad Halabi', 'ziad.halabi', 'Product operations leader scaling delivery across the Gulf.'],
            ['Rasha Amin', 'rasha.amin', 'Brand and content strategist for regional startups.'],
            ['Fadi Costa', 'fadi.costa', 'Investment analyst and personal-finance educator.'],
            ['Aya Sobhi', 'aya.sobhi', 'Change-management facilitator for enterprise transformations.'],
        ];

        $count = max(1, $this->pint('instructors', 6));
        $this->instructors = collect(range(0, $count - 1))->map(function (int $i) use ($instructorPool): User {
            $row = $instructorPool[$i % count($instructorPool)];
            $suffix = $i >= count($instructorPool) ? (string) ($i + 1) : '';
            [$name, $slug, $bio] = $row;

            return $this->makeUser($name, $slug.$suffix, Role::Instructor->value, ['bio' => $bio]);
        });

        $firstNames = ['Ahmed', 'Sara', 'Mohammed', 'Fatima', 'Ali', 'Maryam', 'Youssef', 'Aisha', 'Hassan', 'Layla', 'Ibrahim', 'Noor', 'Khaled', 'Reem', 'Tamer', 'Hana', 'Bilal', 'Rana', 'Sami', 'Jana'];
        $lastNames = ['Kamal', 'Aziz', 'Salem', 'Hakim', 'Nasr', 'Darwish', 'Sultan', 'Fahmy', 'Habib', 'Zaki', 'Mahmoud', 'Shaker'];

        $students = max(1, $this->pint('students', 24));
        $width = max(2, strlen((string) $students));
        $this->students = collect(range(1, $students))->map(function (int $i) use ($firstNames, $lastNames, $width): User {
            $first = $firstNames[($i - 1) % count($firstNames)];
            $last = $lastNames[(intdiv($i - 1, count($firstNames)) + ($i - 1)) % count($lastNames)];
            $name = "{$first} {$last}";
            $slug = 'student'.str_pad((string) $i, $width, '0', STR_PAD_LEFT);

            return $this->makeUser($name, $slug, Role::Student->value, ['first' => $first, 'last' => $last]);
        });
    }

    /**
     * @param  array<string, string>  $extra
     */
    private function makeUser(string $name, string $localPart, string $role, array $extra = []): User
    {
        $user = User::firstOrCreate(
            ['email' => "{$localPart}@{$this->emailDomain}"],
            [
                'name' => $name,
                'password' => $this->passwordHash,
                'locale' => 'en',
                'is_active' => true,
            ],
        );

        // Direct, idempotent pivot insert (avoids spatie's per-call permission-cache reload).
        if (isset($this->roleIds[$role])) {
            DB::table('model_has_roles')->insertOrIgnore([
                'role_id' => $this->roleIds[$role],
                'model_type' => $this->userMorph,
                'model_id' => $user->id,
            ]);
        }

        [$first, $last] = array_pad(explode(' ', $name, 2), 2, '');

        $profile = $user->profile()->firstOrCreate([], [
            'first_name' => $extra['first'] ?? $first,
            'last_name' => $extra['last'] ?? $last,
            'bio' => $extra['bio'] ?? null,
            'avatar_path' => $this->avatarUrl('avatar|'.$localPart),
        ]);

        // Idempotent: backfill a portrait for a profile created before media enrichment existed.
        if ($this->externalMedia && $profile->getAttribute('avatar_path') === null) {
            $profile->forceFill(['avatar_path' => $this->avatarUrl('avatar|'.$localPart)])->save();
        }

        return $user;
    }

    // ----- Taxonomy + Courses + Curriculum -----

    private function seedTaxonomyAndCourses(): void
    {
        /** @var array<string, CourseLevel> $levels */
        $levels = collect(['Beginner', 'Intermediate', 'Advanced'])
            ->mapWithKeys(fn (string $name, int $i): array => [$name => CourseLevel::firstOrCreate(['slug' => Slug::make($name)], ['name' => $name, 'position' => $i])])
            ->all();

        $english = CourseLanguage::firstOrCreate(['code' => 'en'], ['name' => 'English', 'position' => 0]);
        CourseLanguage::firstOrCreate(['code' => 'ar'], ['name' => 'العربية', 'position' => 1]);

        /** @var array<string, Category> $categoryModels */
        $categoryModels = [];
        $position = 0;
        foreach ($this->categories as $slug => $name) {
            $categoryModels[$slug] = Category::firstOrCreate(['slug' => $slug], ['name' => $name, 'position' => $position++]);
        }

        foreach (['strategy', 'leadership', 'growth', 'mena', 'finance', 'ai', 'agile'] as $tag) {
            CourseTag::firstOrCreate(['slug' => Slug::make($tag)], ['name' => ucfirst($tag)]);
        }

        /** @var Collection<int, CourseTag> $tags */
        $tags = CourseTag::query()->get()->keyBy('slug');

        $variants = max(1, min(count($this->cohorts), $this->pint('course_variants', 1)));
        $position = 0;

        foreach ($this->courseBlueprints as $bpIndex => $bp) {
            for ($v = 0; $v < $variants; $v++) {
                $suffix = $this->cohorts[$v] ?? '';
                $title = 'Demo '.$bp['title'].$suffix;
                $slug = Slug::make($title); // => "demo-..."

                $course = Course::firstOrCreate(
                    ['slug' => $slug],
                    [
                        'title' => $title,
                        'subtitle' => $bp['subtitle'],
                        'description' => $bp['subtitle'].' A hands-on, MENA-focused program built for professionals, founders, and teams.',
                        'status' => CourseStatus::Published->value,
                        'visibility' => Visibility::Public->value,
                        'level_id' => $levels[$bp['level']]->id,
                        'language_id' => $english->id,
                        'is_featured' => $bp['featured'] && $v === 0,
                        'position' => $position,
                        'published_at' => now()->subDays($this->ri(30, 900)),
                        'thumbnail_path' => $this->coverUrl($bp['category'], 'cover|'.$slug),
                        'seo' => [
                            'title' => $title,
                            'description' => $bp['subtitle'],
                            // Course has no outcomes/prerequisites/duration/difficulty columns, so
                            // this structured metadata lives in the seo JSON blob.
                            'difficulty' => $bp['level'],
                            'duration_hours' => $this->ri(4, 18),
                            'learning_outcomes' => [
                                'Apply the core framework to a real '.strtolower($bp['category']).' scenario.',
                                'Avoid the two most common mistakes teams make.',
                                'Produce a reusable checklist you can share with your team.',
                            ],
                            'prerequisites' => $bp['level'] === 'Beginner' ? ['None — suitable for newcomers.'] : ['Comfort with the fundamentals of the topic.'],
                            'ar' => [
                                'title' => $bp['title'].' (نسخة تجريبية)',
                                'description' => 'برنامج تطبيقي موجّه لسوق الشرق الأوسط للمحترفين ورواد الأعمال والفرق.',
                            ],
                        ],
                    ],
                );

                // Idempotent media enrichment for pre-existing demo courses (covers added later).
                if ($this->externalMedia && $course->getAttribute('thumbnail_path') === null) {
                    $course->forceFill(['thumbnail_path' => $this->coverUrl($bp['category'], 'cover|'.$slug)])->save();
                }

                $course->categories()->syncWithoutDetaching([$categoryModels[$bp['category']]->id]);
                $tagIds = $this->tagIdsForCategory($bp['category'], $tags);
                if ($tagIds !== []) {
                    $course->tags()->syncWithoutDetaching($tagIds);
                }
                $course->syncTrainers([$this->instructors[$position % $this->instructors->count()]->id]);

                $this->seedCurriculum($course, $position);

                $this->courses->push($course);
                $this->courseBp[] = $bp;
                $position++;
            }
        }
    }

    /**
     * @param  Collection<int, CourseTag>  $tags
     * @return list<int>
     */
    private function tagIdsForCategory(string $category, Collection $tags): array
    {
        $map = [
            'project-management' => ['strategy', 'mena'],
            'agile-mindset' => ['agile', 'growth'],
            'business-development' => ['growth', 'mena'],
            'business-strategies' => ['strategy', 'leadership'],
            'entrepreneurship' => ['growth', 'mena'],
            'business-skills' => ['leadership'],
            'leadership' => ['leadership', 'mena'],
            'marketing-strategies' => ['growth', 'mena'],
            'sales-management' => ['growth', 'leadership'],
            'finance-analysis' => ['finance'],
            'business-ai' => ['ai', 'strategy'],
            'investment-trading' => ['finance'],
        ];

        $ids = [];
        foreach ($map[$category] ?? [] as $slug) {
            $tag = $tags->get($slug);
            if ($tag instanceof CourseTag) {
                $ids[] = (int) $tag->id;
            }
        }

        return $ids;
    }

    private function seedCurriculum(Course $course, int $courseIndex): void
    {
        if (Section::where('course_id', $course->id)->exists()) {
            return;
        }

        $sectionTitles = ['Getting Started', 'Core Concepts', 'Applying It at Work', 'Advanced Techniques', 'Case Studies', 'Measuring Impact', 'Wrap-up & Next Steps'];
        [$secMin, $secMax] = $this->pr('sections_per_course');
        [$lesMin, $lesMax] = $this->pr('lessons_per_section');
        $sectionCount = $this->ri($secMin, $secMax);

        // Deterministic lesson-type rotation: mostly Article/Video, occasional Pdf/Download/
        // ExternalLink, and a few QuizPlaceholder (the ONLY supported representation of a "quiz").
        $typeCycle = [
            LessonType::Video, LessonType::Article, LessonType::Article, LessonType::Pdf,
            LessonType::Video, LessonType::Article, LessonType::QuizPlaceholder, LessonType::Download,
            LessonType::Article, LessonType::ExternalLink,
        ];

        $globalLessonIndex = 0;
        for ($s = 0; $s < $sectionCount; $s++) {
            $section = Section::create([
                'course_id' => $course->id,
                'title' => $sectionTitles[$s % count($sectionTitles)],
                'summary' => 'Orientation and repeatable practice for this part of the program.',
                'position' => $s + 1,
                'publish_state' => PublishState::Published->value,
            ]);

            $lessonCount = $this->ri($lesMin, $lesMax);
            for ($l = 0; $l < $lessonCount; $l++) {
                $isFirstLessonOfCourse = $globalLessonIndex === 0;
                $type = $isFirstLessonOfCourse ? LessonType::Video : $typeCycle[$globalLessonIndex % count($typeCycle)];
                $lessonTitle = $this->lessonTitle($type, $l);

                $lesson = Lesson::create([
                    'section_id' => $section->id,
                    'title' => $lessonTitle,
                    'type' => $type->value,
                    'content' => $this->lessonContent($type, (string) $course->getAttribute('title'), $lessonTitle),
                    'position' => $l + 1,
                    'publish_state' => PublishState::Published->value,
                    'is_preview' => $isFirstLessonOfCourse,
                ]);

                // Video lessons carry a media asset (Mux playback + S3 key placeholders) — these are
                // the demo "media assets". Guarded by lesson_media's unique lesson_id.
                if ($type === LessonType::Video) {
                    LessonMedia::firstOrCreate(
                        ['lesson_id' => $lesson->id],
                        [
                            'mux_playback_id' => 'demo_pb_'.substr(md5('media|'.$lesson->id), 0, 20),
                            's3_key' => 'demo/media/lesson-'.$lesson->id.'.mp4',
                            'mime_type' => 'video/mp4',
                            'duration' => $this->ri(180, 1800),
                            'filesize' => $this->ri(5_000_000, 250_000_000),
                        ],
                    );
                }

                $globalLessonIndex++;
            }
        }
    }

    private function lessonTitle(LessonType $type, int $index): string
    {
        return match ($type) {
            LessonType::Video => 'Watch: '.$this->pick(['Welcome & orientation', 'A worked example', 'Live walkthrough', 'Framework in action']),
            LessonType::Audio => 'Listen: '.$this->pick(['Audio briefing', 'Narrated summary', 'Podcast-style recap', 'On-the-go lesson']),
            LessonType::Pdf => 'Download: workbook & templates',
            LessonType::Download => 'Resource pack',
            LessonType::ExternalLink => 'Further reading (external)',
            LessonType::QuizPlaceholder => 'Knowledge check',
            LessonType::Article => $this->pick(['The core framework', 'Common pitfalls to avoid', 'Your practice checklist', 'A deeper dive', 'Applying it this week']),
        };
    }

    /**
     * Builds ORIGINAL, generated educational content. Articles are rich HTML (headings, lists,
     * tables, callouts, code, key takeaways, exercises). Video lessons carry an external embed
     * reference only when demo.external_media is enabled, and always ship an original reading
     * fallback so the demo is fully usable offline.
     *
     * @return array<string, mixed>
     */
    private function lessonContent(LessonType $type, string $courseTitle, string $lessonTitle): array
    {
        $arabicHtml = '<h2>'.e($lessonTitle).'</h2><p>هذا الدرس ضمن برنامج تدريبي تطبيقي. سوف تتعلّم'
            .' منهجية عملية يمكنك تطبيقها مباشرةً في بيئة الأعمال بالشرق الأوسط.</p>';

        if ($type === LessonType::Article) {
            $rich = '<h2>'.e($lessonTitle).'</h2>'
                .'<p>This lesson is part of <strong>'.e($courseTitle).'</strong>. It gives you a practical,'
                .' step-by-step approach you can apply this week in a MENA business context.</p>'
                .'<h3>What good looks like</h3>'
                .'<ul><li>A concrete example you can copy.</li><li>A repeatable checklist.</li>'
                .'<li>The two mistakes most teams make, and how to avoid them.</li></ul>'
                .'<h3>Quick reference</h3>'
                .'<table><thead><tr><th>Stage</th><th>Goal</th><th>Signal of success</th></tr></thead>'
                .'<tbody><tr><td>Prepare</td><td>Frame the problem</td><td>Agreed definition</td></tr>'
                .'<tr><td>Act</td><td>Run the play</td><td>Evidence, not opinion</td></tr>'
                .'<tr><td>Review</td><td>Learn & adjust</td><td>One improvement shipped</td></tr></tbody></table>'
                .'<blockquote><strong>Callout:</strong> Small, consistent reps beat occasional heroics.</blockquote>'
                .'<pre><code>plan -&gt; do -&gt; check -&gt; act</code></pre>'
                .'<h3>Key takeaways</h3><ul><li>Start from the outcome.</li><li>Make it measurable.</li></ul>'
                .'<h3>Exercise</h3><p>Pick one current task and apply the framework end to end before the next lesson.</p>';

            return ['html' => $rich, 'ar' => ['html' => $arabicHtml]];
        }

        $readingHtml = '<h2>'.e($lessonTitle).'</h2>'
            .'<p>This resource supports <strong>'.e($courseTitle).'</strong>. Use it alongside the rest of'
            .' the section to turn the concept into repeatable practice.</p>'
            .'<ul><li>What to do.</li><li>How to check it worked.</li><li>What to try next.</li></ul>';

        if ($type === LessonType::Video) {
            if ($this->externalMedia) {
                // Deterministic pick from the public educational video pool, keyed per lesson.
                $ref = $this->videoRef('vid|'.$courseTitle.'|'.$lessonTitle);

                return [
                    'provider' => $ref['provider'],
                    'video_id' => $ref['video_id'],
                    'url' => $ref['url'],
                    'embed_url' => $ref['embed_url'],
                    'title' => $ref['title'],
                    'attribution' => $ref['attribution'],
                    'reading_fallback' => $readingHtml,
                    'ar' => ['html' => $arabicHtml],
                ];
            }

            return ['html' => $readingHtml, 'ar' => ['html' => $arabicHtml]];
        }

        if ($type === LessonType::QuizPlaceholder) {
            // Represented ONLY as a placeholder lesson — the repo has no Quiz model/table.
            return [
                'html' => '<h2>'.e($lessonTitle).'</h2><p>A short knowledge check for this section. '
                    .'This is a placeholder lesson; the platform has no quiz engine yet.</p>',
                'placeholder' => 'quiz',
                'ar' => ['html' => $arabicHtml],
            ];
        }

        if ($type === LessonType::ExternalLink) {
            return ['html' => $readingHtml, 'url' => 'https://example.org/further-reading', 'ar' => ['html' => $arabicHtml]];
        }

        // Pdf / Download
        return ['html' => $readingHtml, 's3_key' => 'demo/resources/'.md5($lessonTitle).'.pdf', 'ar' => ['html' => $arabicHtml]];
    }

    private function cacheLessons(): void
    {
        $courseIds = $this->courses->pluck('id')->all();
        $sectionMap = DB::table('course_sections')->whereIn('course_id', $courseIds)->pluck('course_id', 'id'); // section_id => course_id
        $sectionIds = $sectionMap->keys()->all();

        $lessons = DB::table('lessons')
            ->whereIn('section_id', $sectionIds)
            ->where('publish_state', PublishState::Published->value)
            ->whereNull('deleted_at')
            ->orderBy('section_id')->orderBy('position')
            ->get(['id', 'section_id']);

        foreach ($lessons as $row) {
            $courseId = (int) $sectionMap[$row->section_id];
            $this->lessonsByCourse[$courseId][] = (int) $row->id;
        }
    }

    // ----- Commerce catalog (products, prices, coupons) -----

    private function seedCommerceCatalog(): void
    {
        foreach ($this->courses as $index => $course) {
            $bp = $this->courseBp[$index];
            if (! $bp['priced']) {
                continue;
            }

            $product = Product::firstOrCreate(
                ['slug' => 'demo-'.Slug::make((string) $course->getAttribute('title'))],
                [
                    'type' => ProductType::Course->value,
                    'title' => (string) $course->getAttribute('title'),
                    'description' => (string) $course->getAttribute('subtitle'),
                    'status' => ProductStatus::Active->value,
                ],
            );

            $product->courses()->syncWithoutDetaching([$course->id]);

            if ($product->prices()->doesntExist()) {
                $attributes = [
                    'currency' => $this->currency,
                    'amount_minor' => $bp['price'],
                    'is_default' => true,
                ];
                if ($bp['sale'] !== null) {
                    $attributes['sale_amount_minor'] = $bp['sale'];
                }
                $product->prices()->create($attributes);
            }
        }

        // Base coupons (kept stable across scales), then scale up to the profile target.
        Coupon::firstOrCreate(['code' => 'DEMO25'], [
            'type' => CouponType::Percentage->value, 'value' => 25, 'scope' => CouponScope::All->value,
            'is_active' => true, 'starts_at' => now()->subMonth(), 'ends_at' => now()->addMonths(3),
        ]);
        Coupon::firstOrCreate(['code' => 'DEMOSAVE'], [
            'type' => CouponType::Fixed->value, 'value' => 5000, 'currency' => $this->currency, 'scope' => CouponScope::All->value,
            'is_active' => true, 'starts_at' => now()->subMonth(), 'ends_at' => now()->addMonths(3),
        ]);

        $target = max(2, $this->pint('coupons', 2));
        for ($i = 3; $i <= $target; $i++) {
            $code = 'DEMO'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $percentage = $i % 2 === 0;
            Coupon::firstOrCreate(['code' => $code], [
                'type' => ($percentage ? CouponType::Percentage : CouponType::Fixed)->value,
                'value' => $percentage ? $this->deterministicValue('cpn|'.$code, 10, 40) : $this->deterministicValue('cpn|'.$code, 2000, 8000),
                'currency' => $percentage ? null : $this->currency,
                'scope' => CouponScope::All->value,
                'max_redemptions' => $this->deterministicValue('cpnmax|'.$code, 50, 500),
                'is_active' => $i % 5 !== 0,
                'starts_at' => now()->subMonths($this->deterministicValue('cpns|'.$code, 1, 10)),
                'ends_at' => now()->addMonths(3),
            ]);
        }
    }

    /**
     * @return Collection<int, Product> demo products keyed by their (single) course id
     */
    private function demoProductsByCourse(): Collection
    {
        return Product::query()
            ->where('slug', 'like', 'demo-%')
            ->with('courses')
            ->get()
            ->mapWithKeys(function (Product $product): array {
                $course = $product->courses->first();

                return $course !== null ? [(int) $course->getKey() => $product] : [];
            });
    }

    // ----- Enrollments + progress -----

    private function seedEnrollmentsAndProgress(): void
    {
        $studentList = $this->students->values();
        $studentCount = $studentList->count();
        $courseList = $this->courses->values();
        $courseCount = $courseList->count();
        if ($studentCount === 0 || $courseCount === 0) {
            return;
        }

        [$enrMin, $enrMax] = $this->pr('enrollments_per_student');
        $completionRate = (float) ($this->p['completion_rate'] ?? 0.3);
        $actionCount = min($this->actionStudents, $studentCount);

        // (1) Representative SUBSET driven through the real Actions so events, metrics, notifications
        // and certificates genuinely fire.
        $grant = app(GrantEnrollmentAction::class);
        $record = app(RecordLessonProgressAction::class);
        for ($s = 0; $s < $actionCount; $s++) {
            $student = $studentList[$s];
            $take = min(3, $courseCount);
            for ($k = 0; $k < $take; $k++) {
                $course = $courseList[($s + $k) % $courseCount];
                $bp = $this->courseBp[($s + $k) % $courseCount];
                $source = $bp['priced'] ? EnrollmentSource::Purchase : EnrollmentSource::Free;
                $enrollment = $grant->executeByUserId((int) $student->id, (int) $course->id, $source);
                if ($enrollment->getAttribute('status') === EnrollmentStatus::Completed) {
                    continue;
                }
                $lessons = $this->lessonsByCourse[(int) $course->id] ?? [];
                $mode = ($s + $k) % 3;
                $toComplete = $mode === 0 ? $lessons : ($mode === 1 ? array_slice($lessons, 0, (int) ceil(count($lessons) / 2)) : []);
                foreach ($toComplete as $lessonId) {
                    $record->executeByUserId((int) $student->id, (int) $lessonId, LessonProgressStatus::Completed);
                }
            }
        }

        // (2) BULK volume for the remaining students via chunked, event-free inserts. Guard by count:
        // if the demo already holds the expected floor of enrollments, skip regeneration (no-op re-run).
        $demoUserIds = $this->demoUserIds();
        $existing = DB::table('enrollments')->whereIn('user_id', $demoUserIds)->count();
        $floor = (int) ($studentCount * $enrMin * 0.8);
        if ($existing >= $floor && $existing > 0 && $actionCount < $studentCount) {
            // Only skip the bulk phase if it has clearly already run.
            if ($existing >= $studentCount) {
                return;
            }
        }

        $now = now();
        $enrollRows = [];
        /** @var array<string, array{mode:int,frac:float,enrolled_at:Carbon,completed_at:?Carbon}> $plan keyed uid-cid */
        $plan = [];
        for ($s = $actionCount; $s < $studentCount; $s++) {
            $student = $studentList[$s];
            $n = $this->ri($enrMin, $enrMax);
            for ($k = 0; $k < $n; $k++) {
                $course = $courseList[($s * 3 + $k) % $courseCount];
                $cid = (int) $course->id;
                $uid = (int) $student->id;
                $key = $uid.'-'.$cid;
                if (isset($plan[$key])) {
                    continue;
                }
                $bp = $this->courseBp[($s * 3 + $k) % $courseCount];
                $enrolledAt = (clone $now)->subDays($this->ri(1, max(1, min(720, 365))));
                $r = (crc32('mode|'.$key) % 1000) / 1000;
                if ($r < $completionRate) {
                    $mode = 2; // completed
                    $frac = 1.0;
                    $completedAt = (clone $now)->subDays($this->ri(0, 40));
                    $status = EnrollmentStatus::Completed->value;
                    $pct = 100;
                } elseif ($r < $completionRate + 0.4) {
                    $mode = 1; // in progress
                    $frac = $this->ri(15, 85) / 100;
                    $completedAt = null;
                    $status = EnrollmentStatus::Active->value;
                    $pct = (int) round($frac * 100);
                } else {
                    $mode = 0; // just enrolled
                    $frac = 0.0;
                    $completedAt = null;
                    $status = EnrollmentStatus::Active->value;
                    $pct = 0;
                }
                $plan[$key] = ['mode' => $mode, 'frac' => $frac, 'enrolled_at' => $enrolledAt, 'completed_at' => $completedAt];
                $enrollRows[] = [
                    'public_id' => Uuid::v7(),
                    'user_id' => $uid,
                    'course_id' => $cid,
                    'status' => $status,
                    'source' => ($bp['priced'] ? EnrollmentSource::Purchase : EnrollmentSource::Free)->value,
                    'progress_percentage' => $pct,
                    'enrolled_at' => $enrolledAt,
                    'completed_at' => $completedAt,
                    'created_at' => $enrolledAt,
                    'updated_at' => $now,
                ];
            }
        }
        $this->bulk('enrollments', $enrollRows, true);

        // Map (user,course) => enrollment id for the freshly-inserted bulk rows, then build progress.
        $idMap = DB::table('enrollments')
            ->whereIn('user_id', $demoUserIds)
            ->get(['id', 'user_id', 'course_id'])
            ->mapWithKeys(fn ($r): array => [$r->user_id.'-'.$r->course_id => (int) $r->id]);

        $progressRows = [];
        foreach ($plan as $key => $meta) {
            if ($meta['mode'] === 0) {
                continue;
            }
            $enrollmentId = $idMap[$key] ?? null;
            if ($enrollmentId === null) {
                continue;
            }
            [, $cid] = array_map('intval', explode('-', $key));
            $lessons = $this->lessonsByCourse[$cid] ?? [];
            if ($lessons === []) {
                continue;
            }
            $complete = $meta['mode'] === 2 ? count($lessons) : (int) floor($meta['frac'] * count($lessons));
            for ($li = 0; $li < $complete; $li++) {
                $completedAt = $meta['completed_at'] ?? (clone $meta['enrolled_at'])->addDays($this->ri(1, 20));
                $progressRows[] = [
                    'enrollment_id' => $enrollmentId,
                    'lesson_id' => $lessons[$li],
                    'status' => LessonProgressStatus::Completed->value,
                    'position_seconds' => null,
                    'completed_at' => $completedAt,
                    'created_at' => $meta['enrolled_at'],
                    'updated_at' => $completedAt,
                ];
            }
            // One in-progress lesson for realism on partial enrollments.
            if ($meta['mode'] === 1 && $complete < count($lessons)) {
                $progressRows[] = [
                    'enrollment_id' => $enrollmentId,
                    'lesson_id' => $lessons[$complete],
                    'status' => LessonProgressStatus::InProgress->value,
                    'position_seconds' => $this->ri(30, 600),
                    'completed_at' => null,
                    'created_at' => $meta['enrolled_at'],
                    'updated_at' => now(),
                ];
            }
            if (count($progressRows) >= 5000) {
                $this->bulk('lesson_progress', $progressRows, true);
                $progressRows = [];
            }
        }
        $this->bulk('lesson_progress', $progressRows, true);
    }

    // ----- Certificates (bulk from completions + revoke/reissue subset via Actions) -----

    private function seedCertificates(): void
    {
        $demoUserIds = $this->demoUserIds();
        if ($demoUserIds === []) {
            return;
        }

        // Existing (user,course) certs (issued by the completion listener for the action subset).
        $have = DB::table('certificates')
            ->whereIn('user_id', $demoUserIds)
            ->get(['user_id', 'course_id'])
            ->mapWithKeys(fn ($r): array => [$r->user_id.'-'.$r->course_id => true]);

        $completed = DB::table('enrollments')
            ->whereIn('user_id', $demoUserIds)
            ->where('status', EnrollmentStatus::Completed->value)
            ->get(['id', 'user_id', 'course_id', 'completed_at']);

        $now = now();
        $rows = [];
        foreach ($completed as $e) {
            $key = $e->user_id.'-'.$e->course_id;
            if (isset($have[$key])) {
                continue;
            }
            $issuedAt = $e->completed_at ?? (clone $now)->subDays($this->ri(1, 300));
            $rows[] = [
                'public_id' => Uuid::v7(),
                'user_id' => (int) $e->user_id,
                'course_id' => (int) $e->course_id,
                'enrollment_id' => (int) $e->id,
                'template_id' => null,
                'number' => 'DEMO-CERT-'.$e->user_id.'-'.$e->course_id,
                'verification_code' => strtoupper(substr(md5('cert|'.$key.'|'.$this->seed), 0, 16)),
                'status' => CertificateStatus::Issued->value,
                'signature_name' => 'Academy Director',
                'signature_title' => 'Director',
                'signature_hash' => substr(hash('sha256', 'sig|'.$key), 0, 64),
                'issued_at' => $issuedAt,
                'created_at' => $issuedAt,
                'updated_at' => $now,
            ];
        }
        $this->bulk('certificates', $rows, true);

        // Revoke a small deterministic subset via the real Action (audit + event), then reissue an
        // even smaller disjoint subset. Bounded counts keep runtime predictable at every scale.
        $revoke = app(RevokeCertificateAction::class);
        $reissue = app(ReissueCertificateAction::class);
        $certs = Certificate::query()->whereIn('user_id', $demoUserIds)->orderBy('id')->get();
        $revokeCap = min(30, (int) floor($certs->count() * 0.06));
        $reissueCap = min(15, (int) floor($certs->count() * 0.03));
        // Count-guarded so re-runs never revoke/reissue an additional certificate: only act on the
        // shortfall between the target caps and what the demo already holds.
        $alreadyRevoked = $certs->where(fn (Certificate $c): bool => $c->getAttribute('status') === CertificateStatus::Revoked)->count();
        $alreadyReissued = $certs->filter(fn (Certificate $c): bool => $c->getAttribute('reissued_at') !== null)->count();
        $toRevoke = max(0, $revokeCap - $alreadyRevoked);
        $toReissue = max(0, $reissueCap - $alreadyReissued);
        foreach ($certs as $i => $cert) {
            if ($toRevoke > 0 && $i % 17 === 0 && $cert->getAttribute('status') === CertificateStatus::Issued && $cert->getAttribute('reissued_at') === null) {
                $revoke->execute($cert);
                $toRevoke--;

                continue;
            }
            if ($toReissue > 0 && $i % 23 === 0 && $cert->getAttribute('status') === CertificateStatus::Issued) {
                $reissue->execute($cert);
                $toReissue--;
            }
        }
    }

    // ----- Engagement (bookmarks + notes) -----

    private function seedEngagement(): void
    {
        $studentList = $this->students->values();
        $studentCount = $studentList->count();
        if ($studentCount === 0) {
            return;
        }
        $actionCount = min($this->actionStudents, $studentCount);

        // Action subset via the real Actions (idempotent).
        $bookmark = app(ToggleBookmarkAction::class);
        $note = app(UpsertLessonNoteAction::class);
        for ($s = 0; $s < $actionCount; $s++) {
            $student = $studentList[$s];
            $course = $this->courses[$s % $this->courses->count()];
            $lessonId = ($this->lessonsByCourse[(int) $course->id] ?? [null])[0];
            if ($lessonId === null) {
                continue;
            }
            if (DB::table('lesson_bookmarks')->where('user_id', $student->id)->where('lesson_id', $lessonId)->doesntExist()) {
                $bookmark->executeByUserId((int) $student->id, (int) $lessonId);
            }
            $note->executeByUserId((int) $student->id, (int) $lessonId, 'Great intro - revisit the checklist before the next session.');
        }

        // Bulk volume, guarded by count.
        $demoUserIds = $this->demoUserIds();
        [$bMin, $bMax] = $this->pr('bookmarks_per_student');
        [$nMin, $nMax] = $this->pr('notes_per_student');
        $allLessons = [];
        foreach ($this->lessonsByCourse as $ids) {
            foreach ($ids as $id) {
                $allLessons[] = $id;
            }
        }
        if ($allLessons === []) {
            return;
        }

        if (DB::table('lesson_bookmarks')->whereIn('user_id', $demoUserIds)->count() < $studentCount * $bMin) {
            $rows = [];
            for ($s = $actionCount; $s < $studentCount; $s++) {
                $uid = (int) $studentList[$s]->id;
                $count = $this->ri($bMin, $bMax);
                for ($b = 0; $b < $count; $b++) {
                    $lessonId = $allLessons[($s * 7 + $b * 13) % count($allLessons)];
                    $rows[] = ['public_id' => Uuid::v7(), 'user_id' => $uid, 'lesson_id' => $lessonId, 'created_at' => now()->subDays($this->ri(0, 200)), 'updated_at' => now()];
                }
            }
            $this->bulk('lesson_bookmarks', $rows, true);
        }

        if (DB::table('lesson_notes')->whereIn('user_id', $demoUserIds)->count() < $studentCount * $nMin) {
            $noteBodies = ['Key idea — apply on the current project.', 'Revisit this before the workshop.', 'Great checklist, saved for the team.', 'Need to practice this step.', 'Ask the mentor about this in office hours.'];
            $rows = [];
            for ($s = $actionCount; $s < $studentCount; $s++) {
                $uid = (int) $studentList[$s]->id;
                $count = $this->ri($nMin, $nMax);
                for ($b = 0; $b < $count; $b++) {
                    $lessonId = $allLessons[($s * 11 + $b * 5) % count($allLessons)];
                    $rows[] = ['public_id' => Uuid::v7(), 'user_id' => $uid, 'lesson_id' => $lessonId, 'body' => $this->pick($noteBodies), 'created_at' => now()->subDays($this->ri(0, 200)), 'updated_at' => now()];
                }
            }
            // lesson_notes has no unique (user,lesson); dedupe within this batch to stay idempotent-ish.
            $seen = [];
            $rows = array_values(array_filter($rows, function (array $r) use (&$seen): bool {
                $k = $r['user_id'].'-'.$r['lesson_id'];
                if (isset($seen[$k])) {
                    return false;
                }
                $seen[$k] = true;

                return true;
            }));
            $this->bulk('lesson_notes', $rows, false);
        }
    }

    // ----- Orders + items + invoices + transactions + coupons + refunds -----

    private function seedOrders(): void
    {
        $productsByCourse = $this->demoProductsByCourse();
        if ($productsByCourse->isEmpty()) {
            return;
        }
        /** @var list<Product> $products */
        $products = $productsByCourse->values()->all();
        $productCount = count($products);
        $studentList = $this->students->values();
        $studentCount = $studentList->count();
        $demoUserIds = $this->demoUserIds();

        $target = $this->pint('orders', 36);
        $existing = DB::table('orders')->whereIn('user_id', $demoUserIds)->count();
        if ($existing >= $target) {
            return; // guard: area already seeded.
        }

        // Coupon pool for redemptions.
        $coupons = DB::table('coupons')->where('code', 'like', 'DEMO%')->get(['id', 'type', 'value'])->all();

        $now = now();
        /** @var list<array<string,mixed>> $plan */
        $plan = [];
        for ($i = 0; $i < $target; $i++) {
            $student = $studentList[$i % $studentCount];
            $product = $products[($i * 3) % $productCount];
            $price = $product->prices()->orderByDesc('is_default')->first();
            $amount = $price !== null ? (int) ($price->getAttribute('sale_amount_minor') ?? $price->getAttribute('amount_minor')) : 19900;

            $r = crc32('ostat|'.$i) % 100;
            if ($r < 72) {
                $status = OrderStatus::Paid;
            } elseif ($r < 84) {
                $status = OrderStatus::Pending;
            } elseif ($r < 92) {
                $status = OrderStatus::Cancelled;
            } else {
                $status = OrderStatus::Failed;
            }

            $useCoupon = $coupons !== [] && $status === OrderStatus::Paid && $i % 2 === 0;
            $couponId = null;
            $discount = 0;
            if ($useCoupon) {
                $c = $coupons[$i % count($coupons)];
                $couponId = (int) $c->id;
                $discount = $c->type === CouponType::Percentage->value ? (int) round($amount * ((int) $c->value) / 100) : min($amount, (int) $c->value);
            }
            $total = max(0, $amount - $discount);
            $placedAt = (clone $now)->subDays($this->ri(1, 400));
            $paidAt = in_array($status, [OrderStatus::Paid, OrderStatus::Refunded], true) ? (clone $placedAt)->addHours($this->ri(1, 48)) : null;

            $plan[] = [
                'user_id' => (int) $student->id,
                'product_id' => (int) $product->id,
                'title' => (string) $product->getAttribute('title'),
                'amount' => $amount,
                'discount' => $discount,
                'total' => $total,
                'status' => $status,
                'coupon_id' => $couponId,
                'placed_at' => $placedAt,
                'paid_at' => $paidAt,
            ];
        }

        // Insert orders, then re-read ids in insertion order to attach children.
        $orderRows = array_map(fn (array $o): array => [
            'public_id' => Uuid::v7(),
            'user_id' => $o['user_id'],
            'status' => $o['status']->value,
            'currency' => $this->currency,
            'subtotal_minor' => $o['amount'],
            'discount_minor' => $o['discount'],
            'total_minor' => $o['total'],
            'coupon_id' => $o['coupon_id'],
            'placed_at' => $o['placed_at'],
            'paid_at' => $o['paid_at'],
            'created_at' => $o['placed_at'],
            'updated_at' => $now,
        ], $plan);
        $this->bulk('orders', $orderRows, false);

        $orderIds = DB::table('orders')->whereIn('user_id', $demoUserIds)->orderBy('id')->pluck('id')->all();
        // Align to the plan we just inserted (last N by insertion order).
        $orderIds = array_slice($orderIds, -count($plan));

        $items = [];
        $invoices = [];
        $transactions = [];
        $redemptions = [];
        $invSeq = 0;
        $year = (int) $now->format('Y');
        foreach ($plan as $idx => $o) {
            $orderId = (int) $orderIds[$idx];
            $items[] = ['public_id' => Uuid::v7(), 'order_id' => $orderId, 'product_id' => $o['product_id'], 'title' => $o['title'], 'unit_amount_minor' => $o['amount'], 'created_at' => $o['placed_at'], 'updated_at' => $now];

            if ($o['status'] === OrderStatus::Paid) {
                $invSeq++;
                $invoices[] = [
                    'public_id' => Uuid::v7(), 'order_id' => $orderId, 'number' => sprintf('DEMO-INV-%d-%06d', $year, $invSeq),
                    'status' => InvoiceStatus::Paid->value, 'currency' => $this->currency, 'total_minor' => $o['total'],
                    'issued_at' => $o['paid_at'], 'paid_at' => $o['paid_at'], 'created_at' => $o['paid_at'], 'updated_at' => $now,
                ];
                $transactions[] = [
                    'public_id' => Uuid::v7(), 'order_id' => $orderId, 'provider' => 'fake', 'provider_reference' => 'fake_ch_'.substr(md5('ch|'.$orderId), 0, 20),
                    'type' => TransactionType::Charge->value, 'status' => TransactionStatus::Succeeded->value, 'amount_minor' => $o['total'], 'currency' => $this->currency,
                    'created_at' => $o['paid_at'], 'updated_at' => $now,
                ];
                if ($o['coupon_id'] !== null) {
                    $redemptions[] = ['coupon_id' => $o['coupon_id'], 'user_id' => $o['user_id'], 'order_id' => $orderId, 'created_at' => $o['paid_at'], 'updated_at' => $now];
                }
            }
        }
        $this->bulk('order_items', $items, false);
        $this->bulk('invoices', $invoices, false);
        $this->bulk('payment_transactions', $transactions, false);
        $this->bulk('coupon_redemptions', $redemptions, false);

        // Refunds via the real RefundOrderAction on a subset of paid orders (needs a succeeded charge,
        // which we just created). This produces genuine refund transactions + audit + events.
        $refundRate = (float) ($this->p['refund_rate'] ?? 0.05);
        $refundTarget = max(1, (int) round($target * $refundRate));
        $refund = app(RefundOrderAction::class);
        $paidOrders = Order::query()->whereIn('user_id', $demoUserIds)->where('status', OrderStatus::Paid->value)->orderBy('id')->limit($refundTarget)->get();
        foreach ($paidOrders as $order) {
            try {
                $refund->execute($order);
            } catch (\Throwable) {
                // Non-refundable states are simply skipped; the demo never forces an impossible state.
            }
        }
    }

    // ----- Organizations (departments, teams, members, seat pools) -----

    private function seedOrganizations(): void
    {
        $industries = ['Technology', 'Healthcare', 'Education', 'Government', 'Manufacturing', 'Retail', 'Finance', 'Logistics'];
        $sizes = ['small', 'medium', 'large', 'enterprise'];
        $target = max(1, $this->pint('organizations', 1));
        [$memMin, $memMax] = $this->pr('members_per_org');
        $studentList = $this->students->values();
        $studentCount = $studentList->count();
        $now = now();

        for ($o = 0; $o < $target; $o++) {
            $slug = 'demo-org-'.str_pad((string) ($o + 1), 3, '0', STR_PAD_LEFT);
            $industry = $industries[$o % count($industries)];
            $org = Organization::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => 'Demo '.$industry.' Group '.($o + 1),
                    'status' => 'active',
                    'size' => $sizes[$o % count($sizes)],
                    'website' => 'https://'.$slug.'.demo.local',
                ],
            );
            $orgId = (int) $org->id;

            // Departments + teams (guard by existence — no natural unique key).
            if (DB::table('crm_departments')->where('organization_id', $orgId)->doesntExist()) {
                $deptNames = ['Operations', 'People', 'Commercial'];
                $deptRows = array_map(fn (string $n): array => ['public_id' => Uuid::v7(), 'organization_id' => $orgId, 'name' => $n, 'created_at' => $now, 'updated_at' => $now], $deptNames);
                DB::table('crm_departments')->insert($deptRows);
                $deptIds = DB::table('crm_departments')->where('organization_id', $orgId)->pluck('id')->all();
                $teamNames = ['Alpha Team', 'Beta Team', 'Delivery Squad', 'Enablement'];
                $teamRows = [];
                foreach ($teamNames as $ti => $tn) {
                    $teamRows[] = ['public_id' => Uuid::v7(), 'organization_id' => $orgId, 'department_id' => $deptIds[$ti % count($deptIds)], 'name' => $tn, 'created_at' => $now, 'updated_at' => $now];
                }
                DB::table('crm_teams')->insert($teamRows);
            }

            // Seat pool.
            if (DB::table('seat_pools')->where('organization_id', $orgId)->doesntExist()) {
                $totalSeats = $this->ri($memMax, $memMax + 20);
                DB::table('seat_pools')->insert(['public_id' => Uuid::v7(), 'organization_id' => $orgId, 'name' => 'Corporate License', 'total_seats' => $totalSeats, 'used_seats' => $this->ri(0, $memMin), 'created_at' => $now, 'updated_at' => $now]);
            }

            // Members: a few mapped to real demo students (organization learning), rest synthetic.
            // Deterministic count + deterministic per-(org,index) email so re-runs match on the
            // (organization_id, email) unique key and never create additional members.
            $memberN = $this->deterministicValue('members|'.$slug, $memMin, $memMax);
            for ($m = 0; $m < $memberN; $m++) {
                $mappedStudent = $m < 3 && $studentCount > 0 ? $studentList[($o * 3 + $m) % $studentCount] : null;
                $email = $mappedStudent !== null ? (string) $mappedStudent->getAttribute('email') : 'member-'.($o + 1).'-'.($m + 1).'@'.$this->emailDomain;
                $role = $m === 0 ? MemberRole::Owner : ($m <= 2 ? MemberRole::Manager : MemberRole::Member);
                OrganizationMember::firstOrCreate(
                    ['organization_id' => $orgId, 'email' => $email],
                    [
                        'user_id' => $mappedStudent?->id,
                        'role' => $role->value,
                        'status' => MemberStatus::Active->value,
                        'joined_at' => (clone $now)->subDays($this->ri(10, 600)),
                    ],
                );
            }
        }
    }

    // ----- Live -----

    private function seedLive(): void
    {
        $liveCourse = LiveCourse::firstOrCreate(
            ['title' => 'Demo Live Masterclass Series'],
            ['timezone' => 'Asia/Riyadh', 'is_active' => true],
        );
        $liveCourseId = (int) $liveCourse->id;

        $target = max(1, $this->pint('live_sessions', 2));
        $now = now();

        // (1) Action subset via ScheduleSessionAction (fires SessionScheduled; future/scheduled).
        $schedule = app(ScheduleSessionAction::class);
        $actionSessions = min(2, $target);
        for ($i = 0; $i < $actionSessions; $i++) {
            $title = 'Demo Live: '.['Leading Change in MENA Teams', 'Building an AI-Ready Roadmap'][$i];
            if (LiveSession::where('title', $title)->exists()) {
                continue;
            }
            $schedule->execute([
                'live_course_id' => $liveCourseId,
                'title' => $title,
                'timezone' => 'Asia/Riyadh',
                'starts_at' => now()->addWeeks($i + 1)->setTime(18 + $i, 0)->format('Y-m-d H:i'),
                'duration_minutes' => 90,
                'capacity' => 100,
            ]);
        }

        // (2) Bulk sessions across upcoming/completed/cancelled, guarded by count (the action
        // sessions above already count toward the target).
        $existing = DB::table('live_sessions')->where('live_course_id', $liveCourseId)->count();
        if ($existing < $target) {
            $rows = [];
            $topics = ['Stakeholder Alignment', 'Data Storytelling Clinic', 'Negotiation Role-play', 'Agile Retrospective Lab', 'Finance for Founders', 'Marketing Funnels Deep-dive', 'AI Prompting Workshop', 'Sales Forecasting'];
            for ($i = $existing; $i < $target; $i++) {
                $r = crc32('live|'.$i) % 100;
                if ($r < 55) {
                    $status = LiveSessionStatus::Completed;
                    $starts = (clone $now)->subDays($this->ri(3, 300))->setTime($this->ri(9, 19), 0);
                } elseif ($r < 85) {
                    $status = LiveSessionStatus::Scheduled;
                    $starts = (clone $now)->addDays($this->ri(2, 90))->setTime($this->ri(9, 19), 0);
                } else {
                    $status = LiveSessionStatus::Cancelled;
                    $starts = (clone $now)->addDays($this->ri(2, 60))->setTime($this->ri(9, 19), 0);
                }
                // Realistic meeting provider + join URL (cancelled sessions carry none).
                $provider = $this->pick(['zoom', 'google_meet', 'teams']);
                $meetingId = substr(md5('meet|'.$liveCourseId.'|'.$i), 0, 10);
                $joinUrl = $status === LiveSessionStatus::Cancelled ? null : match ($provider) {
                    'google_meet' => 'https://meet.google.com/'.substr($meetingId, 0, 3).'-'.substr($meetingId, 3, 4).'-'.substr($meetingId, 7, 3),
                    'teams' => 'https://teams.microsoft.com/l/meetup-join/'.$meetingId,
                    default => 'https://us06web.zoom.us/j/'.$this->deterministicValue('zoom|'.$i, 10_000_000_000, 99_999_999_999),
                };
                $rows[] = [
                    'public_id' => Uuid::v7(), 'live_course_id' => $liveCourseId, 'title' => 'Demo Live: '.$topics[$i % count($topics)].' #'.($i + 1),
                    'description' => 'A practical, interactive live session for the demo cohort.',
                    'status' => $status->value, 'timezone' => 'Asia/Riyadh', 'starts_at' => $starts, 'ends_at' => (clone $starts)->addMinutes(90),
                    'capacity' => $this->pick([30, 50, 80, 100, 150]), 'waiting_room' => true,
                    'meeting_provider' => $joinUrl === null ? null : $provider,
                    'meeting_external_id' => $joinUrl === null ? null : $meetingId,
                    'join_url' => $joinUrl,
                    'created_at' => $now, 'updated_at' => $now,
                ];
            }
            $this->bulk('live_sessions', $rows, false);
        }

        // Registrations (+ waitlist beyond capacity), attendance, recordings.
        $studentList = $this->students->values();
        $studentCount = $studentList->count();
        if ($studentCount === 0) {
            return;
        }
        [$regMin, $regMax] = $this->pr('registrations_per_session');
        $sessions = DB::table('live_sessions')->where('live_course_id', $liveCourseId)->orderBy('id')->get(['id', 'status', 'capacity', 'starts_at']);

        $regRows = [];
        $attRows = [];
        $recRows = [];
        foreach ($sessions as $si => $session) {
            $sid = (int) $session->id;
            if (DB::table('session_registrations')->where('session_id', $sid)->count() >= $regMin) {
                continue; // guard per session
            }
            $capacity = $session->capacity !== null ? (int) $session->capacity : 100;
            $regCount = $this->ri($regMin, $regMax);
            $registered = 0;
            for ($m = 0; $m < $regCount; $m++) {
                $student = $studentList[($si * 5 + $m) % $studentCount];
                $uid = (int) $student->id;
                $status = $registered < $capacity ? RegistrationStatus::Registered : RegistrationStatus::Waitlisted;
                $registeredAt = (clone $now)->subDays($this->ri(1, 60));
                $regRows[] = ['public_id' => Uuid::v7(), 'session_id' => $sid, 'user_id' => $uid, 'status' => $status->value, 'registered_at' => $registeredAt, 'created_at' => $registeredAt, 'updated_at' => $now];

                // Attendance for completed sessions (~70% of the registered actually attended).
                if ($session->status === LiveSessionStatus::Completed->value && $status === RegistrationStatus::Registered && ($m % 10) < 7) {
                    $joined = Carbon::parse($session->starts_at);
                    $dur = $this->ri(1800, 5400);
                    $attRows[] = ['public_id' => Uuid::v7(), 'session_id' => $sid, 'user_id' => $uid, 'source' => AttendanceSource::SelfJoin->value, 'joined_at' => $joined, 'left_at' => (clone $joined)->addSeconds($dur), 'duration_seconds' => $dur, 'created_at' => $now, 'updated_at' => $now];
                }
                $registered++;
            }

            if ($session->status === LiveSessionStatus::Completed->value && DB::table('session_recordings')->where('session_id', $sid)->doesntExist()) {
                $recRows[] = ['public_id' => Uuid::v7(), 'session_id' => $sid, 'provider' => 'fake', 'external_id' => 'rec_'.substr(md5('rec|'.$sid), 0, 16), 'url' => 'https://recordings.demo.local/'.$sid, 'duration_seconds' => $this->ri(2400, 5400), 'status' => RecordingStatus::Available->value, 'recorded_at' => $session->starts_at, 'created_at' => $now, 'updated_at' => $now];
            }

            if (count($regRows) >= 2000) {
                $this->bulk('session_registrations', $regRows, true);
                $regRows = [];
            }
        }
        $this->bulk('session_registrations', $regRows, true);
        $this->bulk('session_attendances', $attRows, true);
        $this->bulk('session_recordings', $recRows, false);
    }

    // ----- CRM (companies, contacts, leads, opportunities, activities, notes) -----

    private function seedCrm(): void
    {
        /** @var array<string,int> $crm */
        $crm = (array) ($this->p['crm'] ?? []);
        $companyTarget = (int) ($crm['companies'] ?? 4);
        $demoOrgIds = DB::table('crm_organizations')->where('slug', 'like', 'demo-%')->pluck('id')->all();
        if ($demoOrgIds === []) {
            return;
        }

        // Guard by company count (companies are the CRM anchor; org-scoped so they are demo-marked).
        $existingCompanies = DB::table('crm_companies')->whereIn('organization_id', $demoOrgIds)->count();

        $pipeline = Pipeline::where('is_default', true)->orderBy('id')->first() ?? Pipeline::orderBy('id')->first();
        $stages = $pipeline !== null ? $pipeline->stages()->orderBy('position')->get() : collect();
        $now = now();
        $industries = ['Technology', 'Healthcare', 'Education', 'Government', 'Manufacturing', 'Retail', 'Finance', 'Logistics'];

        // Companies (tenant-scoped: organization_id passed explicitly).
        if ($existingCompanies < $companyTarget) {
            $rows = [];
            for ($i = $existingCompanies; $i < $companyTarget; $i++) {
                $rows[] = ['public_id' => Uuid::v7(), 'organization_id' => $demoOrgIds[$i % count($demoOrgIds)], 'name' => 'Demo '.$industries[$i % count($industries)].' Co. '.($i + 1), 'website' => 'https://company'.($i + 1).'.demo.local', 'industry' => $industries[$i % count($industries)], 'size' => $this->pick(['small', 'medium', 'large']), 'created_at' => $now, 'updated_at' => $now];
            }
            $this->bulk('crm_companies', $rows, false);
        }
        $companyIds = DB::table('crm_companies')->whereIn('organization_id', $demoOrgIds)->orderBy('id')->pluck('id')->all();
        if ($companyIds === []) {
            return;
        }

        // Contacts.
        $contactTarget = (int) ($crm['contacts'] ?? 8);
        if (DB::table('crm_contacts')->whereIn('company_id', $companyIds)->count() < $contactTarget) {
            $firsts = ['Omar', 'Sara', 'Ziad', 'Mona', 'Hassan', 'Reem', 'Tariq', 'Dina', 'Karim', 'Layla'];
            $lasts = ['Haddad', 'Nasser', 'Kanaan', 'Saleh', 'Barakat', 'Younis', 'Rahman', 'Awad'];
            $titles = ['L&D Manager', 'Head of HR', 'COO', 'Team Lead', 'Procurement Lead', 'CTO', 'Operations Director'];
            $rows = [];
            for ($i = 0; $i < $contactTarget; $i++) {
                $rows[] = ['public_id' => Uuid::v7(), 'company_id' => $companyIds[$i % count($companyIds)], 'first_name' => $firsts[$i % count($firsts)], 'last_name' => $lasts[$i % count($lasts)], 'email' => 'contact'.str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT).'@'.$this->emailDomain, 'phone' => '+9665'.str_pad((string) $this->ri(0, 9999999), 8, '0', STR_PAD_LEFT), 'title' => $this->pick($titles), 'created_at' => $now, 'updated_at' => $now];
            }
            $this->bulk('crm_contacts', $rows, false);
        }
        $contactIds = DB::table('crm_contacts')->whereIn('company_id', $companyIds)->orderBy('id')->pluck('id')->all();

        // Leads (across pipeline stages; demo-marked by email domain).
        $leadTarget = (int) ($crm['leads'] ?? 6);
        $stageIds = $stages->pluck('id')->all();
        $leadStatuses = [LeadStatus::New, LeadStatus::Working, LeadStatus::Qualified, LeadStatus::Converted, LeadStatus::Lost];
        if ($pipeline !== null && $stageIds !== [] && DB::table('crm_leads')->where('email', 'like', '%@'.$this->emailDomain)->count() < $leadTarget) {
            $orgNames = ['Gulf Retail Group', 'Cedar Logistics', 'Nile Fintech', 'Atlas Manufacturing', 'Oasis Healthcare', 'Delta Education', 'Zenith Tech', 'Falcon Retail'];
            $rows = [];
            for ($i = 0; $i < $leadTarget; $i++) {
                $rows[] = ['public_id' => Uuid::v7(), 'pipeline_id' => (int) $pipeline->id, 'stage_id' => $stageIds[$i % count($stageIds)], 'company_id' => $companyIds[$i % count($companyIds)], 'contact_id' => $contactIds !== [] ? $contactIds[$i % count($contactIds)] : null, 'owner_id' => null, 'name' => $orgNames[$i % count($orgNames)].' — Opportunity '.($i + 1), 'email' => 'lead'.str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT).'@'.$this->emailDomain, 'phone' => '+9665'.str_pad((string) $this->ri(0, 9999999), 8, '0', STR_PAD_LEFT), 'source' => $this->pick(['web', 'referral', 'event']), 'status' => $this->pick($leadStatuses)->value, 'value_minor' => $this->ri(1, 40) * 100000, 'currency' => $this->currency, 'created_at' => $now, 'updated_at' => $now];
            }
            $this->bulk('crm_leads', $rows, false);
        }
        $leadIds = DB::table('crm_leads')->where('email', 'like', '%@'.$this->emailDomain)->orderBy('id')->pluck('id')->all();

        // Opportunities (open/won/lost).
        $oppTarget = (int) ($crm['opportunities'] ?? 4);
        if ($leadIds !== [] && DB::table('crm_opportunities')->whereIn('lead_id', $leadIds)->count() < $oppTarget) {
            $oppStatuses = [OpportunityStatus::Open, OpportunityStatus::Won, OpportunityStatus::Lost];
            $rows = [];
            for ($i = 0; $i < $oppTarget; $i++) {
                $rows[] = ['public_id' => Uuid::v7(), 'lead_id' => $leadIds[$i % count($leadIds)], 'company_id' => $companyIds[$i % count($companyIds)], 'name' => 'Enterprise training package '.($i + 1), 'amount_minor' => $this->ri(2, 60) * 100000, 'currency' => $this->currency, 'status' => $this->pick($oppStatuses)->value, 'expected_close_date' => (clone $now)->addDays($this->ri(-60, 120))->toDateString(), 'created_at' => $now, 'updated_at' => $now];
            }
            $this->bulk('crm_opportunities', $rows, false);
        }

        // Activities + notes across companies and leads (polymorphic).
        $companyMorph = (new Company)->getMorphClass();
        $leadMorph = (new Lead)->getMorphClass();
        $subjects = [];
        foreach ($companyIds as $cid) {
            $subjects[] = [$companyMorph, $cid];
        }
        foreach ($leadIds as $lid) {
            $subjects[] = [$leadMorph, $lid];
        }
        $demoUserIds = $this->demoUserIds();

        $activityTarget = (int) ($crm['activities'] ?? 20);
        if (DB::table('crm_activities')->whereIn('subject_type', [$companyMorph, $leadMorph])->count() < $activityTarget) {
            $types = [ActivityType::Note, ActivityType::Call, ActivityType::Email, ActivityType::Meeting];
            $descs = ['Intro call — discussed goals and timeline.', 'Sent proposal and pricing.', 'Follow-up email after the demo.', 'Scoping meeting with the L&D team.', 'Left voicemail, will retry next week.'];
            $rows = [];
            for ($i = 0; $i < $activityTarget; $i++) {
                [$st, $sid] = $subjects[$i % count($subjects)];
                $occurred = (clone $now)->subDays($this->ri(0, 300));
                $rows[] = ['public_id' => Uuid::v7(), 'subject_type' => $st, 'subject_id' => $sid, 'type' => $types[$i % count($types)]->value, 'description' => $this->pick($descs), 'user_id' => $demoUserIds !== [] ? $demoUserIds[$i % count($demoUserIds)] : null, 'occurred_at' => $occurred, 'created_at' => $occurred, 'updated_at' => $now];
                if (count($rows) >= 1000) {
                    $this->bulk('crm_activities', $rows, false);
                    $rows = [];
                }
            }
            $this->bulk('crm_activities', $rows, false);
        }

        $noteTarget = (int) ($crm['notes'] ?? 16);
        if (DB::table('crm_notes')->whereIn('noteable_type', [$companyMorph, $leadMorph])->count() < $noteTarget) {
            $bodies = ['Champion is the L&D manager; economic buyer is the COO.', 'Budget approved for Q3; procurement in progress.', 'Prefers cohort-based delivery in Arabic + English.', 'Competitor also shortlisted; differentiate on outcomes.', 'Needs SSO and seat management for 200 employees.'];
            $rows = [];
            for ($i = 0; $i < $noteTarget; $i++) {
                [$st, $sid] = $subjects[$i % count($subjects)];
                $created = (clone $now)->subDays($this->ri(0, 300));
                $rows[] = ['public_id' => Uuid::v7(), 'noteable_type' => $st, 'noteable_id' => $sid, 'user_id' => $demoUserIds !== [] ? $demoUserIds[$i % count($demoUserIds)] : null, 'body' => $this->pick($bodies), 'created_at' => $created, 'updated_at' => $now];
                if (count($rows) >= 1000) {
                    $this->bulk('crm_notes', $rows, false);
                    $rows = [];
                }
            }
            $this->bulk('crm_notes', $rows, false);
        }

        // Consulting requests (org-scoped).
        $orgId = (int) $demoOrgIds[0];
        foreach (['Demo: Team upskilling program for 40 managers', 'Demo: Custom AI-readiness assessment'] as $subject) {
            ConsultingRequest::firstOrCreate(
                ['organization_id' => $orgId, 'subject' => $subject],
                ['description' => 'Prospective enterprise engagement scoped for the demo dataset.', 'status' => ConsultingRequestStatus::New->value, 'sla_due_at' => now()->addHours(48)],
            );
        }
    }

    // ----- Notifications (email templates + bulk in-app volume) -----

    private function seedNotifications(): void
    {
        // Email-channel templates (satisfies "25+ email templates"). Unique (key,channel,locale).
        $emailTarget = $this->pint('notification_templates_email', 0);
        $emailKeys = ['welcome', 'enrollment_confirmed', 'course_completed', 'order_receipt', 'certificate_ready', 'session_scheduled', 'session_reminder', 'consulting_ack', 'password_changed', 'payment_failed', 'refund_processed', 'weekly_digest', 'new_course_available', 'cohort_starting', 'live_recording_ready', 'seat_assigned', 'invoice_issued', 'coupon_offer', 'account_suspended', 'account_reactivated', 'profile_incomplete', 'streak_reminder', 'feedback_request', 'certificate_expiring', 'org_invite'];
        for ($i = 0; $i < $emailTarget; $i++) {
            $key = $emailKeys[$i % count($emailKeys)];
            $suffix = $i >= count($emailKeys) ? '_'.$i : '';
            NotificationTemplate::firstOrCreate(
                ['key' => 'email.'.$key.$suffix, 'channel' => 'email', 'locale' => 'en'],
                ['subject' => ucwords(str_replace('_', ' ', $key)).' — HElbaron Academy', 'body' => '<p>Hello {{ name }},</p><p>This is the '.$key.' email notification for the demo dataset.</p>', 'is_active' => true],
            );
        }

        // Action subset: a genuine welcome via SendNotificationAction (renders the in-app template).
        $send = app(SendNotificationAction::class);
        $studentList = $this->students->values();
        $actionCount = min($this->actionStudents, $studentList->count());
        for ($s = 0; $s < $actionCount; $s++) {
            $student = $studentList[$s];
            if (Notification::where('user_id', $student->id)->where('type', 'welcome')->exists()) {
                continue;
            }
            $send->executeForUserId((int) $student->id, NotificationCategory::Account, 'welcome', ['name' => $student->getAttribute('name')]);
        }

        // Bulk in-app notifications, guarded by count.
        $demoUserIds = $this->demoUserIds();
        [$nMin, $nMax] = $this->pr('notifications_per_student');
        if (DB::table('notifications')->whereIn('user_id', $demoUserIds)->count() >= $studentList->count() * $nMin) {
            return;
        }
        $types = [
            ['learning', 'enrollment_confirmed', 'You are enrolled', 'Your enrollment is confirmed. Start learning now.'],
            ['learning', 'course_completed', 'Course completed', 'Congratulations on completing a course.'],
            ['commerce', 'order_receipt', 'Payment received', 'Thank you — your order is confirmed.'],
            ['certification', 'certificate_ready', 'Certificate ready', 'Your certificate is available to download.'],
            ['live', 'session_scheduled', 'New live session', 'A new live session has been scheduled.'],
            ['account', 'profile_incomplete', 'Complete your profile', 'Add a bio and photo to personalise your account.'],
        ];
        $now = now();
        $rows = [];
        foreach ($studentList as $si => $student) {
            $uid = (int) $student->id;
            $count = $this->ri($nMin, $nMax);
            for ($n = 0; $n < $count; $n++) {
                $t = $types[($si + $n) % count($types)];
                $created = (clone $now)->subDays($this->ri(0, 200));
                $read = ($si + $n) % 3 !== 0 ? (clone $created)->addHours($this->ri(1, 72)) : null;
                $rows[] = ['public_id' => Uuid::v7(), 'user_id' => $uid, 'category' => $t[0], 'type' => $t[1], 'title' => $t[2], 'body' => $t[3], 'data' => null, 'locale' => 'en', 'read_at' => $read, 'archived_at' => null, 'created_at' => $created, 'updated_at' => $now];
                if (count($rows) >= 2000) {
                    $this->bulk('notifications', $rows, false);
                    $rows = [];
                }
            }
        }
        $this->bulk('notifications', $rows, false);
    }

    // ----- Analytics (historical daily snapshots; aggregate stand-in for 250k+ "events") -----

    private function seedMetrics(): void
    {
        // NO analytics events table exists — cumulative "events" are represented as aggregate daily
        // MetricSnapshot VALUES with an upward trend + weekly seasonality (deterministic). Idempotent
        // via updateOrCreate on the unique key. We do NOT loop the additive rollup service.
        $metrics = [
            'signups' => [50, 520],
            'enrollments' => [180, 1500],
            'completions' => [40, 620],
            'orders_paid' => [20, 280],
            'revenue' => [300000, 3600000],
            'certificates_issued' => [30, 560],
            'live_sessions_completed' => [0, 6],
            'consulting_requests' => [1, 16],
        ];
        $days = max(1, $this->pint('metric_days', 30));
        $now = now();

        foreach ($metrics as $key => [$startMin, $recentMax]) {
            $rows = [];
            for ($d = $days - 1; $d >= 0; $d--) {
                $date = (clone $now)->subDays($d);
                $period = $date->toDateString();
                $t = $days > 1 ? ($days - 1 - $d) / ($days - 1) : 1.0; // 0 (oldest) -> 1 (today)
                $base = $startMin + ($recentMax - $startMin) * $t;
                $dow = (int) $date->dayOfWeek; // 0=Sun ... 5=Fri, 6=Sat
                $seasonal = in_array($dow, [5, 6], true) ? 0.72 : 1.0;
                $jitter = 0.85 + (crc32($key.'|'.$period) % 30) / 100; // 0.85 .. 1.14
                $value = (int) round($base * $seasonal * $jitter);
                $rows[] = [
                    'metric_key' => $key,
                    'granularity' => Granularity::Daily->value,
                    'period' => $period,
                    'dimension_key' => '',
                    'dimension_value' => '',
                    'value' => max(0, $value),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('metric_snapshots')->upsert(
                $rows,
                ['metric_key', 'granularity', 'period', 'dimension_key', 'dimension_value'],
                ['value', 'updated_at'],
            );
        }
    }

    // ----- Audit (append-only history; bulk insert) -----

    private function seedAudit(): void
    {
        // AuditLog is append-only ($guarded=[]); direct bulk insert is fine for demo history.
        $target = max(1, $this->pint('audit', 1));
        $demoUserIds = $this->demoUserIds();
        $now = now();

        // Marker entry (kept for backwards-compatible identification).
        if (DB::table('audit_logs')->where('action', 'demo.seeded')->doesntExist()) {
            app(AuditLogger::class)->log('demo.seeded', null, ['area' => 'demo', 'courses' => $this->courses->count(), 'students' => $this->students->count()]);
        }

        // Guard: skip the bulk history if it already meets the target.
        if (DB::table('audit_logs')->where('action', '!=', 'demo.seeded')->count() >= $target) {
            return;
        }

        $actions = ['user.login', 'enrollment.granted', 'order.paid', 'order.refunded', 'certificate.issued', 'certificate.revoked', 'lesson.completed', 'admin.settings.updated', 'session.scheduled', 'notification.sent'];
        $rows = [];
        for ($i = 0; $i < $target; $i++) {
            $action = $actions[$i % count($actions)];
            $isSystem = $i % 5 === 0;
            $created = (clone $now)->subDays($this->ri(0, 364))->subMinutes($this->ri(0, 1440));
            $rows[] = [
                'actor_id' => $isSystem || $demoUserIds === [] ? null : $demoUserIds[$i % count($demoUserIds)],
                'actor_type' => $isSystem ? 'system' : 'user',
                'action' => $action,
                'subject_type' => null,
                'subject_id' => null,
                'context' => json_encode(['demo' => true, 'seq' => $i]),
                'ip' => null,
                'created_at' => $created,
            ];
            if (count($rows) >= 1000) {
                $this->bulk('audit_logs', $rows, false);
                $rows = [];
            }
        }
        $this->bulk('audit_logs', $rows, false);
    }

    // ----- Marketing media (enrich the seeded homepage CMS blocks with Unsplash imagery) -----

    /**
     * Attach royalty-free imagery to the marketing surfaces so the homepage (testimonials, partner
     * logo cloud, hero) looks like a populated, credible product. Only enriches blocks the light
     * HomepageSeeder already created; content copy is preserved, media slots are filled. Idempotent.
     */
    private function seedMarketingMedia(): void
    {
        if (! $this->externalMedia) {
            return;
        }

        // A fuller, bilingual, avatar-backed testimonials set (neutral, realistic social proof).
        $testimonials = [
            ['quote' => ['en' => 'HElbaron rebuilt how our managers lead. The cohort format actually stuck.', 'ar' => 'أعادت HElbaron تشكيل طريقة قيادة مديرينا. وأسلوب الأفواج ترسّخ فعلًا.'], 'author' => 'Layla Hassan', 'role' => ['en' => 'People Director, Nile Group', 'ar' => 'مديرة الموارد البشرية، مجموعة النيل']],
            ['quote' => ['en' => 'The AI-for-business track paid for itself within a single quarter.', 'ar' => 'مسار الذكاء الاصطناعي للأعمال عوّض تكلفته خلال ربع سنة واحد.'], 'author' => 'Omar Fathi', 'role' => ['en' => 'COO, Bosphorus Tech', 'ar' => 'مدير العمليات، بوسفورس تِك']],
            ['quote' => ['en' => 'Practical, regional, and genuinely hands-on — exactly what our team needed.', 'ar' => 'عملي وإقليمي وتطبيقي بحق، تمامًا ما احتاجه فريقنا.'], 'author' => 'Sara Al-Amri', 'role' => ['en' => 'Head of L&D, Gulf Ventures', 'ar' => 'رئيسة التعلّم والتطوير، غلف فينتشرز']],
            ['quote' => ['en' => 'We upskilled 200 employees across two cohorts with the best completion rates we have seen.', 'ar' => 'طوّرنا مهارات 200 موظف عبر فوجين بأفضل معدلات إتمام رأيناها.'], 'author' => 'Khaled Mansour', 'role' => ['en' => 'CHRO, Atlas Energy', 'ar' => 'رئيس الموارد البشرية، أطلس للطاقة']],
            ['quote' => ['en' => 'The instructors are working practitioners, so the advice actually lands in the real world.', 'ar' => 'المدرّبون ممارسون فعليون، لذلك تنطبق نصائحهم في الواقع فعلًا.'], 'author' => 'Reem Darwish', 'role' => ['en' => 'Marketing Lead, Delta Foods', 'ar' => 'مديرة التسويق، دلتا للأغذية']],
            ['quote' => ['en' => 'From verifiable certificates to seat management, the platform is genuinely enterprise-ready.', 'ar' => 'من الشهادات القابلة للتحقّق إلى إدارة المقاعد، المنصّة جاهزة للمؤسسات بحق.'], 'author' => 'Tariq Nabil', 'role' => ['en' => 'IT Director, Cedar Health', 'ar' => 'مدير تقنية المعلومات، سيدار للصحة']],
        ];
        $items = [];
        foreach ($testimonials as $t) {
            $items[] = [
                'quote' => $t['quote'],
                'author' => $t['author'],
                'role' => $t['role'],
                'avatar' => $this->avatarUrl('testimonial|'.$t['author']),
                'rating' => 5,
            ];
        }
        $this->updateHomepageBlock('testimonials', fn (array $c): array => array_replace($c, ['items' => $items]));

        // Partners: attach a logo image to each existing partner name.
        $this->updateHomepageBlock('partners', function (array $c): array {
            $out = [];
            foreach ((array) ($c['items'] ?? []) as $it) {
                $row = (array) $it;
                $row['logo'] = $this->logoUrl('logo|'.(string) ($row['name'] ?? 'partner'));
                $out[] = $row;
            }
            if ($out !== []) {
                $c['items'] = $out;
            }

            return $c;
        });

        // Hero: give the hero block a background image.
        $this->updateHomepageBlock('hero', function (array $c): array {
            $hero = (string) ($this->imageManifest()['hero'] ?? '');
            $c['image'] = $hero === '' ? null : $this->buildImageUrl($hero, 'banner_params');

            return $c;
        });

        // Optional logo-cloud expansion block (only if the operator added it): fill logo slots.
        $this->updateHomepageBlock('logo_cloud', function (array $c): array {
            $out = [];
            foreach ((array) ($c['items'] ?? []) as $idx => $it) {
                $row = (array) $it;
                $row['logo'] = $this->logoUrl('logocloud|'.(string) $idx);
                $out[] = $row;
            }
            if ($out !== []) {
                $c['items'] = $out;
            }

            return $c;
        });
    }

    /**
     * Idempotently transform an existing homepage block's content (and its published mirror). Skips
     * silently if the block was never seeded (e.g. demo:seed run before the light homepage seeder).
     *
     * @param  callable(array<string, mixed>): array<string, mixed>  $transform
     */
    private function updateHomepageBlock(string $key, callable $transform): void
    {
        $section = HomepageSection::query()->where('key', $key)->first();
        if (! $section instanceof HomepageSection) {
            return;
        }
        /** @var array<string, mixed> $content */
        $content = (array) $section->getAttribute('content');
        $next = $transform($content);
        $section->setAttribute('content', $next);
        if ($section->getAttribute('published_content') !== null || $section->getAttribute('published_at') !== null) {
            $section->setAttribute('published_content', $next);
        }
        $section->save();
    }

    // ----- Destructive reset (command-gated; FK-safe; transactional) -----

    private function purge(): void
    {
        DB::transaction(function (): void {
            $userIds = DB::table('users')->where('email', 'like', '%@'.$this->emailDomain)->pluck('id')->all();
            $courseIds = DB::table('courses')->where('slug', 'like', 'demo-%')->pluck('id')->all();
            $productIds = DB::table('products')->where('slug', 'like', 'demo-%')->pluck('id')->all();
            $liveCourseIds = DB::table('live_courses')->where('title', 'like', 'Demo %')->pluck('id')->all();
            $orgIds = DB::table('crm_organizations')->where('slug', 'like', 'demo-%')->pluck('id')->all();
            $companyIds = DB::table('crm_companies')->whereIn('organization_id', $orgIds)->pluck('id')->all();
            $leadIds = DB::table('crm_leads')->where('email', 'like', '%@'.$this->emailDomain)->pluck('id')->all();

            if ($userIds !== []) {
                DB::table('model_has_roles')->whereIn('model_id', $userIds)->delete();
                DB::table('users')->whereIn('id', $userIds)->delete();
            }
            if ($liveCourseIds !== []) {
                DB::table('live_sessions')->whereIn('live_course_id', $liveCourseIds)->delete();
                DB::table('live_courses')->whereIn('id', $liveCourseIds)->delete();
            }
            if ($productIds !== []) {
                DB::table('products')->whereIn('id', $productIds)->delete();
            }
            if ($courseIds !== []) {
                DB::table('courses')->whereIn('id', $courseIds)->delete();
            }
            DB::table('coupons')->where('code', 'like', 'DEMO%')->delete();

            // CRM (nullOnDelete / no cascade for these): remove demo-scoped rows explicitly.
            $companyMorph = (new Company)->getMorphClass();
            $leadMorph = (new Lead)->getMorphClass();
            if ($companyIds !== [] || $leadIds !== []) {
                DB::table('crm_activities')->where(function ($q) use ($companyMorph, $leadMorph, $companyIds, $leadIds): void {
                    $q->where(fn ($qq) => $qq->where('subject_type', $companyMorph)->whereIn('subject_id', $companyIds))
                        ->orWhere(fn ($qq) => $qq->where('subject_type', $leadMorph)->whereIn('subject_id', $leadIds));
                })->delete();
                DB::table('crm_notes')->where(function ($q) use ($companyMorph, $leadMorph, $companyIds, $leadIds): void {
                    $q->where(fn ($qq) => $qq->where('noteable_type', $companyMorph)->whereIn('noteable_id', $companyIds))
                        ->orWhere(fn ($qq) => $qq->where('noteable_type', $leadMorph)->whereIn('noteable_id', $leadIds));
                })->delete();
            }
            DB::table('crm_opportunities')->whereIn('lead_id', $leadIds)->orWhereIn('company_id', $companyIds)->delete();
            DB::table('crm_contacts')->whereIn('company_id', $companyIds)->delete();
            DB::table('crm_leads')->where('email', 'like', '%@'.$this->emailDomain)->delete();
            DB::table('crm_companies')->whereIn('organization_id', $orgIds)->delete();
            if ($orgIds !== []) {
                DB::table('consulting_requests')->whereIn('organization_id', $orgIds)->delete();
                DB::table('crm_teams')->whereIn('organization_id', $orgIds)->delete();
                DB::table('crm_departments')->whereIn('organization_id', $orgIds)->delete();
                DB::table('seat_pools')->whereIn('organization_id', $orgIds)->delete();
                DB::table('organization_members')->whereIn('organization_id', $orgIds)->delete();
                DB::table('crm_organizations')->whereIn('id', $orgIds)->delete();
            }
            DB::table('consulting_requests')->where('subject', 'like', 'Demo:%')->delete();

            DB::table('metric_snapshots')
                ->whereIn('metric_key', ['signups', 'enrollments', 'completions', 'orders_paid', 'revenue', 'certificates_issued', 'live_sessions_completed', 'consulting_requests'])
                ->delete();

            DB::table('notification_templates')->where('key', 'like', 'email.%')->delete();
            DB::table('audit_logs')->whereRaw("context::jsonb ->> 'demo' = 'true'")->orWhere('action', 'demo.seeded')->delete();
        });
    }

    // ----- Summary -----

    /**
     * @return array<string, int>
     */
    private function summary(): array
    {
        $userIds = $this->demoUserIds();
        $courseIds = DB::table('courses')->where('slug', 'like', 'demo-%')->pluck('id')->all();
        $sectionIds = DB::table('course_sections')->whereIn('course_id', $courseIds)->pluck('id')->all();
        $lessonIds = DB::table('lessons')->whereIn('section_id', $sectionIds)->pluck('id')->all();
        $orderIds = DB::table('orders')->whereIn('user_id', $userIds)->pluck('id')->all();
        $enrollmentIds = DB::table('enrollments')->whereIn('user_id', $userIds)->pluck('id')->all();
        $liveCourseIds = DB::table('live_courses')->where('title', 'like', 'Demo %')->pluck('id')->all();
        $sessionIds = DB::table('live_sessions')->whereIn('live_course_id', $liveCourseIds)->pluck('id')->all();
        $orgIds = DB::table('crm_organizations')->where('slug', 'like', 'demo-%')->pluck('id')->all();
        $companyIds = DB::table('crm_companies')->whereIn('organization_id', $orgIds)->pluck('id')->all();
        $leadIds = DB::table('crm_leads')->where('email', 'like', '%@'.$this->emailDomain)->pluck('id')->all();
        $productIds = DB::table('products')->where('slug', 'like', 'demo-%')->pluck('id')->all();
        $companyMorph = (new Company)->getMorphClass();
        $leadMorph = (new Lead)->getMorphClass();

        return [
            'instructors' => $this->roleCount(Role::Instructor->value),
            'students' => $this->roleCount(Role::Student->value),
            'courses' => count($courseIds),
            'sections' => count($sectionIds),
            'lessons' => count($lessonIds),
            'lesson_media' => DB::table('lesson_media')->whereIn('lesson_id', $lessonIds)->count(),
            'enrollments' => count($enrollmentIds),
            'lesson_progress' => DB::table('lesson_progress')->whereIn('enrollment_id', $enrollmentIds)->count(),
            'bookmarks' => DB::table('lesson_bookmarks')->whereIn('user_id', $userIds)->count(),
            'notes' => DB::table('lesson_notes')->whereIn('user_id', $userIds)->count(),
            'certificates' => DB::table('certificates')->whereIn('user_id', $userIds)->count(),
            'certificates_revoked' => DB::table('certificates')->whereIn('user_id', $userIds)->where('status', CertificateStatus::Revoked->value)->count(),
            'products' => count($productIds),
            'product_prices' => DB::table('product_prices')->whereIn('product_id', $productIds)->count(),
            'coupons' => DB::table('coupons')->where('code', 'like', 'DEMO%')->count(),
            'coupon_redemptions' => DB::table('coupon_redemptions')->whereIn('order_id', $orderIds)->count(),
            'orders' => count($orderIds),
            'order_items' => DB::table('order_items')->whereIn('order_id', $orderIds)->count(),
            'invoices' => DB::table('invoices')->whereIn('order_id', $orderIds)->count(),
            'charges' => DB::table('payment_transactions')->whereIn('order_id', $orderIds)->where('type', TransactionType::Charge->value)->count(),
            'refunds' => DB::table('payment_transactions')->whereIn('order_id', $orderIds)->where('type', TransactionType::Refund->value)->count(),
            'organizations' => count($orgIds),
            'organization_members' => DB::table('organization_members')->whereIn('organization_id', $orgIds)->count(),
            'departments' => DB::table('crm_departments')->whereIn('organization_id', $orgIds)->count(),
            'teams' => DB::table('crm_teams')->whereIn('organization_id', $orgIds)->count(),
            'seat_pools' => DB::table('seat_pools')->whereIn('organization_id', $orgIds)->count(),
            'live_courses' => count($liveCourseIds),
            'live_sessions' => count($sessionIds),
            'session_registrations' => DB::table('session_registrations')->whereIn('session_id', $sessionIds)->count(),
            'session_attendances' => DB::table('session_attendances')->whereIn('session_id', $sessionIds)->count(),
            'session_recordings' => DB::table('session_recordings')->whereIn('session_id', $sessionIds)->count(),
            'crm_companies' => count($companyIds),
            'crm_contacts' => DB::table('crm_contacts')->whereIn('company_id', $companyIds)->count(),
            'crm_leads' => count($leadIds),
            'crm_opportunities' => DB::table('crm_opportunities')->whereIn('lead_id', $leadIds)->count(),
            'crm_activities' => DB::table('crm_activities')->whereIn('subject_type', [$companyMorph, $leadMorph])->count(),
            'crm_notes' => DB::table('crm_notes')->whereIn('noteable_type', [$companyMorph, $leadMorph])->count(),
            'consulting_requests' => DB::table('consulting_requests')->where('subject', 'like', 'Demo:%')->count(),
            'notifications' => DB::table('notifications')->whereIn('user_id', $userIds)->count(),
            'email_templates' => DB::table('notification_templates')->where('channel', 'email')->where('key', 'like', 'email.%')->count(),
            'metric_snapshots' => DB::table('metric_snapshots')->whereIn('metric_key', ['signups', 'enrollments', 'completions', 'orders_paid', 'revenue', 'certificates_issued', 'live_sessions_completed', 'consulting_requests'])->count(),
            'audit_logs' => DB::table('audit_logs')->where(fn ($q) => $q->whereRaw("context::jsonb ->> 'demo' = 'true'")->orWhere('action', 'demo.seeded'))->count(),
        ];
    }

    private function roleCount(string $role): int
    {
        return DB::table('users')
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('users.email', 'like', '%@'.$this->emailDomain)
            ->where('roles.name', $role)->distinct()->count('users.id');
    }
}
