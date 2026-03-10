import Echo from "laravel-echo";
import Pusher from "pusher-js";
import { getApiBaseUrl } from "./api";

type EchoInstance = Echo<"reverb">;

let echo: EchoInstance | null = null;

export function getEcho(token: string): EchoInstance | null {
  if (typeof window === "undefined") {
    return null;
  }

  if (!process.env.NEXT_PUBLIC_REVERB_APP_KEY) {
    return null;
  }

  if (echo) {
    return echo;
  }

  const apiUrl = getApiBaseUrl();
  const baseUrl = apiUrl.replace(/\/api\/?$/, "");

  (window as typeof window & { Pusher: typeof Pusher }).Pusher = Pusher;

  echo = new Echo({
    broadcaster: "reverb",
    key: process.env.NEXT_PUBLIC_REVERB_APP_KEY,
    wsHost: process.env.NEXT_PUBLIC_REVERB_HOST || "localhost",
    wsPort: Number(process.env.NEXT_PUBLIC_REVERB_PORT || 8080),
    wssPort: Number(process.env.NEXT_PUBLIC_REVERB_PORT || 8080),
    forceTLS: (process.env.NEXT_PUBLIC_REVERB_SCHEME || "http") === "https",
    enabledTransports: ["ws", "wss"],
    authEndpoint: `${baseUrl}/broadcasting/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    },
  });

  return echo;
}
