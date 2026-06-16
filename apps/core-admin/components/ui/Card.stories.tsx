import type { Meta, StoryObj } from "@storybook/nextjs-vite";
import { Card } from "./Card";

const meta = {
  title: "UI/Card",
  component: Card,
  tags: ["autodocs"],
} satisfies Meta<typeof Card>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
  args: {
    children: (
      <div>
        <h3 className="font-bold text-brand-navy">بطاقة</h3>
        <p className="mt-1 text-sm text-brand-navy/60">
          محتوى داخل بطاقة بنصف قطر 12px (البراند بوك §7.10).
        </p>
      </div>
    ),
  },
};
