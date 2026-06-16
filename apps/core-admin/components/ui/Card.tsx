import { cn } from "@/lib/cn";

export function Card({
  className,
  ...props
}: React.HTMLAttributes<HTMLDivElement>) {
  return (
    <div
      className={cn(
        "rounded-[var(--radius-card)] border border-black/5 bg-white p-4 shadow-sm",
        className,
      )}
      {...props}
    />
  );
}
