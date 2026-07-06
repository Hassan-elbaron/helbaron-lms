"use client";

import { LegalPage } from "@/components/marketing/legal-page";

export default function PrivacyPage() {
  return (
    <LegalPage
      title={{ en: "Privacy Policy", ar: "سياسة الخصوصية" }}
      intro={{ en: "How HElbaron collects, uses, and protects your information across our academy and services.", ar: "كيف تجمع HElbaron معلوماتك وتستخدمها وتحميها عبر أكاديميتنا وخدماتنا." }}
      sections={[
        { h: { en: "Information we collect", ar: "المعلومات التي نجمعها" }, p: { en: "Account details, learning progress, and usage data needed to deliver courses, cohorts, and enterprise programs.", ar: "بيانات الحساب وتقدّم التعلّم وبيانات الاستخدام اللازمة لتقديم الدورات والأفواج وبرامج المؤسسات." } },
        { h: { en: "How we use it", ar: "كيف نستخدمها" }, p: { en: "To personalize learning, issue certificates, provide support, and improve the platform. We do not sell your data.", ar: "لتخصيص التعلّم وإصدار الشهادات وتقديم الدعم وتحسين المنصة. لا نبيع بياناتك." } },
        { h: { en: "Your rights", ar: "حقوقك" }, p: { en: "You may access, correct, export, or delete your data at any time by contacting our team.", ar: "يمكنك الوصول لبياناتك أو تصحيحها أو تصديرها أو حذفها في أي وقت بالتواصل مع فريقنا." } },
      ]}
    />
  );
}
