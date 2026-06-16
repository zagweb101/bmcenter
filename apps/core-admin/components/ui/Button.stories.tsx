import type { Meta, StoryObj } from "@storybook/nextjs-vite";
import { Button } from "./Button";

const meta = {
  title: "UI/Button",
  component: Button,
  tags: ["autodocs"],
  args: { children: "زر" },
  argTypes: {
    variant: {
      control: "select",
      options: ["primary", "secondary", "danger", "ghost"],
    },
    size: { control: "inline-radio", options: ["sm", "md"] },
  },
} satisfies Meta<typeof Button>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Primary: Story = { args: { variant: "primary", children: "حفظ" } };
export const Secondary: Story = {
  args: { variant: "secondary", children: "إلغاء" },
};
export const Danger: Story = { args: { variant: "danger", children: "حذف" } };
export const Ghost: Story = { args: { variant: "ghost", children: "تخطٍّ" } };
export const Small: Story = { args: { size: "sm", children: "صغير" } };
export const Disabled: Story = {
  args: { disabled: true, children: "غير متاح" },
};
