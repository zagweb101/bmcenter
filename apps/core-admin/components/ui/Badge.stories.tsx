import type { Meta, StoryObj } from "@storybook/nextjs-vite";
import { Badge } from "./Badge";

const meta = {
  title: "UI/Badge",
  component: Badge,
  tags: ["autodocs"],
  args: { children: "حالة" },
  argTypes: {
    tone: {
      control: "select",
      options: ["neutral", "success", "danger", "info", "warning"],
    },
  },
} satisfies Meta<typeof Badge>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Neutral: Story = { args: { tone: "neutral", children: "مسوّدة" } };
export const Success: Story = { args: { tone: "success", children: "مؤكّد" } };
export const Danger: Story = { args: { tone: "danger", children: "مرفوض" } };
export const Info: Story = { args: { tone: "info", children: "قيد المعالجة" } };
export const Warning: Story = {
  args: { tone: "warning", children: "بانتظار" },
};
