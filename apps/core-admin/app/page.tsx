import { redirect } from "next/navigation";

// نقطة الدخول — التحويل إلى لوحة الأشخاص (الحارس يعيد غير المصادَق إلى /login).
export default function Home() {
  redirect("/persons");
}
