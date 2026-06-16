"use client";

import { useEffect, useState } from "react";
import { usePathname, useRouter } from "next/navigation";
import Link from "next/link";
import { clearToken, getToken } from "@/lib/api";

const NAV = [
  { href: "/leads", label: "العملاء المحتملون" },
  { href: "/persons", label: "الأشخاص" },
  { href: "/enrollment", label: "الدورات والتسجيل" },
  { href: "/invoices", label: "الفواتير" },
  { href: "/consents", label: "الموافقات" },
  { href: "/privacy-requests", label: "طلبات الخصوصية" },
];

/**
 * هيكل لوحة الإدارة. Sidebar داكن (Navy) للاستخدام الطويل (PRD §7.9).
 * يحرس الصفحات: يحوّل إلى /login إن لم يوجد توكن.
 */
export function AppShell({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const pathname = usePathname();
  const [ready, setReady] = useState(false);

  useEffect(() => {
    if (!getToken()) {
      router.replace("/login");
      return;
    }
    setReady(true);
  }, [router]);

  function logout() {
    clearToken();
    router.replace("/login");
  }

  if (!ready) {
    return (
      <div className="flex flex-1 items-center justify-center text-brand-navy/50">
        جارٍ التحميل…
      </div>
    );
  }

  return (
    <div className="flex flex-1">
      <aside className="hidden w-60 shrink-0 flex-col bg-surface-sidebar text-white md:flex">
        <div className="border-b border-white/10 px-6 py-5">
          <span className="text-lg font-bold">بيت المصور</span>
          <span className="block text-xs text-white/50">لوحة الإدارة</span>
        </div>
        <nav className="flex-1 space-y-1 p-3">
          {NAV.map((item) => {
            const active = pathname.startsWith(item.href);
            return (
              <Link
                key={item.href}
                href={item.href}
                className={`block rounded-lg px-4 py-2.5 text-sm transition ${
                  active
                    ? "bg-brand-blue text-white"
                    : "text-white/70 hover:bg-white/10"
                }`}
              >
                {item.label}
              </Link>
            );
          })}
        </nav>
        <button
          onClick={logout}
          className="m-3 rounded-lg border border-white/15 px-4 py-2 text-sm text-white/80 transition hover:bg-white/10"
        >
          تسجيل الخروج
        </button>
      </aside>

      <div className="flex flex-1 flex-col">
        <header className="flex items-center justify-between border-b border-black/5 bg-white px-6 py-4 md:hidden">
          <span className="font-bold text-brand-navy">بيت المصور</span>
          <button onClick={logout} className="text-sm text-brand-blue">
            خروج
          </button>
        </header>
        <main className="flex-1 p-6">{children}</main>
      </div>
    </div>
  );
}
