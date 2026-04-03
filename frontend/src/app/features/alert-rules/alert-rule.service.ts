import { Injectable } from '@angular/core';
import { ApiService, PaginatedResponse } from '../../core/services/api.service';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';

export interface AlertRule {
  id: number;
  public_id: string;
  name: string;
  monitor_id: number;
  monitor_name?: string;
  trigger_type: string;
  channel: string;
  recipients: string[];
  cooldown_minutes: number;
  active: boolean;
  created_at: string;
  updated_at: string;
}

@Injectable({ providedIn: 'root' })
export class AlertRuleService {
  constructor(private api: ApiService) {}

  getAll(params?: any): Observable<PaginatedResponse<AlertRule>> {
    return this.api.get<any>('/alert-rules', params).pipe(
      map(data => ({
        items: data.alert_rules || data.items || [],
        pagination: data.pagination || { page: 1, limit: 999, total: (data.alert_rules || data.items || []).length, pages: 1 },
      }))
    );
  }

  get(id: string): Observable<AlertRule> {
    return this.api.get<any>(`/alert-rules/${id}`).pipe(
      map(data => data.alert_rule || data)
    );
  }

  create(data: Partial<AlertRule>): Observable<AlertRule> {
    return this.api.post<AlertRule>('/alert-rules', data);
  }

  update(id: string, data: Partial<AlertRule>): Observable<AlertRule> {
    return this.api.put<AlertRule>(`/alert-rules/${id}`, data);
  }

  delete(id: string): Observable<void> {
    return this.api.delete<void>(`/alert-rules/${id}`);
  }
}
