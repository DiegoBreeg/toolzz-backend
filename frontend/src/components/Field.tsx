type FieldProps = {
  label: string;
  id: string;
  type?: string;
  value: string;
  onChange: (value: string) => void;
  placeholder?: string;
};

export default function Field({
  label,
  id,
  type = "text",
  value,
  onChange,
  placeholder,
}: FieldProps) {
  return (
    <label className="flex flex-col gap-2 text-sm">
      <span className="text-[var(--muted)]">{label}</span>
      <input
        id={id}
        type={type}
        value={value}
        onChange={(event) => onChange(event.target.value)}
        placeholder={placeholder}
        className="input-base"
      />
    </label>
  );
}
