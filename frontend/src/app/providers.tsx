"use client";

import { createContext, useContext, useEffect, useMemo, useState } from "react";

type Language = "pt" | "en";

type LanguageContextValue = {
  language: Language;
  setLanguage: (lang: Language) => void;
};

type ThemeContextValue = {
  theme: "light" | "dark";
  toggleTheme: () => void;
};

const LanguageContext = createContext<LanguageContextValue | undefined>(
  undefined
);
const ThemeContext = createContext<ThemeContextValue | undefined>(undefined);

export function useLanguage(): LanguageContextValue {
  const context = useContext(LanguageContext);
  if (!context) {
    throw new Error("useLanguage must be used within Providers");
  }
  return context;
}

export function useTheme(): ThemeContextValue {
  const context = useContext(ThemeContext);
  if (!context) {
    throw new Error("useTheme must be used within Providers");
  }
  return context;
}

export default function Providers({ children }: { children: React.ReactNode }) {
  const [language, setLanguageState] = useState<Language>(() => {
    if (typeof window === "undefined") {
      return "pt";
    }
    const stored = window.localStorage.getItem("lang");
    return stored === "pt" || stored === "en" ? stored : "pt";
  });
  const [theme, setTheme] = useState<"light" | "dark">(() => {
    if (typeof window === "undefined") {
      return "dark";
    }
    const stored = window.localStorage.getItem("theme");
    return stored === "light" || stored === "dark" ? stored : "dark";
  });

  useEffect(() => {
    window.localStorage.setItem("lang", language);
  }, [language]);

  useEffect(() => {
    const root = document.documentElement;
    root.classList.toggle("dark", theme === "dark");
    window.localStorage.setItem("theme", theme);
  }, [theme]);

  const languageValue = useMemo(
    () => ({
      language,
      setLanguage: setLanguageState,
    }),
    [language]
  );

  const themeValue = useMemo(
    () => ({
      theme,
      toggleTheme: () =>
        setTheme((current) => (current === "dark" ? "light" : "dark")),
    }),
    [theme]
  );

  return (
    <LanguageContext.Provider value={languageValue}>
      <ThemeContext.Provider value={themeValue}>
        {children}
      </ThemeContext.Provider>
    </LanguageContext.Provider>
  );
}
