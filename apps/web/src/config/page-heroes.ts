import type { Localized } from "./theme";

export type PageHeroConfig = {
  eyebrow: Localized;
  title: Localized;
  emphasis: Localized;
  subtitle: Localized;
  icon: string;
  stat?: { value: string; label: Localized };
};

export const pageHeroes: Record<string, PageHeroConfig> = {
  courses: {
    eyebrow: { en: "COURSES", ar: "الدورات" },
    title: { en: "Learn by", ar: "تعلّم" },
    emphasis: { en: "doing.", ar: "بالممارسة." },
    subtitle: { en: "On-demand programs across 12 business verticals — start anytime, learn at your own pace.", ar: "برامج عند الطلب عبر 12 مجالًا — ابدأ في أي وقت وتعلّم بالوتيرة التي تناسبك." },
    icon: "GraduationCap",
    stat: { value: "12", label: { en: "verticals", ar: "مجالًا" } },
  },
  categories: {
    eyebrow: { en: "CURRICULUM", ar: "المنهج" },
    title: { en: "Explore the", ar: "استكشف" },
    emphasis: { en: "verticals.", ar: "المجالات." },
    subtitle: { en: "Twelve MENA-focused tracks. Find the path that fits your goals and role.", ar: "اثنا عشر مسارًا يركّز على المنطقة. اعثر على المسار الذي يناسب أهدافك ودورك." },
    icon: "LayoutGrid",
    stat: { value: "12", label: { en: "verticals", ar: "مجالًا" } },
  },
  trainers: {
    eyebrow: { en: "MENTORS", ar: "المدرّبون" },
    title: { en: "Meet the", ar: "تعرّف على" },
    emphasis: { en: "mentors.", ar: "المدرّبين." },
    subtitle: { en: "Practitioners and operators from across the region who teach what they've actually done.", ar: "ممارسون وخبراء من أنحاء المنطقة يعلّمون ما طبّقوه فعلًا." },
    icon: "Users",
    stat: { value: "12", label: { en: "verticals", ar: "مجالًا" } },
  },
  products: {
    eyebrow: { en: "PRICING", ar: "الأسعار" },
    title: { en: "Plans &", ar: "خطط" },
    emphasis: { en: "packages.", ar: "وباقات." },
    subtitle: { en: "Simple pricing for individuals and teams. Upgrade, downgrade, or cancel anytime.", ar: "تسعير بسيط للأفراد والفرق. ترقية أو تخفيض أو إلغاء في أي وقت." },
    icon: "Tag",
  },
  cart: {
    eyebrow: { en: "CHECKOUT", ar: "الدفع" },
    title: { en: "Your", ar: "سلّة" },
    emphasis: { en: "cart.", ar: "المشتريات." },
    subtitle: { en: "Review your selection before you check out.", ar: "راجع اختيارك قبل إتمام الدفع." },
    icon: "ShoppingCart",
  },
  orders: {
    eyebrow: { en: "ACCOUNT", ar: "الحساب" },
    title: { en: "Your", ar: "طلباتك" },
    emphasis: { en: "orders.", ar: "وسجلّك." },
    subtitle: { en: "Your purchase history and invoices, all in one place.", ar: "سجلّ مشترياتك وفواتيرك في مكان واحد." },
    icon: "Receipt",
  },
  contracts: {
    eyebrow: { en: "AGREEMENTS", ar: "الاتفاقيات" },
    title: { en: "Your", ar: "اتفاقياتك" },
    emphasis: { en: "agreements.", ar: "للمراجعة." },
    subtitle: { en: "Review and accept the agreements tied to your enrollments.", ar: "راجع ووافق على الاتفاقيات المرتبطة بتسجيلاتك." },
    icon: "FileSignature",
  },
};
