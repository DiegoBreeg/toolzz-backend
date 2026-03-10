export type ApiError = {
  message?: string;
  errors?: Record<string, string[]>;
};

const DEFAULT_API_URL = "http://localhost/api";

export function getApiBaseUrl(): string {
  return process.env.NEXT_PUBLIC_API_URL || DEFAULT_API_URL;
}

export function getToken(): string | null {
  if (typeof window === "undefined") {
    return null;
  }
  return window.localStorage.getItem("token");
}

export function setToken(token: string): void {
  if (typeof window !== "undefined") {
    window.localStorage.setItem("token", token);
  }
}

export function clearToken(): void {
  if (typeof window !== "undefined") {
    window.localStorage.removeItem("token");
  }
}

export async function apiFetch<T>(
  path: string,
  options: RequestInit = {}
): Promise<T> {
  const url = `${getApiBaseUrl()}${path}`;
  const token = getToken();

  const headers = new Headers(options.headers);
  headers.set("Accept", "application/json");

  if (options.body && !headers.has("Content-Type")) {
    headers.set("Content-Type", "application/json");
  }

  if (token) {
    headers.set("Authorization", `Bearer ${token}`);
  }

  const response = await fetch(url, {
    ...options,
    headers,
  });

  if (response.status === 204) {
    return {} as T;
  }

  const data = (await response.json()) as T | ApiError;

  if (!response.ok) {
    const error = data as ApiError;
    const message = error.message || "Request failed";
    throw new Error(message);
  }

  return data as T;
}
