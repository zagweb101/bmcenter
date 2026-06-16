import { cn } from "@/lib/cn";

export interface InputProps
  extends React.InputHTMLAttributes<HTMLInputElement> {
  label?: string;
  error?: string;
}

export function Input({
  label,
  error,
  id,
  className,
  ...props
}: InputProps) {
  return (
    <div className="w-full">
      {label && (
        <label htmlFor={id} className="mb-1 block text-sm font-medium">
          {label}
        </label>
      )}
      <input
        id={id}
        aria-invalid={!!error}
        className={cn(
          "w-full rounded-lg border bg-white px-3 py-2 text-sm outline-none transition",
          error
            ? "border-brand-red focus:ring-2 focus:ring-brand-red/20"
            : "border-black/10 focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20",
          className,
        )}
        {...props}
      />
      {error && (
        <p role="alert" className="mt-1 text-xs text-brand-red">
          {error}
        </p>
      )}
    </div>
  );
}
