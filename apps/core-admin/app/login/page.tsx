"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { api, setToken } from "@/lib/api";
import type { LoginResponse } from "@/lib/types";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";
import { Card } from "@/components/ui/Card";

export default function LoginPage() {
  const router = useRouter();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setLoading(true);
    try {
      const res = await api.post<LoginResponse>("/auth/login", {
        email,
        password,
        device_name: "core-admin",
      });
      setToken(res.token);
      router.push("/persons");
    } catch (err) {
      setError(err instanceof Error ? err.message : "تعذّر تسجيل الدخول");
    } finally {
      setLoading(false);
    }
  }

  return (
    <main className="flex flex-1 items-center justify-center p-6">
      <Card className="w-full max-w-sm bg-surface-elevated p-8">
        <div className="mb-8 text-center">
          <h1 className="text-2xl font-bold text-brand-navy">بيت المصور</h1>
          <p className="mt-1 text-sm text-brand-navy/60">لوحة الإدارة</p>
        </div>

        <form onSubmit={onSubmit} className="space-y-4">
          <Input
            id="email"
            type="email"
            label="البريد الإلكتروني"
            required
            autoComplete="username"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
          />
          <Input
            id="password"
            type="password"
            label="كلمة المرور"
            required
            autoComplete="current-password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            error={error ?? undefined}
          />
          <Button type="submit" disabled={loading} className="w-full">
            {loading ? "جارٍ الدخول…" : "تسجيل الدخول"}
          </Button>
        </form>
      </Card>
    </main>
  );
}
