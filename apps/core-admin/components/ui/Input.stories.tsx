import type { Meta, StoryObj } from "@storybook/nextjs-vite";
import { Input } from "./Input";

const meta = {
  title: "UI/Input",
  component: Input,
  tags: ["autodocs"],
  args: { label: "الاسم الأول", placeholder: "اكتب هنا" },
} satisfies Meta<typeof Input>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {};
export const WithValue: Story = { args: { defaultValue: "محمد" } };
export const WithError: Story = {
  args: { label: "البريد الإلكتروني", error: "البريد غير صحيح" },
};
