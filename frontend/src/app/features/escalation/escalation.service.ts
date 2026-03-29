import { Injectable } from '@angular/core';
import { ApiService, PaginatedResponse } from '../../core/services/api.service';
import { Observable } from 'rxjs';

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
    return this.api.get<PaginatedResponse<EscalationPolicy>>('/escalation-policies', params);
  }

  get(id: number): Observable<EscalationPolicy> {
    return this.api.get<EscalationPolicy>(`/escalation-policies/${id}`);
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
