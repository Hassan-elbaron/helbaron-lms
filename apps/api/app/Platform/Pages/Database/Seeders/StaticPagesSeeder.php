<?php

namespace App\Platform\Pages\Database\Seeders;

use App\Platform\Pages\Enums\PageStatus;
use App\Platform\Pages\Enums\TemplateType;
use App\Platform\Pages\Models\StaticPage;
use Illuminate\Database\Seeder;

/**
 * Seeds the static CMS pages with on-brand bilingual content, already published, so the public
 * routes render from the CMS immediately. Migrates the previously hardcoded frontend pages
 * (about / contact / privacy / terms) verbatim in spirit, plus additional standard legal/support
 * pages (cookies, refund-policy, faq, careers, help). Idempotent: firstOrCreate on `slug`.
 */
class StaticPagesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (self::pages() as $slug => $page) {
            StaticPage::firstOrCreate(
                ['slug' => $slug],
                [
                    'template' => $page['template'],
                    'title' => $page['title'],
                    'excerpt' => $page['excerpt'],
                    'body' => $page['body'],
                    'hero_image' => null,
                    'status' => PageStatus::Published,
                    'published_at' => now(),
                    'unpublished_at' => null,
                    'position' => $page['position'],
                    'show_in_nav' => $page['show_in_nav'] ?? false,
                    'seo' => $page['seo'] ?? null,
                ],
            );
        }
    }

    /**
     * @return array<string, array{
     *     template: TemplateType, position: int, show_in_nav?: bool,
     *     title: array<string,string>, excerpt: array<string,string>,
     *     body: array<string,string>, seo?: array<string,mixed>
     * }>
     */
    private static function pages(): array
    {
        return [
            'about' => [
                'template' => TemplateType::Standard,
                'position' => 10,
                'title' => ['en' => 'About HElbaron', 'ar' => 'عن HElbaron'],
                'excerpt' => [
                    'en' => 'HElbaron is a bilingual professional academy built for the MENA region — practical courses, live cohorts, and verifiable certificates in Arabic and English.',
                    'ar' => 'HElbaron أكاديمية مهنية ثنائية اللغة مصمّمة لمنطقة الشرق الأوسط وشمال أفريقيا — دورات عملية وأفواج مباشرة وشهادات قابلة للتحقّق بالعربية والإنجليزية.',
                ],
                'body' => [
                    'en' => '<p>HElbaron exists to make high-quality, practical business education available in both Arabic and English — designed from the ground up for learners across the MENA region.</p>'
                        .'<h2>Our mission</h2><p>Help professionals and teams master the fundamentals and lead with confidence — with learning that respects their language and context.</p>'
                        .'<h2>How we teach</h2><p>Practical, outcome-focused programs led by practitioners — courses, live cohorts, and workshops you can apply the next day.</p>'
                        .'<h2>Bilingual by design</h2><p>The whole experience works in Arabic and English, with full right-to-left support — not a translation bolted on afterwards.</p>'
                        .'<h2>Our story</h2><p>HElbaron started from a simple observation: ambitious professionals across the region were learning in a language that was not theirs, from material that did not reflect their market. We set out to build an academy that treats Arabic and English as equals and puts practical, regional relevance first.</p>'
                        .'<h2>What we believe</h2><p>Great education is practical, honest, and accessible. We focus on skills people can use, we present our offering plainly, and we build for the languages and devices our learners actually use.</p>'
                        .'<p>We are an independent academy. We describe our programs honestly and do not claim external accreditation we do not hold.</p>',
                    'ar' => '<p>وُجدت HElbaron لإتاحة تعليم أعمال عملي وعالي الجودة بالعربية والإنجليزية معًا — مصمّمة من الأساس لمتعلّمي منطقة الشرق الأوسط وشمال أفريقيا.</p>'
                        .'<h2>مهمّتنا</h2><p>مساعدة المحترفين والفرق على إتقان الأساسيات والقيادة بثقة — بتعليم يحترم لغتهم وسياقهم.</p>'
                        .'<h2>كيف نعلّم</h2><p>برامج عملية تركّز على النتائج يقودها ممارسون — دورات وأفواج مباشرة وورش يمكنك تطبيقها في اليوم التالي.</p>'
                        .'<h2>ثنائية اللغة بالتصميم</h2><p>التجربة بأكملها تعمل بالعربية والإنجليزية مع دعم كامل للكتابة من اليمين إلى اليسار — لا ترجمة مُضافة لاحقًا.</p>'
                        .'<h2>قصّتنا</h2><p>بدأت HElbaron من ملاحظة بسيطة: محترفون طموحون في المنطقة يتعلّمون بلغة ليست لغتهم ومن مواد لا تعكس سوقهم. فانطلقنا لبناء أكاديمية تعامل العربية والإنجليزية على قدم المساواة وتضع الملاءمة العملية والإقليمية أولًا.</p>'
                        .'<h2>ما نؤمن به</h2><p>التعليم الجيّد عملي وصادق ومتاح. نركّز على مهارات يمكن للناس استخدامها، ونعرض ما نقدّمه بوضوح، ونبني للّغات والأجهزة التي يستخدمها متعلّمونا فعلًا.</p>'
                        .'<p>نحن أكاديمية مستقلّة. نصف برامجنا بصدق ولا ندّعي اعتمادًا خارجيًا لا نملكه.</p>',
                ],
            ],
            'contact' => [
                'template' => TemplateType::Contact,
                'position' => 20,
                'title' => ['en' => 'Contact HElbaron', 'ar' => 'تواصل معنا'],
                'excerpt' => [
                    'en' => 'Get in touch with HElbaron — reach out about enterprise and government training, advisory engagements, or general questions.',
                    'ar' => 'تواصل مع HElbaron — راسلنا بخصوص تدريب المؤسسات والحكومات أو مشاريع الاستشارات أو الأسئلة العامة.',
                ],
                'body' => [
                    'en' => '<p>Choose the route that fits your need. For business and consulting we will connect you with the right team; for everything else, email us directly.</p>'
                        .'<h2>Enterprise &amp; government</h2><p>For team training, seat-based plans, SSO/SCORM, and custom programs, start with our <a href="/enterprise">enterprise team</a>.</p>'
                        .'<h2>Advisory &amp; consulting</h2><p>For strategy, operations, partnerships, and go-to-market engagements, reach <a href="/advisory">HElbaron Advisory</a>.</p>'
                        .'<h2>General questions</h2><p>For anything else — courses, certificates, or partnerships — email <a href="mailto:hello@helbaron.academy">hello@helbaron.academy</a> and we will point you to the right place.</p>'
                        .'<h2>Where we are</h2><p>HElbaron works across the region, with hubs in Cairo, Dubai, and Riyadh. Wherever you are, our courses and cohorts are available online in Arabic and English.</p>'
                        .'<h2>Already a learner?</h2><p>If you already have an account, sign in to manage your profile, track progress, and download or verify your certificates.</p>',
                    'ar' => '<p>اختر المسار الذي يناسب احتياجك. للأعمال والاستشارات سنوصلك بالفريق المناسب؛ ولكل ما عدا ذلك، راسلنا مباشرة.</p>'
                        .'<h2>المؤسسات والحكومات</h2><p>لتدريب الفرق والخطط القائمة على المقاعد والدخول الموحّد وSCORM والبرامج المخصّصة، ابدأ مع <a href="/enterprise">فريق المؤسسات</a>.</p>'
                        .'<h2>الاستشارات</h2><p>للاستراتيجية والعمليات والشراكات ودخول السوق، تواصل مع <a href="/advisory">استشارات HElbaron</a>.</p>'
                        .'<h2>أسئلة عامة</h2><p>لأي شيء آخر — الدورات أو الشهادات أو الشراكات — راسلنا على <a href="mailto:hello@helbaron.academy">hello@helbaron.academy</a> وسنوجّهك إلى المكان الصحيح.</p>'
                        .'<h2>أين نحن</h2><p>تعمل HElbaron عبر المنطقة، بمراكز في القاهرة ودبي والرياض. أينما كنت، دوراتنا وأفواجنا متاحة عبر الإنترنت بالعربية والإنجليزية.</p>'
                        .'<h2>متعلّم بالفعل؟</h2><p>إن كان لديك حساب بالفعل، سجّل الدخول لإدارة ملفك ومتابعة تقدّمك وتنزيل شهاداتك أو التحقّق منها.</p>',
                ],
            ],
            'privacy' => [
                'template' => TemplateType::Legal,
                'position' => 90,
                'title' => ['en' => 'Privacy Policy', 'ar' => 'سياسة الخصوصية'],
                'excerpt' => [
                    'en' => 'How HElbaron collects, uses, and protects your information across our academy and services.',
                    'ar' => 'كيف تجمع HElbaron معلوماتك وتستخدمها وتحميها عبر أكاديميتنا وخدماتنا.',
                ],
                'body' => [
                    'en' => '<p>How HElbaron collects, uses, and protects your information across our academy and services.</p>'
                        .'<h2>Information we collect</h2><p>Account details, learning progress, and usage data needed to deliver courses, cohorts, and enterprise programs.</p>'
                        .'<h2>How we use it</h2><p>To personalize learning, issue certificates, provide support, and improve the platform. We do not sell your data.</p>'
                        .'<h2>Your rights</h2><p>You may access, correct, export, or delete your data at any time by contacting our team.</p>',
                    'ar' => '<p>كيف تجمع HElbaron معلوماتك وتستخدمها وتحميها عبر أكاديميتنا وخدماتنا.</p>'
                        .'<h2>المعلومات التي نجمعها</h2><p>بيانات الحساب وتقدّم التعلّم وبيانات الاستخدام اللازمة لتقديم الدورات والأفواج وبرامج المؤسسات.</p>'
                        .'<h2>كيف نستخدمها</h2><p>لتخصيص التعلّم وإصدار الشهادات وتقديم الدعم وتحسين المنصة. لا نبيع بياناتك.</p>'
                        .'<h2>حقوقك</h2><p>يمكنك الوصول لبياناتك أو تصحيحها أو تصديرها أو حذفها في أي وقت بالتواصل مع فريقنا.</p>',
                ],
            ],
            'terms' => [
                'template' => TemplateType::Legal,
                'position' => 91,
                'title' => ['en' => 'Terms of Service', 'ar' => 'شروط الخدمة'],
                'excerpt' => [
                    'en' => 'The terms governing your use of HElbaron courses, cohorts, and services.',
                    'ar' => 'الشروط التي تحكم استخدامك لدورات وأفواج وخدمات HElbaron.',
                ],
                'body' => [
                    'en' => '<p>The terms that govern your use of HElbaron courses, cohorts, workshops, enterprise training, and advisory.</p>'
                        .'<h2>Using the platform</h2><p>Your account is personal. Content is licensed for your own learning and may not be redistributed.</p>'
                        .'<h2>Payments &amp; refunds</h2><p>Fees are shown before purchase. Refund eligibility depends on the program and is described at checkout.</p>'
                        .'<h2>Enterprise agreements</h2><p>B2B / B2G engagements are governed by a separate signed agreement in addition to these terms.</p>',
                    'ar' => '<p>الشروط التي تحكم استخدامك لدورات وأفواج وورش وتدريب واستشارات HElbaron.</p>'
                        .'<h2>استخدام المنصة</h2><p>حسابك شخصي. المحتوى مرخّص لتعلّمك الشخصي ولا يجوز إعادة توزيعه.</p>'
                        .'<h2>المدفوعات والاسترداد</h2><p>تُعرض الرسوم قبل الشراء. تعتمد أهلية الاسترداد على البرنامج وتُوضّح عند الدفع.</p>'
                        .'<h2>اتفاقيات المؤسسات</h2><p>تخضع مشاريع المؤسسات والحكومات لاتفاقية موقّعة منفصلة إضافةً لهذه الشروط.</p>',
                ],
            ],
            'cookies' => [
                'template' => TemplateType::Legal,
                'position' => 92,
                'title' => ['en' => 'Cookie Policy', 'ar' => 'سياسة ملفات تعريف الارتباط'],
                'excerpt' => [
                    'en' => 'How HElbaron uses cookies and similar technologies, and the choices you have.',
                    'ar' => 'كيف تستخدم HElbaron ملفات تعريف الارتباط والتقنيات المشابهة، والخيارات المتاحة لك.',
                ],
                'body' => [
                    'en' => '<p>This policy explains how HElbaron uses cookies and similar technologies across our academy.</p>'
                        .'<h2>What cookies we use</h2><p>Strictly necessary cookies keep you signed in and secure. Preference cookies remember your language and settings. Analytics cookies help us understand and improve the platform.</p>'
                        .'<h2>Managing cookies</h2><p>You can control non-essential cookies through your browser settings at any time. Disabling some cookies may affect how the platform works.</p>'
                        .'<h2>Third parties</h2><p>Some features rely on trusted providers (for example payments and video). Their cookies are governed by their own policies.</p>',
                    'ar' => '<p>توضّح هذه السياسة كيفية استخدام HElbaron لملفات تعريف الارتباط والتقنيات المشابهة عبر أكاديميتنا.</p>'
                        .'<h2>ما الملفات التي نستخدمها</h2><p>الملفات الضرورية تُبقيك مسجّلاً وآمنًا. ملفات التفضيلات تتذكّر لغتك وإعداداتك. ملفات التحليلات تساعدنا على فهم المنصة وتحسينها.</p>'
                        .'<h2>إدارة الملفات</h2><p>يمكنك التحكّم في الملفات غير الأساسية عبر إعدادات المتصفّح في أي وقت. قد يؤثّر تعطيل بعضها على عمل المنصة.</p>'
                        .'<h2>الأطراف الثالثة</h2><p>تعتمد بعض الميزات على مزوّدين موثوقين (مثل المدفوعات والفيديو). تخضع ملفاتهم لسياساتهم الخاصة.</p>',
                ],
            ],
            'refund-policy' => [
                'template' => TemplateType::Legal,
                'position' => 93,
                'title' => ['en' => 'Refund Policy', 'ar' => 'سياسة الاسترداد'],
                'excerpt' => [
                    'en' => 'When and how refunds apply to HElbaron courses, cohorts, and workshops.',
                    'ar' => 'متى وكيف يُطبّق الاسترداد على دورات وأفواج وورش HElbaron.',
                ],
                'body' => [
                    'en' => '<p>We want you to be confident in what you buy. Refund eligibility depends on the type of program and is always shown at checkout.</p>'
                        .'<h2>Self-paced courses</h2><p>You may request a refund within 14 days of purchase provided you have completed less than 25% of the course.</p>'
                        .'<h2>Live cohorts &amp; workshops</h2><p>Because seats are limited, refunds are available up to 7 days before the start date. After that, you may transfer your seat to a future cohort.</p>'
                        .'<h2>Enterprise agreements</h2><p>Refund and cancellation terms for B2B / B2G engagements are set out in the signed agreement.</p>'
                        .'<h2>How to request</h2><p>Email <a href="mailto:hello@helbaron.academy">hello@helbaron.academy</a> from your account email and our team will help.</p>',
                    'ar' => '<p>نريدك أن تكون واثقًا مما تشتريه. تعتمد أهلية الاسترداد على نوع البرنامج وتُعرض دائمًا عند الدفع.</p>'
                        .'<h2>الدورات الذاتية</h2><p>يمكنك طلب استرداد خلال 14 يومًا من الشراء بشرط أن تكون قد أكملت أقل من 25% من الدورة.</p>'
                        .'<h2>الأفواج والورش المباشرة</h2><p>نظرًا لمحدودية المقاعد، يتاح الاسترداد حتى 7 أيام قبل تاريخ البدء. بعد ذلك يمكنك نقل مقعدك إلى فوج لاحق.</p>'
                        .'<h2>اتفاقيات المؤسسات</h2><p>تُحدَّد شروط الاسترداد والإلغاء لمشاريع المؤسسات والحكومات في الاتفاقية الموقّعة.</p>'
                        .'<h2>كيفية الطلب</h2><p>راسلنا على <a href="mailto:hello@helbaron.academy">hello@helbaron.academy</a> من بريد حسابك وسيساعدك فريقنا.</p>',
                ],
            ],
            'faq' => [
                'template' => TemplateType::Faq,
                'position' => 30,
                'title' => ['en' => 'Frequently Asked Questions', 'ar' => 'الأسئلة الشائعة'],
                'excerpt' => [
                    'en' => 'Answers to the most common questions about HElbaron courses, certificates, and enterprise training.',
                    'ar' => 'إجابات لأكثر الأسئلة شيوعًا حول دورات HElbaron وشهاداتها وتدريب المؤسسات.',
                ],
                'body' => [
                    'en' => '<h2>Who are the courses for?</h2><p>Professionals, founders, and enterprise teams across MENA — from individual learners to whole organizations.</p>'
                        .'<h2>Are the courses in Arabic or English?</h2><p>The platform is fully bilingual (English and Arabic) with right-to-left support throughout.</p>'
                        .'<h2>Do I get a certificate?</h2><p>Yes — completed courses and cohorts issue a verifiable certificate you can share and validate online.</p>'
                        .'<h2>Can you train my whole team?</h2><p>Yes. Our B2B / B2G programs offer custom curricula, SSO, SCORM, and a dedicated success manager.</p>',
                    'ar' => '<h2>لمن هذه الدورات؟</h2><p>للمحترفين والروّاد وفِرَق المؤسسات في المنطقة — من المتعلّم الفرد إلى المؤسسة بأكملها.</p>'
                        .'<h2>هل الدورات بالعربية أم الإنجليزية؟</h2><p>المنصّة ثنائية اللغة بالكامل (العربية والإنجليزية) مع دعم الكتابة من اليمين لليسار في كل مكان.</p>'
                        .'<h2>هل أحصل على شهادة؟</h2><p>نعم — تصدر الدورات والأفواج المكتملة شهادة قابلة للتحقّق يمكنك مشاركتها والتحقّق منها عبر الإنترنت.</p>'
                        .'<h2>هل يمكنكم تدريب فريقي بالكامل؟</h2><p>نعم. تقدّم برامجنا للمؤسسات والحكومات مناهج مخصّصة ودخولًا موحّدًا وSCORM ومدير نجاح مخصّصًا.</p>',
                ],
            ],
            'careers' => [
                'template' => TemplateType::Standard,
                'position' => 40,
                'title' => ['en' => 'Careers at HElbaron', 'ar' => 'الوظائف في HElbaron'],
                'excerpt' => [
                    'en' => 'Help build the bilingual business academy for the MENA region. See how we work and how to reach us.',
                    'ar' => 'ساعدنا في بناء أكاديمية الأعمال ثنائية اللغة للمنطقة. تعرّف على طريقة عملنا وكيفية التواصل معنا.',
                ],
                'body' => [
                    'en' => '<p>We are building the bilingual business academy for the MENA region, and we are always glad to meet people who care about practical education done well.</p>'
                        .'<h2>How we work</h2><p>Small teams, clear ownership, and a bias for shipping. We value people who write clearly, think in both Arabic and English, and put learners first.</p>'
                        .'<h2>Areas we hire for</h2><p>Instruction and curriculum, engineering and design, learner success, and enterprise partnerships.</p>'
                        .'<h2>Get in touch</h2><p>Even if you do not see a specific opening, send a short note and a link to your work to <a href="mailto:careers@helbaron.academy">careers@helbaron.academy</a>.</p>',
                    'ar' => '<p>نحن نبني أكاديمية الأعمال ثنائية اللغة للمنطقة، ويسعدنا دائمًا لقاء من يهتمّون بالتعليم العملي المُتقَن.</p>'
                        .'<h2>كيف نعمل</h2><p>فرق صغيرة ومسؤوليات واضحة وميل للتنفيذ. نقدّر من يكتبون بوضوح ويفكّرون بالعربية والإنجليزية ويضعون المتعلّم أولًا.</p>'
                        .'<h2>مجالات نوظّف فيها</h2><p>التدريس والمناهج، الهندسة والتصميم، نجاح المتعلّمين، وشراكات المؤسسات.</p>'
                        .'<h2>تواصل معنا</h2><p>حتى إن لم تجد شاغرًا محدّدًا، أرسل نبذة قصيرة ورابطًا لأعمالك إلى <a href="mailto:careers@helbaron.academy">careers@helbaron.academy</a>.</p>',
                ],
            ],
            'help' => [
                'template' => TemplateType::Standard,
                'position' => 50,
                'title' => ['en' => 'Help Center', 'ar' => 'مركز المساعدة'],
                'excerpt' => [
                    'en' => 'Guidance on accounts, courses, certificates, and getting support from the HElbaron team.',
                    'ar' => 'إرشادات حول الحسابات والدورات والشهادات والحصول على الدعم من فريق HElbaron.',
                ],
                'body' => [
                    'en' => '<p>Need a hand? Start here — most questions about your account, courses, and certificates are answered below.</p>'
                        .'<h2>Your account</h2><p>Sign in to update your profile, change your language, and manage security. Forgot your password? Use the reset link on the sign-in page.</p>'
                        .'<h2>Courses &amp; progress</h2><p>Your enrolled courses and progress live in your dashboard. Progress saves automatically as you complete lessons.</p>'
                        .'<h2>Certificates</h2><p>When you complete a course or cohort you receive a verifiable certificate with a unique code anyone can check on the verify page.</p>'
                        .'<h2>Still need help?</h2><p>Email <a href="mailto:hello@helbaron.academy">hello@helbaron.academy</a> and our team will get back to you.</p>',
                    'ar' => '<p>تحتاج مساعدة؟ ابدأ من هنا — معظم الأسئلة حول حسابك ودوراتك وشهاداتك مُجابة أدناه.</p>'
                        .'<h2>حسابك</h2><p>سجّل الدخول لتحديث ملفك وتغيير لغتك وإدارة الأمان. نسيت كلمة المرور؟ استخدم رابط إعادة التعيين في صفحة الدخول.</p>'
                        .'<h2>الدورات والتقدّم</h2><p>تظهر دوراتك المسجّلة وتقدّمك في لوحتك. يُحفظ التقدّم تلقائيًا كلما أكملت الدروس.</p>'
                        .'<h2>الشهادات</h2><p>عند إكمال دورة أو فوج تحصل على شهادة قابلة للتحقّق برمز فريد يمكن لأي شخص التحقّق منه في صفحة التحقّق.</p>'
                        .'<h2>ما زلت بحاجة للمساعدة؟</h2><p>راسلنا على <a href="mailto:hello@helbaron.academy">hello@helbaron.academy</a> وسيعود إليك فريقنا.</p>',
                ],
            ],
        ];
    }
}
