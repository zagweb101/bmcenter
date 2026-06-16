"use client";

import { useCallback, useEffect, useState } from "react";
import { AppShell } from "@/components/AppShell";
import { api, ApiError } from "@/lib/api";
import type { Paginated, Person } from "@/lib/types";

export default function PersonsPage() {
  const [persons, setPersons] = useState<Person[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // نموذج إضافة بسيط
  const [firstName, setFirstName] = useState("");
  const [phone, setPhone] = useState("");
  const [formMsg, setFormMsg] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await api.get<Paginated<Person>>("/persons");
      setPersons(res.data);
    } catch (err) {
      setError(err instanceof Error ? err.message : "تعذّر جلب الأشخاص");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  async function addPerson(e: React.FormEvent) {
    e.preventDefault();
    setFormMsg(null);
    setSaving(true);
    try {
      await api.post("/persons", { first_name: firstName, phone });
      setFirstName("");
      setPhone("");
      setFormMsg("تمت الإضافة بنجاح.");
      await load();
    } catch (err) {
      if (err instanceof ApiError && err.status === 409) {
        setFormMsg("يوجد شخص مطابق بالفعل (منع التكرار).");
      } else {
        setFormMsg(err instanceof Error ? err.message : "تعذّرت الإضافة");
      }
    } finally {
      setSaving(false);
    }
  }

  return (
    <AppShell>
      <div className="mx-auto max-w-4xl">
        <h1 className="mb-6 text-xl font-bold text-brand-navy">الأشخاص</h1>

        <form
          onSubmit={addPerson}
          className="mb-6 flex flex-col gap-3 rounded-[var(--radius-card)] border border-black/5 bg-surface-elevated p-4 sm:flex-row sm:items-end"
        >
          <div className="flex-1">
            <label className="mb-1 block text-sm font-medium">الاسم الأول</label>
            <input
              required
              value={firstName}
              onChange={(e) => setFirstName(e.target.value)}
              className="w-full rounded-lg border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:border-brand-blue"
            />
          </div>
          <div className="flex-1">
            <label className="mb-1 block text-sm font-medium">الجوال</label>
            <input
              value={phone}
              onChange={(e) => setPhone(e.target.value)}
              placeholder="05XXXXXXXX"
              className="w-full rounded-lg border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:border-brand-blue"
            />
          </div>
          <button
            type="submit"
            disabled={saving}
            className="rounded-lg bg-brand-blue px-5 py-2 text-sm font-medium text-white transition hover:bg-brand-blue/90 disabled:opacity-60"
          >
            {saving ? "جارٍ الحفظ…" : "إضافة"}
          </button>
        </form>

        {formMsg && (
          <p className="mb-4 text-sm text-brand-purple">{formMsg}</p>
        )}

        {loading ? (
          <p className="text-brand-navy/50">جارٍ التحميل…</p>
        ) : error ? (
          <p className="text-brand-red">{error}</p>
        ) : persons.length === 0 ? (
          <p className="text-brand-navy/50">لا يوجد أشخاص بعد.</p>
        ) : (
          <>
            {/* جدول على الشاشات الكبيرة */}
            <table className="hidden w-full overflow-hidden rounded-[var(--radius-card)] border border-black/5 text-sm sm:table">
              <thead className="bg-surface-elevated text-right text-brand-navy/70">
                <tr>
                  <th className="px-4 py-3 font-medium">الاسم</th>
                  <th className="px-4 py-3 font-medium">الجوال</th>
                  <th className="px-4 py-3 font-medium">البريد</th>
                </tr>
              </thead>
              <tbody>
                {persons.map((p) => (
                  <tr key={p.id} className="border-t border-black/5">
                    <td className="px-4 py-3">{p.full_name || p.first_name}</td>
                    <td className="px-4 py-3" dir="ltr">
                      {p.phone_e164 ?? "—"}
                    </td>
                    <td className="px-4 py-3">{p.email ?? "—"}</td>
                  </tr>
                ))}
              </tbody>
            </table>

            {/* بطاقات على الجوال (PRD §7.9) */}
            <ul className="space-y-3 sm:hidden">
              {persons.map((p) => (
                <li
                  key={p.id}
                  className="rounded-[var(--radius-card)] border border-black/5 bg-white p-4"
                >
                  <p className="font-medium text-brand-navy">
                    {p.full_name || p.first_name}
                  </p>
                  <p className="mt-1 text-sm text-brand-navy/60" dir="ltr">
                    {p.phone_e164 ?? "—"}
                  </p>
                </li>
              ))}
            </ul>
          </>
        )}
      </div>
    </AppShell>
  );
}
