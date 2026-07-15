import type { Metadata } from "next";
import { ContentPage } from "@/components/marketing/content-page";

const description =
  "HElbaron pricing is simple: pay per course with lifetime access, start with free courses, or scale your whole team with seat-based enterprise plans.";

export const metadata: Metadata = {
  title: "Pricing",
  description,
  alternates: { canonical: "/pricing" },
  openGraph: { title: "Pricing", description, url: "/pricing" },
};

export default function PricingPage() {
  return (
    <ContentPage
      eyebrow={{ en: "PRICING", ar: "الأسعار" }}
      title={{ en: "Simple,", ar: "تسعير بسيط" }}
      emphasis={{ en: "honest pricing.", ar: "وواضح." }}
      subtitle={{
        en: "No confusing tiers. Buy the courses you want, start with the free ones, and move to enterprise plans when your team grows.",
        ar: "لا باقات مربكة. اشترِ الدورات التي تريدها، وابدأ بالمجانية، وانتقل إلى خطط المؤسسات حين ينمو فريقك.",
      }}
      ctas={[
        { label: { en: "Browse courses", ar: "تصفّح الدورات" }, href: "/courses" },
        { label: { en: "Talk to enterprise", ar: "تحدّث مع فريق المؤسسات" }, href: "/enterprise" },
      ]}
      cards={[
        {
          icon: "Gift",
          title: { en: "Free courses", ar: "دورات مجانية" },
          body: {
            en: "Selected courses are completely free. Create an account and start learning right away — no payment required.",
            ar: "دورات مختارة مجانية تمامًا. أنشئ حسابًا وابدأ التعلّم فورًا — دون أي دفع.",
          },
          meta: { en: "Free forever", ar: "مجانية دائمًا" },
          cta: { label: { en: "Start free", ar: "ابدأ مجانًا" }, href: "/courses" },
        },
        {
          icon: "BookOpen",
          title: { en: "Per-course", ar: "لكل دورة" },
          body: {
            en: "Paid courses are priced individually, and the price is shown on each course page. Buy once and keep access to that course.",
            ar: "الدورات المدفوعة مسعّرة بشكل فردي، ويظهر السعر على صفحة كل دورة. ادفع مرة واحدة واحتفظ بالوصول إلى تلك الدورة.",
          },
          meta: { en: "Price shown per course", ar: "السعر معروض لكل دورة" },
          highlight: true,
          cta: { label: { en: "See course prices", ar: "شاهد أسعار الدورات" }, href: "/courses" },
        },
        {
          icon: "Building2",
          title: { en: "Enterprise & organizations", ar: "المؤسسات والمنظمات" },
          body: {
            en: "Roll out learning across your team with seat-based plans, admin controls, and reporting. Cohorts and workshops are quoted per program.",
            ar: "وفّر التعلّم لفريقك عبر خطط قائمة على المقاعد مع أدوات إدارية وتقارير. تُسعّر الأفواج والورش لكل برنامج.",
          },
          meta: { en: "Seat-based, tailored", ar: "حسب المقاعد ومخصّصة" },
          cta: { label: { en: "Request a quote", ar: "اطلب عرض سعر" }, href: "/enterprise" },
        },
      ]}
      sections={[
        {
          h: { en: "How pricing works", ar: "كيف يعمل التسعير" },
          body: [
            {
              en: "Individual courses are the core of HElbaron. Each course carries its own price — shown clearly on the course page — so you only pay for what you choose to learn. Many courses are free to help you get started.",
              ar: "الدورات الفردية هي جوهر HElbaron. لكل دورة سعرها الخاص — المعروض بوضوح على صفحتها — فتدفع فقط مقابل ما تختار تعلّمه. والعديد من الدورات مجانية لمساعدتك على البدء.",
            },
            {
              en: "Live cohorts and in-person workshops are structured programs with limited seats, so they are priced per program rather than as a subscription.",
              ar: "الأفواج المباشرة والورش الحضورية برامج منظّمة بمقاعد محدودة، لذا تُسعّر لكل برنامج بدلًا من الاشتراك.",
            },
          ],
        },
        {
          h: { en: "For teams and organizations", ar: "للفرق والمؤسسات" },
          body: [
            {
              en: "Organizations buy seats and assign them to members, with admin dashboards, progress reporting, and options like SSO and SCORM for larger deployments. Pricing is tailored to the size and scope of your program.",
              ar: "تشتري المؤسسات مقاعد وتوزّعها على الأعضاء، مع لوحات إدارية وتقارير تقدّم وخيارات مثل الدخول الموحّد وSCORM للنشر الأوسع. ويُصمَّم التسعير حسب حجم برنامجك ونطاقه.",
            },
          ],
        },
        {
          h: { en: "What every course includes", ar: "ما تتضمّنه كل دورة" },
          body: [
            {
              en: "Every completed course issues a verifiable certificate you can share, and the platform is fully bilingual (Arabic and English) with right-to-left support throughout.",
              ar: "كل دورة مكتملة تصدر شهادة قابلة للتحقّق يمكنك مشاركتها، والمنصة ثنائية اللغة بالكامل (العربية والإنجليزية) مع دعم الكتابة من اليمين إلى اليسار في كل مكان.",
            },
          ],
        },
      ]}
    />
  );
}
