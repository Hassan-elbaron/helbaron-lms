/**
 * Sample homepage-block sections for the internal design showcase (Part 15).
 *
 * Pure data — no network, no auth. Each section satisfies its renderer's content/`resolved`
 * guard (see the homepage block registry) so every block type renders with representative
 * content. Kept out of the client component so the showcase file stays focused on layout.
 */
import type { HomepageSection, Localized, LocalizedLink } from "@/lib/homepage/api";

const L = (en: string, ar: string): Localized => ({ en, ar });
const link = (en: string, ar: string, href = "#"): LocalizedLink => ({ label: L(en, ar), href });

/** Self-contained placeholder image (no network / 404s) used for logos, thumbnails, gallery. */
const IMG =
  "data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20width='320'%20height='180'%3E%3Crect%20width='320'%20height='180'%20fill='%230f766e'/%3E%3Cpath%20d='M0%20140%20L320%2090%20320%20180%200%20180Z'%20fill='%23134e4a'/%3E%3C/svg%3E";

let pos = 0;
const next = () => (pos += 1);

/** One representative section per renderable block type (footer + seo are chrome/metadata only). */
export const sampleSections: HomepageSection[] = [
  {
    key: "s-hero",
    type: "hero",
    position: next(),
    content: {
      headline: L("Learn without limits", "تعلّم بلا حدود"),
      subheadline: L(
        "A bilingual professional academy for the modern MENA workforce.",
        "أكاديمية احترافية ثنائية اللغة لقوى العمل الحديثة في المنطقة.",
      ),
      cta_primary: link("Browse courses", "تصفح الدورات"),
      cta_secondary: link("Talk to us", "تواصل معنا"),
      image: null,
    },
  },
  {
    key: "s-features",
    type: "features",
    position: next(),
    content: {
      items: [
        { title: L("Live cohorts", "دفعات مباشرة"), description: L("Structured, instructor-led programs.", "برامج منظمة بقيادة المدربين."), icon: "GraduationCap" },
        { title: L("Self-paced", "تعلم ذاتي"), description: L("Learn anytime, on any device.", "تعلّم في أي وقت على أي جهاز."), icon: "Clock" },
        { title: L("Certificates", "شهادات"), description: L("Verifiable, QR-backed credentials.", "شهادات موثقة برمز QR."), icon: "BadgeCheck" },
      ],
    },
  },
  {
    key: "s-statistics",
    type: "statistics",
    position: next(),
    content: {
      heading: L("Trusted at scale", "موثوق على نطاق واسع"),
      items: [
        { value: "48", suffix: "k+", label: L("Learners", "متعلم") },
        { value: "1.2", suffix: "k", label: L("Courses", "دورة") },
        { value: "96", suffix: "%", label: L("Completion", "إتمام") },
      ],
    },
  },
  {
    key: "s-numbers",
    type: "numbers",
    position: next(),
    content: {
      heading: L("By the numbers", "بالأرقام"),
      items: [
        { value: "120+", label: L("Instructors", "مدرب") },
        { value: "24/7", label: L("Support", "دعم") },
        { value: "12", label: L("Countries", "دولة") },
      ],
    },
  },
  {
    key: "s-categories",
    type: "categories",
    position: next(),
    content: { heading: L("Explore categories", "استكشف الفئات") },
    resolved: {
      categories: [
        { id: "c1", name: L("Business", "الأعمال"), description: L("Strategy & leadership", "الاستراتيجية والقيادة"), slug: "business", href: "#" },
        { id: "c2", name: L("Technology", "التقنية"), description: L("Engineering & data", "الهندسة والبيانات"), slug: "technology", href: "#" },
        { id: "c3", name: L("Design", "التصميم"), description: L("Product & brand", "المنتج والعلامة"), slug: "design", href: "#" },
      ],
    },
  },
  {
    key: "s-featured-courses",
    type: "featured_courses",
    position: next(),
    content: { heading: L("Featured courses", "دورات مميزة"), subheading: L("Hand-picked programs.", "برامج مختارة بعناية."), cta: link("View all", "عرض الكل") },
    resolved: {
      courses: [
        { id: "k1", title: L("Product Strategy", "استراتيجية المنتج"), subtitle: L("8 weeks", "8 أسابيع"), slug: "product-strategy", thumbnail: IMG, level: "Intermediate", href: "#" },
        { id: "k2", title: L("Data Foundations", "أساسيات البيانات"), subtitle: L("6 weeks", "6 أسابيع"), slug: "data-foundations", thumbnail: IMG, level: "Beginner", href: "#" },
        { id: "k3", title: L("Leadership Lab", "مختبر القيادة"), subtitle: L("4 weeks", "4 أسابيع"), slug: "leadership-lab", thumbnail: IMG, level: "Advanced", href: "#" },
      ],
    },
  },
  {
    key: "s-featured-events",
    type: "featured_events",
    position: next(),
    content: { heading: L("Upcoming events", "الفعاليات القادمة"), subheading: L("Live sessions & workshops.", "جلسات وورش مباشرة."), cta: link("All events", "كل الفعاليات") },
    resolved: {
      events: [
        { id: "e1", title: L("Design Systems 101", "أنظمة التصميم 101"), description: L("Free webinar", "ندوة مجانية"), starts_at: "2026-09-01T15:00:00Z", href: "#" },
        { id: "e2", title: L("AI for Managers", "الذكاء الاصطناعي للمدراء"), description: L("Workshop", "ورشة"), starts_at: "2026-09-14T12:00:00Z", href: "#" },
      ],
    },
  },
  {
    key: "s-clients",
    type: "clients",
    position: next(),
    content: {
      heading: L("Trusted by teams", "موثوق من الفرق"),
      items: [
        { name: "Acme", logo: IMG, href: "#" },
        { name: "Globex", logo: IMG, href: "#" },
        { name: "Initech", logo: IMG, href: "#" },
        { name: "Umbrella", logo: IMG, href: "#" },
      ],
    },
  },
  {
    key: "s-pricing",
    type: "pricing_preview",
    position: next(),
    content: {
      heading: L("Simple pricing", "أسعار بسيطة"),
      subheading: L("Pick a plan that fits.", "اختر الخطة المناسبة."),
      plans: [
        { name: L("Starter", "المبتدئ"), price: "$0", period: L("/mo", "/شهر"), features: [L("1 seat", "مقعد واحد"), L("Core courses", "دورات أساسية")], cta: link("Start free", "ابدأ مجاناً") },
        { name: L("Pro", "المحترف"), price: "$29", period: L("/mo", "/شهر"), highlighted: true, features: [L("Unlimited", "غير محدود"), L("Certificates", "شهادات"), L("Priority support", "دعم أولوية")], cta: link("Go Pro", "اشترك") },
        { name: L("Team", "الفريق"), price: "$99", period: L("/mo", "/شهر"), features: [L("10 seats", "10 مقاعد"), L("Analytics", "تحليلات")], cta: link("Contact us", "تواصل معنا") },
      ],
    },
  },
  {
    key: "s-comparison",
    type: "comparison_table",
    position: next(),
    content: {
      heading: L("Compare plans", "قارن الخطط"),
      columns: [L("Feature", "الميزة"), L("Starter", "المبتدئ"), L("Pro", "المحترف")],
      rows: [
        { cells: [L("Courses", "الدورات"), L("Core", "أساسية"), L("All", "الكل")] },
        { cells: [L("Certificates", "الشهادات"), L("—", "—"), L("Yes", "نعم")] },
        { cells: [L("Support", "الدعم"), L("Email", "بريد"), L("Priority", "أولوية")] },
      ],
    },
  },
  {
    key: "s-testimonials",
    type: "testimonials",
    position: next(),
    content: {
      items: [
        { quote: L("The best learning experience I've had.", "أفضل تجربة تعلم مررت بها."), author: "Layla H.", role: L("Product Manager", "مديرة منتج"), avatar: null },
        { quote: L("Certificates opened real doors for me.", "فتحت الشهادات لي أبواباً حقيقية."), author: "Omar K.", role: L("Engineer", "مهندس"), avatar: null },
      ],
    },
  },
  {
    key: "s-partners",
    type: "partners",
    position: next(),
    content: {
      items: [
        { name: "Acme", logo: IMG, href: "#" },
        { name: "Globex", logo: IMG, href: "#" },
        { name: "Initech", logo: IMG, href: "#" },
      ],
    },
  },
  {
    key: "s-gallery",
    type: "gallery",
    position: next(),
    content: {
      heading: L("Campus gallery", "معرض الحرم"),
      items: [
        { image: IMG, caption: L("Cohort day", "يوم الدفعة") },
        { image: IMG, caption: L("Workshop", "ورشة") },
        { image: IMG, caption: L("Graduation", "التخرج") },
      ],
    },
  },
  {
    key: "s-timeline",
    type: "timeline",
    position: next(),
    content: {
      heading: L("How it works", "كيف يعمل"),
      items: [
        { date: "01", title: L("Enroll", "سجّل"), description: L("Pick a program.", "اختر برنامجاً.") },
        { date: "02", title: L("Learn", "تعلّم"), description: L("Attend live or self-paced.", "احضر مباشرة أو ذاتياً.") },
        { date: "03", title: L("Certify", "احصل على الشهادة"), description: L("Earn a verifiable credential.", "احصل على شهادة موثقة.") },
      ],
    },
  },
  {
    key: "s-team",
    type: "team",
    position: next(),
    content: {
      heading: L("Meet the team", "تعرّف على الفريق"),
      items: [
        { name: "Sara N.", role: L("Head of Learning", "رئيسة التعلم"), avatar: IMG, href: "#" },
        { name: "Yusuf A.", role: L("Lead Instructor", "المدرب الرئيسي"), avatar: IMG, href: "#" },
      ],
    },
  },
  {
    key: "s-video",
    type: "video",
    position: next(),
    content: {
      heading: L("Watch the intro", "شاهد المقدمة"),
      url: "https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ",
      caption: L("A 2-minute overview.", "نظرة عامة في دقيقتين."),
    },
  },
  {
    key: "s-richtext",
    type: "rich_text",
    position: next(),
    content: {
      title: L("Our mission", "مهمتنا"),
      body: L(
        "<p>We make world-class professional education <strong>accessible</strong> in both Arabic and English.</p>",
        "<p>نجعل التعليم المهني عالمي المستوى <strong>متاحاً</strong> بالعربية والإنجليزية.</p>",
      ),
    },
  },
  {
    key: "s-cta",
    type: "cta",
    position: next(),
    content: {
      headline: L("Ready to start?", "هل أنت مستعد للبدء؟"),
      subheadline: L("Join thousands of learners today.", "انضم لآلاف المتعلمين اليوم."),
      cta_primary: link("Get started", "ابدأ الآن"),
      cta_secondary: link("Contact sales", "تواصل مع المبيعات"),
    },
  },
  {
    key: "s-newsletter",
    type: "newsletter",
    position: next(),
    content: {
      heading: L("Stay in the loop", "ابقَ على اطلاع"),
      subheading: L("Monthly updates, no spam.", "تحديثات شهرية بلا إزعاج."),
      placeholder: L("you@example.com", "you@example.com"),
      cta: L("Subscribe", "اشترك"),
      action_url: null,
    },
  },
  {
    key: "s-contact",
    type: "contact_strip",
    position: next(),
    content: {
      heading: L("Talk to our team", "تحدث مع فريقنا"),
      subheading: L("We usually reply within a day.", "نرد عادة خلال يوم."),
      phone: "+20 100 000 0000",
      email: "hello@example.com",
      address: L("Cairo, Egypt", "القاهرة، مصر"),
      cta: link("Book a call", "احجز مكالمة"),
    },
  },
  {
    key: "s-faq",
    type: "faq",
    position: next(),
    content: {
      items: [
        { question: L("Is there a free trial?", "هل هناك تجربة مجانية؟"), answer: L("Yes, the Starter plan is free forever.", "نعم، خطة المبتدئ مجانية دائماً.") },
        { question: L("Are certificates verifiable?", "هل الشهادات قابلة للتحقق؟"), answer: L("Every certificate has a QR-backed public verify page.", "لكل شهادة صفحة تحقق عامة برمز QR.") },
      ],
    },
  },
];
