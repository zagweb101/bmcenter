import type { Metadata } from "next";
import { IBM_Plex_Sans_Arabic, Source_Sans_3 } from "next/font/google";
import "./globals.css";

// العربية أولًا — IBM Plex Sans Arabic (البراند بوك §7.8)
const plexArabic = IBM_Plex_Sans_Arabic({
  variable: "--font-ar",
  subsets: ["arabic", "latin"],
  weight: ["300", "400", "500", "700"],
});

// الإنجليزية — عائلة Source (البراند بوك §7.8)
const sourceSans = Source_Sans_3({
  variable: "--font-en",
  subsets: ["latin"],
  weight: ["300", "400", "500", "700"],
});

export const metadata: Metadata = {
  title: "بيت المصور — لوحة الإدارة",
  description: "BAYT ALMOSWER ACADEMY — Core Admin",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html
      lang="ar"
      dir="rtl"
      className={`${plexArabic.variable} ${sourceSans.variable} h-full antialiased`}
    >
      <body className="min-h-full flex flex-col bg-surface-app text-brand-navy">
        {children}
      </body>
    </html>
  );
}
