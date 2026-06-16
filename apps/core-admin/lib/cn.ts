/** دمج أسماء الأصناف الشرطية ببساطة (بدون اعتماديات). */
export function cn(...parts: Array<string | false | null | undefined>): string {
  return parts.filter(Boolean).join(" ");
}
