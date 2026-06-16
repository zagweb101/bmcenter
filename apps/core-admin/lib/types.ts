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
