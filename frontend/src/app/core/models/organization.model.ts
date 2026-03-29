export interface Organization {
  id: number;
  name: string;
  slug: string;
  plan_id?: number;
  custom_domain?: string;
  logo_url?: string;
  active: boolean;
  settings?: Record<string, any>;
  created: string;
  modified: string;
}

export interface OrganizationUser {
  id: number;
  organization_id: number;
  user_id: number;
  role: 'owner' | 'admin' | 'member' | 'viewer';
  created: string;
  modified: string;
}
