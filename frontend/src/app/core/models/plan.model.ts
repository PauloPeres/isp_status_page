export interface Plan {
  id: number;
  name: string;
  slug: string;
  price_monthly: number;
  price_yearly: number;
  max_monitors: number;
  max_users: number;
  max_status_pages: number;
  check_interval_min: number;
  features: string[];
  active: boolean;
  created: string;
  modified: string;
}
