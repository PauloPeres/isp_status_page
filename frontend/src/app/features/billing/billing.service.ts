import { Injectable } from '@angular/core';
import { ApiService } from '../../core/services/api.service';
import { Observable } from 'rxjs';

export interface Plan {
  id: string;
  name: string;
  price: number;
  interval: 'monthly' | 'yearly';
  features: string[];
  monitor_limit: number;
  is_current: boolean;
}

export interface CreditBalance {
  credits: number;
  last_purchase_at: string | null;
}

@Injectable({ providedIn: 'root' })
export class BillingService {
  constructor(private api: ApiService) {}

  getPlans(): Observable<Plan[]> {
    return this.api.get<Plan[]>('/billing/plans');
  }

  getCurrentPlan(): Observable<Plan> {
    return this.api.get<Plan>('/billing/current-plan');
  }

  checkout(planId: string): Observable<{ checkout_url: string }> {
    return this.api.post<{ checkout_url: string }>('/billing/checkout', { plan_id: planId });
  }

  getCredits(): Observable<CreditBalance> {
    return this.api.get<CreditBalance>('/billing/credits');
  }

  buyCredits(amount: number): Observable<{ checkout_url: string }> {
    return this.api.post<{ checkout_url: string }>('/billing/credits/buy', { amount });
  }
}
