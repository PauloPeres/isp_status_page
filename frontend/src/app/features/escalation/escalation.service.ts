import { Injectable } from '@angular/core';
import { ApiService, PaginatedResponse } from '../../core/services/api.service';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';

export interface EscalationStep {
  delay_minutes: number;
  channel: string;
  recipients: string[];
}

export interface EscalationPolicy {
  id: number;
  name: string;
  description: string;
  steps: EscalationStep[];
  monitor_ids: number[];
  monitor_count?: number;
  active: boolean;
  created_at: string;
  updated_at: string;
}

@Injectable({ providedIn: 'root' })
export class EscalationService {
  constructor(private api: ApiService) {}

  getAll(params?: any): Observable<PaginatedResponse<EscalationPolicy>> {
    return this.api.get<any>('/escalation-policies', params).pipe(
      map(data => ({
        items: data.escalation_policies || data.items || [],
        pagination: data.pagination || { page: 1, limit: 999, total: (data.escalation_policies || data.items || []).length, pages: 1 },
      }))
    );
  }

  get(id: number): Observable<EscalationPolicy> {
    return this.api.get<any>(`/escalation-policies/${id}`).pipe(
      map(data => data.escalation_policy || data)
    );
  }

  create(data: Partial<EscalationPolicy>): Observable<EscalationPolicy> {
    return this.api.post<EscalationPolicy>('/escalation-policies', data);
  }

  update(id: number, data: Partial<EscalationPolicy>): Observable<EscalationPolicy> {
    return this.api.put<EscalationPolicy>(`/escalation-policies/${id}`, data);
  }

  delete(id: number): Observable<void> {
    return this.api.delete<void>(`/escalation-policies/${id}`);
  }
}
