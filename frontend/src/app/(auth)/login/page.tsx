"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState } from "react";
import Field from "@/components/Field";
import TopBar from "@/components/TopBar";
import { apiFetch, setToken } from "@/lib/api";
import { dictionary } from "@/lib/i18n";
import { useLanguage } from "@/app/providers";

export default function LoginPage() {
  const router = useRouter();
  const { language } = useLanguage();
  const t = dictionary[language];
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError(null);
    setLoading(true);

    try {
      const data = await apiFetch<{ token: string }>("/login", {
        method: "POST",
        body: JSON.stringify({ email, password }),
      });

      setToken(data.token);
      router.push("/app");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Erro ao entrar");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="min-h-screen px-4 py-8">
      <div className="mx-auto flex w-full max-w-5xl flex-col gap-8">
        <TopBar />
        <div className="grid gap-8 md:grid-cols-[1.1fr_0.9fr]">
          <section className="glass rounded-3xl p-8 fade-in">
            <h2 className="font-[var(--font-display)] text-3xl">
              {t.signIn}
            </h2>
            <p className="mt-2 text-sm text-[var(--muted)]">
              Acesse com suas credenciais para iniciar o chat.
            </p>
            <form className="mt-6 flex flex-col gap-4" onSubmit={handleSubmit}>
              <Field
                id="email"
                label={t.email}
                type="email"
                value={email}
                onChange={setEmail}
              />
              <Field
                id="password"
                label={t.password}
                type="password"
                value={password}
                onChange={setPassword}
              />
              {error ? (
                <p className="text-sm text-red-400">{error}</p>
              ) : null}
              <button type="submit" className="btn-primary" disabled={loading}>
                {loading ? "..." : t.signIn}
              </button>
            </form>
            <p className="mt-6 text-sm text-[var(--muted)]">
              Ainda nao tem conta?{" "}
              <Link className="text-[var(--accent-2)]" href="/register">
                {t.signUp}
              </Link>
            </p>
          </section>
          <aside className="rounded-3xl border border-white/10 bg-[var(--surface-strong)] p-8 text-sm text-[var(--muted)] fade-in">
            <p className="uppercase tracking-[0.3em] text-xs">Fluxo minimo</p>
            <h3 className="mt-4 font-[var(--font-display)] text-xl text-[var(--foreground)]">
              Chat direto ao ponto
            </h3>
            <ul className="mt-4 space-y-3">
              <li>Cadastro e login por token</li>
              <li>Lista de usuarios e conversas</li>
              <li>Historico paginado com envio rapido</li>
            </ul>
          </aside>
        </div>
      </div>
    </div>
  );
}
