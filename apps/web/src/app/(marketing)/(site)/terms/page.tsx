"use client";

import { LegalPage } from "@/components/marketing/legal-page";

export default function TermsPage() {
  return (
    <LegalPage
      title={{ en: "Terms of Service", ar: "شروط الخدمة" }}
      intro={{ en: "The terms that govern your use of HElbaron courses, cohorts, workshops, enterprise training, and advisory.", ar: "الشروط التي تحكم استخدامك لدورات وأفواج وورش وتدريب واستشارات HElbaron." }}
      sections={[
        { h: { en: "Using the platform", ar: "استخدام المنصة" }, p: { en: "Your account is personal. Content is licensed for your own learning and may not be redistributed.", ar: "حسابك شخصي. المحتوى مرخّص لتعلّمك الشخصي ولا يجوز إعادة توزيعه." } },
        { h: { en: "Payments & refunds", ar: "المدفوعات والاسترداد" }, p: { en: "Fees are shown before purchase. Refund eligibility depends on the program and is described at checkout.", ar: "تُعرض الرسوم قبل الشراء. تعتمد أهلية الاسترداد على البرنامج وتُوضّح عند الدفع." } },
        { h: { en: "Enterprise agreements", ar: "اتفاقيات المؤسسات" }, p: { en: "B2B / B2G engagements are governed by a separate signed agreement in addition to these terms.", ar: "تخضع مشاريع المؤسسات والحكومات لاتفاقية موقّعة منفصلة إضافةً لهذه الشروط." } },
      ]}
    />
  );
}
