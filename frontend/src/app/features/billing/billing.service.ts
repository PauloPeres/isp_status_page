import { Injectable } from '@angular/core';
import { ApiService } from '../../core/services/api.service';
import { Observable, map } from 'rxjs';

export interface Plan {
  id: number;
  name: string;
  slug: string;
  price_monthly: number; // cents
  price_yearly: number; // cents
  monitor_limit: number;
  check_interval_min: number;
  team_member_limit: number;
  status_page_limit: number;
  api_rate_limit: number;
  data_retention_days: number;
  features: string; // JSON string
  display_order: number;
  active: boolean;
  is_free: boolean;
  // Computed
  is_current?: boolean;
  parsed_features?: string[];
  formatted_price?: string;
}

export interface CreditBalance {
  id: number;
  organization_id: number;
  balance: number;
  monthly_grant: number;
  auto_recharge: boolean;
  auto_recharge_threshold: number;
  auto_recharge_amount: number;
  last_grant_at: string | null;
}

export interface BillingPlansResponse {
  plans: Plan[];
  current_plan: string;
}

@Injectable({ providedIn: 'root' })
export class BillingService {
  constructor(private api: ApiService) {}

  getPlans(): Observable<Plan[]> {
    return this.api.get<BillingPlansResponse>('/billing/plans').pipe(
      map(data => {
        const currentSlug = data.current_plan || 'free';
        return (data.plans || []).map(plan => ({
          ...plan,
          is_current: plan.slug === currentSlug,
          parsed_features: this.parseFeatures(plan.features),
          formatted_price: plan.price_monthly === 0 ? 'Free' : `$${(plan.price_monthly / 100).toFixed(0)}/mo`,
        }));
      })
    );
  }

  getCredits(): Observable<CreditBalance> {
    return this.api.get<{ credits: CreditBalance }>('/billing/credits').pipe(
      map(data => data.credits)
    );
  }

  checkout(planSlug: string): Observable<any> {
    return this.api.post('/billing/checkout', { plan: planSlug });
  }

  buyCredits(amount: number): Observable<any> {
    return this.api.post('/billing/credits/buy', { amount });
  }

  private parseFeatures(featuresJson: string): string[] {
    try {
      const obj = typeof featuresJson === 'string' ? JSON.parse(featuresJson) : featuresJson;
      return Object.entries(obj)
        .filter(([, v]) => v === true)
        .map(([k]) => k.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()));
    } catch {
      return [];
    }
  }
}
