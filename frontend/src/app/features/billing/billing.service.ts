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
  formatted_price_yearly?: string;
  yearly_savings_percent?: number;
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

export interface Usage {
  monitors: number;
  team_members: number;
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
          formatted_price: this.formatPrice(plan),
          formatted_price_yearly: this.formatYearlyPrice(plan),
          yearly_savings_percent: this.calcYearlySavings(plan),
        }));
      })
    );
  }

  getCredits(): Observable<CreditBalance> {
    return this.api.get<{ credits: CreditBalance }>('/billing/credits').pipe(
      map(data => data.credits)
    );
  }

  getUsage(): Observable<Usage> {
    return this.api.get<{ usage: Usage }>('/billing/usage').pipe(
      map(data => data.usage)
    );
  }

  checkout(planSlug: string): Observable<any> {
    return this.api.post('/billing/checkout', { plan: planSlug });
  }

  openPortal(): Observable<any> {
    return this.api.post('/billing/portal', {});
  }

  buyCredits(amount: number): Observable<any> {
    return this.api.post('/billing/credits/buy', { amount });
  }

  private formatPrice(plan: Plan): string {
    if (plan.slug === 'enterprise') return 'Custom';
    if (plan.price_monthly === 0) return 'Free';
    return `$${(plan.price_monthly / 100).toFixed(0)}/mo`;
  }

  private formatYearlyPrice(plan: Plan): string {
    if (!plan.price_yearly || plan.price_yearly === 0) return '';
    return `$${(plan.price_yearly / 100 / 12).toFixed(0)}/mo billed yearly`;
  }

  private calcYearlySavings(plan: Plan): number {
    if (!plan.price_yearly || plan.price_monthly === 0) return 0;
    const monthlyTotal = plan.price_monthly * 12;
    if (plan.price_yearly >= monthlyTotal) return 0;
    return Math.round((1 - plan.price_yearly / monthlyTotal) * 100);
  }

  parseFeatures(featuresJson: string): string[] {
    try {
      const obj = typeof featuresJson === 'string' ? JSON.parse(featuresJson) : featuresJson;
      if (!obj || typeof obj !== 'object') return [];

      const featureLabels: Record<string, string> = {
        email_alerts: 'Email Alerts',
        slack_alerts: 'Slack Alerts',
        discord_alerts: 'Discord Alerts',
        telegram_alerts: 'Telegram Alerts',
        webhook_alerts: 'Webhook Alerts',
        sms_alerts: 'SMS Alerts',
        phone_alerts: 'Phone Call Alerts',
        ssl_monitoring: 'SSL Monitoring',
        api_access: 'API Access',
        custom_status_page: 'Custom Status Pages',
        custom_domain: 'Custom Domains',
        multi_region: 'Multi-Region Checks',
        priority_support: 'Priority Support',
        dedicated_support: 'Dedicated Support',
        sla_tracking: 'SLA Tracking',
        sso_saml: 'SSO / SAML',
      };

      return Object.entries(obj)
        .filter(([, v]) => v === true)
        .map(([k]) => featureLabels[k] || k.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()));
    } catch {
      return [];
    }
  }
}
