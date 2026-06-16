import type { Meta, StoryObj } from "@storybook/nextjs-vite";

// توثيق ألوان العلامة (البراند بوك §7.3/§7.4) داخل Storybook.
const COLORS: { name: string; varName: string; hex: string }[] = [
  { name: "red", varName: "--color-brand-red", hex: "#BE1622" },
  { name: "blue", varName: "--color-brand-blue", hex: "#1319B4" },
  { name: "purple", varName: "--color-brand-purple", hex: "#590159" },
  { name: "navy", varName: "--color-brand-navy", hex: "#010A2D" },
  { name: "rose", varName: "--color-brand-rose", hex: "#861755" },
  { name: "cyan", varName: "--color-brand-cyan", hex: "#81FFFE" },
  { name: "magenta", varName: "--color-brand-magenta", hex: "#FB42FD" },
  { name: "indigo", varName: "--color-brand-indigo", hex: "#414593" },
];

function Palette() {
  return (
    <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
      {COLORS.map((c) => (
        <div key={c.name} className="text-center">
          <div
            className="mb-2 h-20 w-full rounded-[var(--radius-card)] border border-black/5"
            style={{ background: `var(${c.varName})` }}
          />
          <p className="text-sm font-medium text-brand-navy">brand.{c.name}</p>
          <p className="text-xs text-brand-navy/50" dir="ltr">
            {c.hex}
          </p>
        </div>
      ))}
    </div>
  );
}

const meta = {
  title: "Design/Brand Colors",
  component: Palette,
  parameters: { layout: "fullscreen" },
} satisfies Meta<typeof Palette>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Palette_: Story = { name: "الألوان" };
