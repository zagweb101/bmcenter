import { cn } from "@/lib/cn";

type Tone = "neutral" | "success" | "danger" | "info" | "warning";

// نغمات دلالية مؤقتة — ألوان Semantic النهائية تُعتمد بعد فحص Contrast (PRD §7.6).
const tones: Record<Tone, string> = {
  neutral: "bg-black/5 text-brand-navy",
  success: "bg-emerald-100 text-emerald-800",
  danger: "bg-brand-red/10 text-brand-red",
  info: "bg-brand-blue/10 text-brand-blue",
  warning: "bg-amber-100 text-amber-800",
};

export interface BadgeProps extends React.HTMLAttributes<HTMLSpanElement> {
  tone?: Tone;
}

export function Badge({ tone = "neutral", className, ...props }: BadgeProps) {
  return (
    <span
      className={cn(
        "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium",
        tones[tone],
        className,
      )}
      {...props}
    />
  );
}
