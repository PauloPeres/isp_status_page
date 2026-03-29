export interface User {
  id: number;
  username: string;
  email: string;
  is_super_admin: boolean;
  two_factor_enabled: boolean;
  timezone?: string;
  locale?: string;
  last_login_at?: string;
  created: string;
  modified: string;
}
