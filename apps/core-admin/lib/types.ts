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

export interface Invoice {
  id: number;
  document_number: string | null;
  enrollment_id: number | null;
  invoice_type_code: string | null;
  transaction_type: string | null;
  currency: string;
  subtotal: string;
  tax_total: string;
  total_including_tax: string;
  status: string;
  issued_at: string | null;
  created_at: string | null;
}

export interface InvoiceBalance {
  invoice_id: number;
  total_including_tax: string;
  allocated: string;
  outstanding: string;
}

export const INVOICE_STATUS_LABELS: Record<string, string> = {
  draft: "مسودة",
  issued: "صادرة",
  reported: "مُبلَّغة (ZATCA)",
  cleared: "مخلَّصة (ZATCA)",
  rejected: "مرفوضة",
  archived: "مؤرشفة",
};

export interface Course {
  id: number;
  code: string | null;
  name_ar: string;
  default_price: string;
  is_active: boolean;
}

export interface Cohort {
  id: number;
  course_id: number;
  name: string;
  capacity: number;
  price: string;
  tax_rate: string;
  status: string;
  starts_on: string | null;
  ends_on: string | null;
  seats_taken?: number;
}

export interface Enrollment {
  id: number;
  cohort_id: number;
  person_id: number;
  status: string;
  total_snapshot: string;
  discount_amount_snapshot: string;
}
