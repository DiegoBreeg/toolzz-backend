"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import { useRouter } from "next/navigation";
import TopBar from "@/components/TopBar";
import MessageBubble from "@/components/MessageBubble";
import { apiFetch, clearToken, getToken } from "@/lib/api";
import { dictionary } from "@/lib/i18n";
import { useLanguage } from "@/app/providers";
import { Conversation, Message, User } from "@/lib/types";
import { getEcho } from "@/lib/echo";

const DEFAULT_PAGE_SIZE = 20;

type Paginated<T> = {
  data: T[];
  current_page: number;
  last_page: number;
};

export default function ChatPage() {
  const router = useRouter();
  const { language } = useLanguage();
  const t = dictionary[language];
  const token = useMemo(() => getToken(), []);

  const [me, setMe] = useState<User | null>(null);
  const [conversations, setConversations] = useState<Conversation[]>([]);
  const [users, setUsers] = useState<User[]>([]);
  const [activeUser, setActiveUser] = useState<User | null>(null);
  const [messages, setMessages] = useState<Message[]>([]);
  const [messageInput, setMessageInput] = useState("");
  const [isTyping, setIsTyping] = useState(false);
  const [messagePage, setMessagePage] = useState(1);
  const [hasMoreMessages, setHasMoreMessages] = useState(false);
  const [loadingMessages, setLoadingMessages] = useState(false);
  const typingTimer = useRef<NodeJS.Timeout | null>(null);

  useEffect(() => {
    if (!token) {
      router.push("/login");
      return;
    }

    async function bootstrap() {
      try {
        const user = await apiFetch<User>("/user");
        setMe(user);
        await Promise.all([refreshConversations(), refreshUsers()]);
      } catch {
        clearToken();
        router.push("/login");
      }
    }

    bootstrap();
  }, [router, token]);

  useEffect(() => {
    if (!me || !token) {
      return;
    }

    const echo = getEcho(token);
    if (!echo) {
      return;
    }

    const channel = echo.private(`chat.${me.id}`);

    channel.listen("MessageSent", (event: {
      id: number;
      content: string;
      sender_id: number;
      sender_name: string;
      created_at: string;
    }) => {
      const incomingMessage: Message = {
        id: event.id,
        content: event.content,
        sender_id: event.sender_id,
        receiver_id: me.id,
        created_at: event.created_at,
      };

      setMessages((prev) => {
        if (!activeUser || event.sender_id !== activeUser.id) {
          return prev;
        }
        return [...prev, incomingMessage];
      });

      refreshConversations();
    });

    channel.listen("TypingStatusUpdated", (event: {
      sender_id: number;
      is_typing: boolean;
    }) => {
      if (activeUser && event.sender_id === activeUser.id) {
        setIsTyping(event.is_typing);
      }
    });

    return () => {
      echo.leave(`chat.${me.id}`);
    };
  }, [activeUser, me, token]);

  async function refreshConversations() {
    const response = await apiFetch<{ data: Paginated<Conversation> }>(
      `/conversations?page=1&per_page=${DEFAULT_PAGE_SIZE}`
    );
    setConversations(response.data.data ?? []);
  }

  async function refreshUsers(query = "") {
    const response = await apiFetch<{ data: { data: User[] } }>(
      `/users${query ? `?q=${encodeURIComponent(query)}` : ""}`
    );
    setUsers(response.data.data ?? []);
  }

  async function loadMessages(user: User, page = 1, replace = false) {
    setLoadingMessages(true);
    try {
      const response = await apiFetch<Paginated<Message>>(
        `/messages/${user.id}?page=${page}`
      );
      const ordered = [...response.data].reverse();
      setMessages((prev) => (replace ? ordered : [...ordered, ...prev]));
      setMessagePage(response.current_page);
      setHasMoreMessages(response.current_page < response.last_page);
    } finally {
      setLoadingMessages(false);
    }
  }

  async function handleSelectUser(user: User) {
    setActiveUser(user);
    setIsTyping(false);
    await loadMessages(user, 1, true);
  }

  async function handleSendMessage() {
    if (!activeUser || !messageInput.trim()) {
      return;
    }

    const payload = {
      receiver_id: activeUser.id,
      content: messageInput.trim(),
    };

    const response = await apiFetch<{ data: Message }>("/messages", {
      method: "POST",
      body: JSON.stringify(payload),
    });

    setMessages((prev) => [...prev, response.data]);
    setMessageInput("");
    setIsTyping(false);
    refreshConversations();
  }

  async function sendTypingStatus(status: boolean) {
    if (!activeUser) {
      return;
    }

    await apiFetch("/typing", {
      method: "POST",
      body: JSON.stringify({
        receiver_id: activeUser.id,
        is_typing: status,
      }),
    });
  }

  function handleTyping(value: string) {
    setMessageInput(value);

    if (!activeUser) {
      return;
    }

    if (typingTimer.current) {
      clearTimeout(typingTimer.current);
    }

    sendTypingStatus(true);

    typingTimer.current = setTimeout(() => {
      sendTypingStatus(false);
    }, 1500);
  }

  function handleLogout() {
    clearToken();
    router.push("/login");
  }

  if (!me) {
    return (
      <div className="min-h-screen px-4 py-8">
        <div className="mx-auto max-w-5xl">
          <TopBar />
          <div className="mt-6 rounded-3xl border border-white/10 bg-[var(--surface)] p-8">
            <p className="text-sm text-[var(--muted)]">Carregando...</p>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen px-4 py-8">
      <div className="mx-auto flex w-full max-w-6xl flex-col gap-6">
        <TopBar onLogout={handleLogout} />

        <div className="grid gap-6 lg:grid-cols-[320px_1fr]">
          <aside className="glass rounded-3xl p-6 fade-in">
            <div className="mb-4 flex items-center justify-between">
              <h2 className="font-[var(--font-display)] text-xl">{t.conversations}</h2>
              <span className="text-xs text-[var(--muted)]">
                {conversations.length}
              </span>
            </div>
            <div className="space-y-3">
              {conversations.length === 0 ? (
                <p className="text-sm text-[var(--muted)]">{t.emptyState}</p>
              ) : null}
              {conversations.map((conversation) => (
                <button
                  key={conversation.user.id}
                  type="button"
                  onClick={() => handleSelectUser(conversation.user)}
                  className={`w-full rounded-2xl border px-4 py-3 text-left transition ${
                    activeUser?.id === conversation.user.id
                      ? "border-[var(--accent-2)] bg-[var(--surface-strong)]"
                      : "border-transparent hover:border-white/10"
                  }`}
                >
                  <p className="text-sm font-semibold">
                    {conversation.user.name}
                  </p>
                  <p className="text-xs text-[var(--muted)] line-clamp-1">
                    {conversation.last_message.content}
                  </p>
                </button>
              ))}
            </div>
            <div className="mt-6">
              <h3 className="text-sm font-semibold">{t.users}</h3>
              <input
                className="input-base mt-2 w-full"
                placeholder={t.searchUsers}
                onChange={(event) => refreshUsers(event.target.value)}
              />
              <div className="mt-3 space-y-2">
                {users.map((user) => (
                  <button
                    key={user.id}
                    type="button"
                    onClick={() => handleSelectUser(user)}
                    className="w-full rounded-xl border border-transparent px-3 py-2 text-left text-sm transition hover:border-white/10"
                  >
                    {user.name}
                  </button>
                ))}
              </div>
            </div>
          </aside>

          <section className="glass rounded-3xl p-6 fade-in">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs uppercase tracking-[0.3em] text-[var(--muted)]">
                  {t.history}
                </p>
                <h2 className="font-[var(--font-display)] text-2xl">
                  {activeUser ? activeUser.name : t.pickUser}
                </h2>
              </div>
              {isTyping ? (
                <span className="text-xs text-[var(--accent-2)]">{t.typing}</span>
              ) : null}
            </div>

            <div className="mt-6 flex h-[420px] flex-col gap-3 overflow-y-auto rounded-2xl bg-[var(--surface)] p-4">
              {hasMoreMessages ? (
                <button
                  type="button"
                  className="btn-ghost self-center text-xs"
                  disabled={loadingMessages}
                  onClick={() =>
                    activeUser && loadMessages(activeUser, messagePage + 1)
                  }
                >
                  {loadingMessages ? "..." : t.loadMore}
                </button>
              ) : null}
              {messages.length === 0 ? (
                <p className="text-sm text-[var(--muted)]">Sem mensagens.</p>
              ) : null}
              {messages.map((message) => (
                <MessageBubble
                  key={message.id}
                  message={message}
                  isMine={message.sender_id === me.id}
                />
              ))}
            </div>

            <div className="mt-4 flex flex-col gap-3 sm:flex-row">
              <input
                className="input-base flex-1"
                value={messageInput}
                placeholder={t.messagePlaceholder}
                onChange={(event) => handleTyping(event.target.value)}
                disabled={!activeUser}
              />
              <button
                className="btn-primary"
                onClick={handleSendMessage}
                disabled={!activeUser}
              >
                {t.send}
              </button>
            </div>
          </section>
        </div>
      </div>
    </div>
  );
}
