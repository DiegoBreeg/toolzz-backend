"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState } from "react";
import Field from "@/components/Field";
import TopBar from "@/components/TopBar";
import { apiFetch, setToken } from "@/lib/api";
import { dictionary } from "@/lib/i18n";
import { useLanguage } from "@/app/providers";

export default function RegisterPage() {
  const router = useRouter();
  const { language } = useLanguage();
  const t = dictionary[language];
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError(null);
    setLoading(true);

    try {
      const data = await apiFetch<{ token: string }>("/register", {
        method: "POST",
        body: JSON.stringify({
          name,
          email,
          password,
          password_confirmation: confirmPassword,
        }),
      });

      setToken(data.token);
      router.push("/app");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Erro ao cadastrar");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="min-h-screen px-4 py-8">
      <div className="mx-auto flex w-full max-w-5xl flex-col gap-8">
        <TopBar />
        <section className="glass rounded-3xl p-8 fade-in">
          <h2 className="font-[var(--font-display)] text-3xl">{t.signUp}</h2>
          <p className="mt-2 text-sm text-[var(--muted)]">
            Crie sua conta e comece a conversar.
          </p>
          <form className="mt-6 grid gap-4 md:grid-cols-2" onSubmit={handleSubmit}>
            <Field id="name" label={t.name} value={name} onChange={setName} />
            <Field id="email" label={t.email} type="email" value={email} onChange={setEmail} />
            <Field
              id="password"
              label={t.password}
              type="password"
              value={password}
              onChange={setPassword}
            />
            <Field
              id="passwordConfirmation"
              label={t.confirmPassword}
              type="password"
              value={confirmPassword}
              onChange={setConfirmPassword}
            />
            <div className="md:col-span-2">
              {error ? (
                <p className="mb-3 text-sm text-red-400">{error}</p>
              ) : null}
              <button type="submit" className="btn-primary" disabled={loading}>
                {loading ? "..." : t.signUp}
              </button>
              <p className="mt-4 text-sm text-[var(--muted)]">
                Ja tem conta?{" "}
                <Link className="text-[var(--accent-2)]" href="/login">
                  {t.signIn}
                </Link>
              </p>
            </div>
          </form>
        </section>
      </div>
    </div>
  );
}
