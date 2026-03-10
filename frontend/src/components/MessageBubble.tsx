import { Message } from "@/lib/types";

type MessageBubbleProps = {
  message: Message;
  isMine: boolean;
};

export default function MessageBubble({ message, isMine }: MessageBubbleProps) {
  return (
    <div
      className={`max-w-[70%] rounded-2xl px-4 py-3 text-sm shadow-sm ${
        isMine
          ? "self-end bg-[var(--accent)] text-black"
          : "self-start bg-[var(--surface-strong)] text-[var(--foreground)]"
      }`}
    >
      <p className="whitespace-pre-wrap leading-relaxed">{message.content}</p>
      <span className="mt-2 block text-[10px] opacity-70">
        {new Date(message.created_at).toLocaleString()}
      </span>
    </div>
  );
}
