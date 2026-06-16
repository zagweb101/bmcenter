"use client";

import { useCallback, useEffect, useState } from "react";
import { AppShell } from "@/components/AppShell";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";
import { Card } from "@/components/ui/Card";
import { Badge } from "@/components/ui/Badge";
import { api, ApiError } from "@/lib/api";
import type { Cohort, Course, Enrollment, Paginated } from "@/lib/types";

const ENROLL_STATUS: Record<string, string> = {
  pending_invoice: "بانتظار الفاتورة",
  pending_payment: "بانتظار الدفع",
  pending_approval: "بانتظار اعتماد الخصم",
  waitlisted: "قائمة انتظار",
  confirmed: "مؤكَّد",
  cancelled: "ملغى",
};

export default function EnrollmentPage() {
  const [courses, setCourses] = useState<Course[]>([]);
  const [cohorts, setCohorts] = useState<Cohort[]>([]);
  const [loading, setLoading] = useState(true);
  const [msg, setMsg] = useState<string | null>(null);

  // course form
  const [courseName, setCourseName] = useState("");
  const [coursePrice, setCoursePrice] = useState("");
  // cohort form
  const [cohortCourseId, setCohortCourseId] = useState("");
  const [cohortName, setCohortName] = useState("");
  const [capacity, setCapacity] = useState("0");
  const [cohortPrice, setCohortPrice] = useState("");
  // enroll form
  const [enrollCohortId, setEnrollCohortId] = useState("");
  const [personId, setPersonId] = useState("");
  const [discount, setDiscount] = useState("0");

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const [c, co] = await Promise.all([
        api.get<Paginated<Course>>("/courses"),
        api.get<Paginated<Cohort>>("/cohorts"),
      ]);
      setCourses(c.data);
      setCohorts(co.data);
      if (!cohortCourseId && c.data[0]) setCohortCourseId(String(c.data[0].id));
      if (!enrollCohortId && co.data[0]) setEnrollCohortId(String(co.data[0].id));
    } finally {
      setLoading(false);
    }
  }, [cohortCourseId, enrollCohortId]);

  useEffect(() => {
    load();
  }, [load]);

  async function act(fn: () => Promise<unknown>, ok: string) {
    setMsg(null);
    try {
      await fn();
      setMsg(ok);
      await load();
    } catch (err) {
      if (err instanceof ApiError) setMsg(err.message);
      else setMsg(err instanceof Error ? err.message : "تعذّر التنفيذ");
    }
  }

  const addCourse = (e: React.FormEvent) => {
    e.preventDefault();
    return act(
      () => api.post("/courses", { name_ar: courseName, default_price: Number(coursePrice || 0) }),
      "تمت إضافة الدورة.",
    ).then(() => {
      setCourseName("");
      setCoursePrice("");
    });
  };

  const addCohort = (e: React.FormEvent) => {
    e.preventDefault();
    return act(
      () =>
        api.post("/cohorts", {
          course_id: Number(cohortCourseId),
          name: cohortName,
          capacity: Number(capacity || 0),
          price: Number(cohortPrice || 0),
          tax_rate: 15,
        }),
      "تمت إضافة المجموعة.",
    ).then(() => setCohortName(""));
  };

  const enroll = (e: React.FormEvent) => {
    e.preventDefault();
    return act(async () => {
      const res = await api.post<{ data: Enrollment }>("/enrollments", {
        cohort_id: Number(enrollCohortId),
        person_id: Number(personId),
        discount_amount: Number(discount || 0),
      });
      setMsg(`تم التسجيل — الحالة: ${ENROLL_STATUS[res.data.status] ?? res.data.status}`);
    }, "تم التسجيل.");
  };

  return (
    <AppShell>
      <div className="mx-auto max-w-5xl space-y-6">
        <h1 className="text-xl font-bold text-brand-navy">الدورات والتسجيل</h1>
        {msg && <p className="text-sm text-brand-purple">{msg}</p>}

        <div className="grid gap-4 md:grid-cols-2">
          <Card className="bg-surface-elevated">
            <h2 className="mb-3 font-medium text-brand-navy">إضافة دورة</h2>
            <form onSubmit={addCourse} className="space-y-3">
              <Input label="اسم الدورة" value={courseName} onChange={(e) => setCourseName(e.target.value)} required />
              <Input label="السعر الافتراضي" type="number" value={coursePrice} onChange={(e) => setCoursePrice(e.target.value)} />
              <Button type="submit">إضافة</Button>
            </form>
          </Card>

          <Card className="bg-surface-elevated">
            <h2 className="mb-3 font-medium text-brand-navy">إضافة مجموعة</h2>
            <form onSubmit={addCohort} className="space-y-3">
              <div>
                <label className="mb-1 block text-sm font-medium">الدورة</label>
                <select value={cohortCourseId} onChange={(e) => setCohortCourseId(e.target.value)} className="w-full rounded-lg border border-black/10 bg-white px-3 py-2 text-sm">
                  {courses.map((c) => (
                    <option key={c.id} value={c.id}>{c.name_ar}</option>
                  ))}
                </select>
              </div>
              <Input label="اسم المجموعة" value={cohortName} onChange={(e) => setCohortName(e.target.value)} required />
              <div className="flex gap-3">
                <Input label="السعة (0=غير محدود)" type="number" value={capacity} onChange={(e) => setCapacity(e.target.value)} />
                <Input label="السعر" type="number" value={cohortPrice} onChange={(e) => setCohortPrice(e.target.value)} />
              </div>
              <Button type="submit" disabled={courses.length === 0}>إضافة</Button>
            </form>
          </Card>
        </div>

        <Card className="bg-surface-elevated">
          <h2 className="mb-3 font-medium text-brand-navy">تسجيل طالب في مجموعة</h2>
          <form onSubmit={enroll} className="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div className="w-full">
              <label className="mb-1 block text-sm font-medium">المجموعة</label>
              <select value={enrollCohortId} onChange={(e) => setEnrollCohortId(e.target.value)} className="w-full rounded-lg border border-black/10 bg-white px-3 py-2 text-sm">
                {cohorts.map((c) => (
                  <option key={c.id} value={c.id}>{c.name} — {c.price} ر.س</option>
                ))}
              </select>
            </div>
            <Input label="رقم الشخص (Person ID)" type="number" value={personId} onChange={(e) => setPersonId(e.target.value)} required />
            <Input label="خصم" type="number" value={discount} onChange={(e) => setDiscount(e.target.value)} />
            <Button type="submit" disabled={cohorts.length === 0}>تسجيل</Button>
          </form>
        </Card>

        <div>
          <h2 className="mb-3 font-medium text-brand-navy">المجموعات</h2>
          {loading ? (
            <p className="text-brand-navy/50">جارٍ التحميل…</p>
          ) : cohorts.length === 0 ? (
            <p className="text-brand-navy/50">لا توجد مجموعات بعد.</p>
          ) : (
            <div className="space-y-2">
              {cohorts.map((c) => (
                <Card key={c.id} className="flex items-center justify-between">
                  <div>
                    <span className="font-medium text-brand-navy">{c.name}</span>
                    <p className="text-sm text-brand-navy/60" dir="ltr">
                      {c.price} {" ر.س "} · سعة {c.capacity === 0 ? "∞" : c.capacity}
                      {typeof c.seats_taken === "number" ? ` · مشغول ${c.seats_taken}` : ""}
                    </p>
                  </div>
                  <Badge tone="info">{c.status}</Badge>
                </Card>
              ))}
            </div>
          )}
        </div>
      </div>
    </AppShell>
  );
}
