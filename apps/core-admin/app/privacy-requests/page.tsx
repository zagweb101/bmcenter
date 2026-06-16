"use client";

import { AppShell } from "@/components/AppShell";

export default function PrivacyRequestsPage() {
  return (
    <AppShell>
      <div className="mx-auto max-w-4xl">
        <h1 className="mb-2 text-xl font-bold text-brand-navy">
          طلبات الخصوصية
        </h1>
        <p className="text-brand-navy/50">
          واجهة طلبات حقوق صاحب البيانات قيد الإنشاء — الـ API جاهز (PRD §19.5).
        </p>
      </div>
    </AppShell>
  );
}
