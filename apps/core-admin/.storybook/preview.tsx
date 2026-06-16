import type { Preview, Decorator } from "@storybook/nextjs-vite";
import { IBM_Plex_Sans_Arabic } from "next/font/google";
import "../app/globals.css";

// نفس خط الواجهة (البراند بوك §7.8) ليطابق Storybook الإنتاج.
const plexArabic = IBM_Plex_Sans_Arabic({
  variable: "--font-ar",
  subsets: ["arabic", "latin"],
  weight: ["300", "400", "500", "700"],
});

// كل القصص تُعرض RTL/عربي لمطابقة المنتج (PRD §6, §7).
const withRtl: Decorator = (Story) => (
  <div dir="rtl" lang="ar" className={`${plexArabic.variable} font-sans p-6`}>
    <Story />
  </div>
);

const preview: Preview = {
  decorators: [withRtl],
  parameters: {
    layout: "centered",
    controls: {
      matchers: {
        color: /(background|color)$/i,
        date: /Date$/i,
      },
    },
    a11y: {
      test: "todo",
    },
  },
};

export default preview;
