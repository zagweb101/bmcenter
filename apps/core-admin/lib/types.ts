/** أنواع مشتركة تعكس استجابات core-api. */

export interface AuthUser {
  id: number;
  name: string;
  email: string;
  organization_id: number | null;
}

export interface LoginResponse {
  token: string;
  user: AuthUser;
}

export interface Person {
  id: number;
  first_name: string;
  last_name: string | null;
  full_name: string | null;
  phone_e164: string | null;
  email: string | null;
  birth_date: string | null;
  gender: string | null;
  nationality: string | null;
  is_merged: boolean;
  created_at: string | null;
}

export interface Paginated<T> {
  data: T[];
  links?: unknown;
  meta?: {
    current_page: number;
    last_page: number;
    total: number;
  };
}

export interface LeadSource {
  id: number;
  key: string;
  name_ar: string;
}

export interface Lead {
  id: number;
  full_name: string | null;
  phone_e164: string | null;
  email: string | null;
  stage: string;
  status: string;
  lost_reason: string | null;
  owner_user_id: number | null;
  person_id: number | null;
  lead_source_id: number | null;
  next_follow_up_at: string | null;
  created_at: string | null;
}

export const LEAD_STAGES = [
  "new",
  "assigned",
  "contacted",
  "qualified",
  "interested",
  "payment_pending",
  "enrolled",
  "nurturing",
  "lost",
] as const;

export const LEAD_STAGE_LABELS: Record<string, string> = {
  new: "جديد",
  assigned: "مُسنَد",
  contacted: "تم التواصل",
  qualified: "مؤهَّل",
  interested: "مهتم",
  payment_pending: "بانتظار الدفع",
  enrolled: "مسجَّل",
  nurturing: "رعاية",
  lost: "مفقود",
};
