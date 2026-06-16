"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { api, setToken } from "@/lib/api";
import type { LoginResponse } from "@/lib/types";

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
      <div className="w-full max-w-sm rounded-[var(--radius-card)] border border-black/5 bg-surface-elevated p-8 shadow-sm">
        <div className="mb-8 text-center">
          <h1 className="text-2xl font-bold text-brand-navy">بيت المصور</h1>
          <p className="mt-1 text-sm text-brand-navy/60">لوحة الإدارة</p>
        </div>

        <form onSubmit={onSubmit} className="space-y-4">
          <div>
            <label htmlFor="email" className="mb-1 block text-sm font-medium">
              البريد الإلكتروني
            </label>
            <input
              id="email"
              type="email"
              required
              autoComplete="username"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="w-full rounded-lg border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20"
            />
          </div>

          <div>
            <label htmlFor="password" className="mb-1 block text-sm font-medium">
              كلمة المرور
            </label>
            <input
              id="password"
              type="password"
              required
              autoComplete="current-password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="w-full rounded-lg border border-black/10 bg-white px-3 py-2 text-sm outline-none focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20"
            />
          </div>

          {error && (
            <p
              role="alert"
              className="rounded-lg bg-brand-red/10 px-3 py-2 text-sm text-brand-red"
            >
              {error}
            </p>
          )}

          <button
            type="submit"
            disabled={loading}
            className="w-full rounded-lg bg-brand-blue px-4 py-2.5 text-sm font-medium text-white transition hover:bg-brand-blue/90 disabled:opacity-60"
          >
            {loading ? "جارٍ الدخول…" : "تسجيل الدخول"}
          </button>
        </form>
      </div>
    </main>
  );
}
