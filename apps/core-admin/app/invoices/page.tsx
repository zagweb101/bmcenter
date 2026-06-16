"use client";

import { useCallback, useEffect, useState } from "react";
import { AppShell } from "@/components/AppShell";
import { Button } from "@/components/ui/Button";
import { Card } from "@/components/ui/Card";
import { Badge } from "@/components/ui/Badge";
import { api } from "@/lib/api";
import {
  INVOICE_STATUS_LABELS,
  type Invoice,
  type InvoiceBalance,
  type Paginated,
} from "@/lib/types";

function statusTone(s: string): "neutral" | "success" | "danger" | "info" | "warning" {
  if (s === "draft") return "neutral";
  if (s === "issued") return "warning";
  if (s === "reported" || s === "cleared") return "success";
  if (s === "rejected") return "danger";
  return "info";
}

export default function InvoicesPage() {
  const [invoices, setInvoices] = useState<Invoice[]>([]);
  const [balances, setBalances] = useState<Record<number, InvoiceBalance>>({});
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [msg, setMsg] = useState<string | null>(null);
  const [busy, setBusy] = useState<number | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await api.get<Paginated<Invoice>>("/invoices");
      setInvoices(res.data);
      const entries = await Promise.all(
        res.data.map(async (inv) => {
          try {
            const b = await api.get<InvoiceBalance>(`/invoices/${inv.id}/balance`);
            return [inv.id, b] as const;
          } catch {
            return [inv.id, null] as const;
          }
        }),
      );
      const map: Record<number, InvoiceBalance> = {};
      for (const [id, b] of entries) if (b) map[id] = b;
      setBalances(map);
    } catch (err) {
      setError(err instanceof Error ? err.message : "تعذّر جلب الفواتير");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  async function run(id: number, fn: () => Promise<unknown>, okMsg: string) {
    setMsg(null);
    setBusy(id);
    try {
      await fn();
      setMsg(okMsg);
      await load();
    } catch (err) {
      setMsg(err instanceof Error ? err.message : "تعذّر تنفيذ الإجراء");
    } finally {
      setBusy(null);
    }
  }

  const issue = (inv: Invoice) =>
    run(inv.id, () => api.post(`/invoices/${inv.id}/issue`), "تم إصدار الفاتورة.");
  const submitZatca = (inv: Invoice) =>
    run(inv.id, () => api.post(`/invoices/${inv.id}/submit-zatca`), "تم الإرسال إلى ZATCA (محاكاة).");
  const payFull = (inv: Invoice) => {
    const outstanding = balances[inv.id]?.outstanding ?? inv.total_including_tax;
    return run(
      inv.id,
      () =>
        api.post("/payments", {
          method: "cash",
          amount: outstanding,
          allocations: [{ invoice_id: inv.id, amount: outstanding }],
        }),
      "تم تسجيل الدفعة وإصدار السند.",
    );
  };

  return (
    <AppShell>
      <div className="mx-auto max-w-5xl">
        <h1 className="mb-6 text-xl font-bold text-brand-navy">الفواتير</h1>
        {msg && <p className="mb-4 text-sm text-brand-purple">{msg}</p>}

        {loading ? (
          <p className="text-brand-navy/50">جارٍ التحميل…</p>
        ) : error ? (
          <p className="text-brand-red">{error}</p>
        ) : invoices.length === 0 ? (
          <p className="text-brand-navy/50">لا توجد فواتير بعد (تُنشأ من التسجيل).</p>
        ) : (
          <div className="space-y-3">
            {invoices.map((inv) => {
              const bal = balances[inv.id];
              const outstanding = bal?.outstanding ?? inv.total_including_tax;
              const paid = bal && Number(bal.outstanding) === 0;
              return (
                <Card key={inv.id} className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                  <div>
                    <div className="flex items-center gap-2">
                      <span className="font-medium text-brand-navy">
                        {inv.document_number ?? `#${inv.id}`}
                      </span>
                      <Badge tone={statusTone(inv.status)}>
                        {INVOICE_STATUS_LABELS[inv.status] ?? inv.status}
                      </Badge>
                      {paid && <Badge tone="success">مسدّدة</Badge>}
                    </div>
                    <p className="mt-1 text-sm text-brand-navy/60" dir="ltr">
                      الإجمالي {inv.total_including_tax} {inv.currency} · المتبقّي {outstanding}
                    </p>
                  </div>

                  <div className="flex flex-wrap items-center gap-2">
                    {inv.status === "draft" && (
                      <Button size="sm" disabled={busy === inv.id} onClick={() => issue(inv)}>
                        إصدار
                      </Button>
                    )}
                    {inv.status === "issued" && (
                      <Button size="sm" variant="secondary" disabled={busy === inv.id} onClick={() => submitZatca(inv)}>
                        إرسال ZATCA
                      </Button>
                    )}
                    {!paid && Number(outstanding) > 0 && (
                      <Button size="sm" disabled={busy === inv.id} onClick={() => payFull(inv)}>
                        تحصيل نقدي
                      </Button>
                    )}
                  </div>
                </Card>
              );
            })}
          </div>
        )}
      </div>
    </AppShell>
  );
}
