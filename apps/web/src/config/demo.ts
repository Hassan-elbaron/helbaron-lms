/**
 * Demo content layer — sample courses for a lively marketing demo.
 * Toggle everything on/off with DEMO_ENABLED (set false to rely on real API data only).
 * YouTube ids are illustrative (famous business/leadership TED talks) — swap for real course trailers.
 */
import type { Localized, Swatch } from "./theme";

export const DEMO_ENABLED = true;

export type DemoCourse = {
  id: string;
  title: Localized;
  code: string;
  color: Swatch;
  category: Localized;
  level: Localized;
  trainer: string;
  price: string;
  rating: string;
  lessons: number;
  hours: number;
  youtubeId: string;
};

export const featuredHeading = {
  eyebrow: { en: "FROM THE CATALOG", ar: "من الكتالوج" } as Localized,
  title1: { en: "Real courses.", ar: "دورات حقيقية." } as Localized,
  title2: { en: "Watch a preview.", ar: "شاهد المعاينة." } as Localized,
  subtitle: {
    en: "A taste of what's inside HElbaron — hands-on programs built for MENA business.",
    ar: "لمحة عمّا في HElbaron — برامج عملية مبنية لأعمال المنطقة.",
  } as Localized,
  cta: { en: "Browse all courses", ar: "تصفّح كل الدورات" } as Localized,
};

export const demoCourses: DemoCourse[] = [
  { id: "d1", code: "PM", color: "teal", title: { en: "Project Management Foundations", ar: "أساسيات إدارة المشاريع" }, category: { en: "Project Management", ar: "إدارة المشاريع" }, level: { en: "Beginner", ar: "مبتدئ" }, trainer: "Yara Adel", price: "$29", rating: "4.9", lessons: 42, hours: 6, youtubeId: "u4ZoJKF_VuA" },
  { id: "d2", code: "LD", color: "teal", title: { en: "Leadership in the Modern Workplace", ar: "القيادة في بيئة العمل الحديثة" }, category: { en: "Leadership", ar: "القيادة" }, level: { en: "Intermediate", ar: "متوسط" }, trainer: "Omar Farouk", price: "$34", rating: "4.8", lessons: 36, hours: 5, youtubeId: "arj7oStGLkU" },
  { id: "d3", code: "AI", color: "gold", title: { en: "Business AI for Decision Makers", ar: "الذكاء الاصطناعي للأعمال لصنّاع القرار" }, category: { en: "Business AI", ar: "الذكاء الاصطناعي للأعمال" }, level: { en: "Intermediate", ar: "متوسط" }, trainer: "Nour Hassan", price: "$39", rating: "5.0", lessons: 28, hours: 4, youtubeId: "Ks-_Mh1QhMc" },
  { id: "d4", code: "MK", color: "gold", title: { en: "Marketing Strategy Masterclass", ar: "ماستر كلاس استراتيجية التسويق" }, category: { en: "Marketing Strategies", ar: "استراتيجيات التسويق" }, level: { en: "Advanced", ar: "متقدّم" }, trainer: "Laila Mansour", price: "$44", rating: "4.7", lessons: 48, hours: 8, youtubeId: "u4ZoJKF_VuA" },
  { id: "d5", code: "FN", color: "copper", title: { en: "Finance & Analysis Essentials", ar: "أساسيات المالية والتحليل" }, category: { en: "Finance & Analysis", ar: "المالية والتحليل" }, level: { en: "Beginner", ar: "مبتدئ" }, trainer: "Karim Saleh", price: "$32", rating: "4.8", lessons: 40, hours: 6, youtubeId: "arj7oStGLkU" },
  { id: "d6", code: "EN", color: "copper", title: { en: "Entrepreneurship: 0 to Launch", ar: "ريادة الأعمال: من الصفر للإطلاق" }, category: { en: "Entrepreneurship", ar: "ريادة الأعمال" }, level: { en: "Beginner", ar: "مبتدئ" }, trainer: "Hana Zaki", price: "$36", rating: "4.9", lessons: 52, hours: 9, youtubeId: "Ks-_Mh1QhMc" },
  { id: "d7", code: "SL", color: "red", title: { en: "Sales Management Playbook", ar: "دليل إدارة المبيعات" }, category: { en: "Sales Management", ar: "إدارة المبيعات" }, level: { en: "Intermediate", ar: "متوسط" }, trainer: "Tarek Fahmy", price: "$30", rating: "4.6", lessons: 33, hours: 5, youtubeId: "u4ZoJKF_VuA" },
  { id: "d8", code: "BS", color: "teal", title: { en: "Business Strategy & Growth", ar: "استراتيجية الأعمال والنمو" }, category: { en: "Business Strategies", ar: "استراتيجيات الأعمال" }, level: { en: "Advanced", ar: "متقدّم" }, trainer: "Salma Nabil", price: "$42", rating: "4.9", lessons: 45, hours: 7, youtubeId: "arj7oStGLkU" },
  { id: "d9", code: "IT", color: "teal", title: { en: "Investment & Trading Basics", ar: "أساسيات الاستثمار والتداول" }, category: { en: "Investment & Trading", ar: "الاستثمار والتداول" }, level: { en: "Beginner", ar: "مبتدئ" }, trainer: "Amir Gamal", price: "$38", rating: "4.7", lessons: 30, hours: 5, youtubeId: "Ks-_Mh1QhMc" },
];
