"use client";

import { useCallback, useEffect, useState } from "react";
import { AppShell } from "@/components/AppShell";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";
import { Card } from "@/components/ui/Card";
import { Badge } from "@/components/ui/Badge";
import { api, ApiError } from "@/lib/api";
import {
  LEAD_STAGES,
  LEAD_STAGE_LABELS,
  type Lead,
  type LeadSource,
  type Paginated,
} from "@/lib/types";

function stageTone(stage: string): "neutral" | "success" | "danger" | "info" | "warning" {
  if (stage === "lost") return "danger";
  if (stage === "enrolled") return "success";
  if (stage === "payment_pending") return "warning";
  if (stage === "new") return "neutral";
  return "info";
}

export default function LeadsPage() {
  const [leads, setLeads] = useState<Lead[]>([]);
  const [sources, setSources] = useState<LeadSource[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [msg, setMsg] = useState<string | null>(null);

  const [fullName, setFullName] = useState("");
  const [phone, setPhone] = useState("");
  const [sourceId, setSourceId] = useState<string>("");
  const [saving, setSaving] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const [leadsRes, sourcesRes] = await Promise.all([
        api.get<Paginated<Lead>>("/leads"),
        api.get<{ data: LeadSource[] }>("/lead-sources"),
      ]);
      setLeads(leadsRes.data);
      setSources(sourcesRes.data);
      if (!sourceId && sourcesRes.data[0]) setSourceId(String(sourcesRes.data[0].id));
    } catch (err) {
      setError(err instanceof Error ? err.message : "تعذّر جلب البيانات");
    } finally {
      setLoading(false);
    }
  }, [sourceId]);

  useEffect(() => {
    load();
  }, [load]);

  async function addLead(e: React.FormEvent) {
    e.preventDefault();
    setMsg(null);
    setSaving(true);
    try {
      await api.post("/leads", {
        full_name: fullName,
        phone,
        lead_source_id: Number(sourceId),
      });
      setFullName("");
      setPhone("");
      setMsg("تمت إضافة العميل المحتمل.");
      await load();
    } catch (err) {
      setMsg(err instanceof Error ? err.message : "تعذّرت الإضافة");
    } finally {
      setSaving(false);
    }
  }

  async function convert(lead: Lead) {
    setMsg(null);
    try {
      const res = await api.post<{ matched: boolean }>(`/leads/${lead.id}/convert`);
      setMsg(res.matched ? "تم الربط بشخص مطابق موجود." : "تم إنشاء شخص جديد وربطه.");
      await load();
    } catch (err) {
      setMsg(err instanceof Error ? err.message : "تعذّر التحويل");
    }
  }

  async function changeStage(lead: Lead, stage: string) {
    if (stage === lead.stage) return;
    setMsg(null);
    let lost_reason: string | undefined;
    if (stage === "lost") {
      lost_reason = window.prompt("سبب الإغلاق كمفقود؟") ?? undefined;
      if (!lost_reason) return;
    }
    try {
      await api.patch(`/leads/${lead.id}/transition`, { stage, lost_reason });
      await load();
    } catch (err) {
      if (err instanceof ApiError && err.status === 422) {
        setMsg("لا يمكن ضبط هذه المرحلة يدويًا.");
      } else {
        setMsg(err instanceof Error ? err.message : "تعذّر تغيير المرحلة");
      }
    }
  }

  return (
    <AppShell>
      <div className="mx-auto max-w-5xl">
        <h1 className="mb-6 text-xl font-bold text-brand-navy">العملاء المحتملون</h1>

        <Card className="mb-6 bg-surface-elevated">
          <form onSubmit={addLead} className="flex flex-col gap-3 sm:flex-row sm:items-end">
            <Input
              label="الاسم"
              value={fullName}
              onChange={(e) => setFullName(e.target.value)}
              required
            />
            <Input
              label="الجوال"
              placeholder="05XXXXXXXX"
              value={phone}
              onChange={(e) => setPhone(e.target.value)}
            />
            <div className="w-full">
              <label className="mb-1 block text-sm font-medium">المصدر</label>
              <select
                value={sourceId}
                onChange={(e) => setSourceId(e.target.value)}
                className="w-full rounded-lg border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:border-brand-blue"
              >
                {sources.map((s) => (
                  <option key={s.id} value={s.id}>
                    {s.name_ar}
                  </option>
                ))}
              </select>
            </div>
            <Button type="submit" disabled={saving}>
              {saving ? "جارٍ الحفظ…" : "إضافة"}
            </Button>
          </form>
        </Card>

        {msg && <p className="mb-4 text-sm text-brand-purple">{msg}</p>}

        {loading ? (
          <p className="text-brand-navy/50">جارٍ التحميل…</p>
        ) : error ? (
          <p className="text-brand-red">{error}</p>
        ) : leads.length === 0 ? (
          <p className="text-brand-navy/50">لا يوجد عملاء محتملون بعد.</p>
        ) : (
          <div className="space-y-3">
            {leads.map((lead) => (
              <Card key={lead.id} className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div className="min-w-0">
                  <div className="flex items-center gap-2">
                    <span className="font-medium text-brand-navy">{lead.full_name}</span>
                    <Badge tone={stageTone(lead.stage)}>
                      {LEAD_STAGE_LABELS[lead.stage] ?? lead.stage}
                    </Badge>
                    {lead.person_id && <Badge tone="success">مرتبط بشخص</Badge>}
                  </div>
                  <p className="mt-1 text-sm text-brand-navy/60" dir="ltr">
                    {lead.phone_e164 ?? "—"}
                  </p>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                  <select
                    value={lead.stage}
                    onChange={(e) => changeStage(lead, e.target.value)}
                    className="rounded-lg border border-black/10 bg-white px-2 py-1.5 text-xs outline-none focus:border-brand-blue"
                  >
                    {LEAD_STAGES.filter((s) => s !== "enrolled").map((s) => (
                      <option key={s} value={s}>
                        {LEAD_STAGE_LABELS[s]}
                      </option>
                    ))}
                  </select>
                  {!lead.person_id && (
                    <Button size="sm" variant="secondary" onClick={() => convert(lead)}>
                      تحويل لشخص
                    </Button>
                  )}
                </div>
              </Card>
            ))}
          </div>
        )}
      </div>
    </AppShell>
  );
}
