"use client";

import { useLanguage, useTheme } from "@/app/providers";
import { dictionary } from "@/lib/i18n";

type TopBarProps = {
  onLogout?: () => void;
};

export default function TopBar({ onLogout }: TopBarProps) {
  const { language, setLanguage } = useLanguage();
  const { theme, toggleTheme } = useTheme();
  const t = dictionary[language];

  return (
    <header className="flex flex-wrap items-center justify-between gap-3 rounded-3xl px-6 py-4 glass fade-in">
      <div>
        <p className="text-xs uppercase tracking-[0.4em] text-[var(--muted)]">
          Minimal realtime
        </p>
        <h1 className="font-[var(--font-display)] text-2xl">{t.appTitle}</h1>
      </div>
      <div className="flex items-center gap-2">
        <button
          type="button"
          className="btn-ghost text-sm"
          onClick={() => setLanguage(language === "pt" ? "en" : "pt")}
        >
          {language === "pt" ? "PT" : "EN"}
        </button>
        <button type="button" className="btn-ghost text-sm" onClick={toggleTheme}>
          {theme === "dark" ? "Light" : "Dark"}
        </button>
        {onLogout ? (
          <button type="button" className="btn-primary text-sm" onClick={onLogout}>
            {t.logout}
          </button>
        ) : null}
      </div>
    </header>
  );
}
