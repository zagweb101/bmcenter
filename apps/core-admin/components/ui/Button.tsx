import { cn } from "@/lib/cn";

type Variant = "primary" | "secondary" | "danger" | "ghost";
type Size = "sm" | "md";

export interface ButtonProps
  extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: Variant;
  size?: Size;
}

const variants: Record<Variant, string> = {
  // الأزرار الأساسية باللون الأزرق (البراند بوك §7.3)
  primary: "bg-brand-blue text-white hover:bg-brand-blue/90",
  secondary:
    "bg-surface-elevated text-brand-navy border border-black/10 hover:bg-black/5",
  danger: "bg-brand-red text-white hover:bg-brand-red/90",
  ghost: "text-brand-navy hover:bg-black/5",
};

const sizes: Record<Size, string> = {
  sm: "px-3 py-1.5 text-xs",
  md: "px-4 py-2.5 text-sm",
};

export function Button({
  variant = "primary",
  size = "md",
  className,
  ...props
}: ButtonProps) {
  return (
    <button
      className={cn(
        "inline-flex items-center justify-center rounded-lg font-medium transition focus:outline-none focus:ring-2 focus:ring-brand-blue/30 disabled:opacity-60 disabled:pointer-events-none",
        variants[variant],
        sizes[size],
        className,
      )}
      {...props}
    />
  );
}
