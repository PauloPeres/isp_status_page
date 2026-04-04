import { Injectable } from '@angular/core';
import { ApiService, PaginatedResponse } from '../../core/services/api.service';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';
import { NotificationChannel } from '../channels/channel.service';

export interface NotificationPolicyStep {
  id?: number;
  step_order: number;
  delay_minutes: number;
  notification_channel_id: number;
  notify_on_resolve: boolean;
  notification_channel?: NotificationChannel;
}

export interface NotificationPolicy {
  id: number;
  public_id: string;
  name: string;
  description: string;
  trigger_type: string;
  repeat_interval_minutes: number;
  active: boolean;
  step_count?: number;
  monitor_count?: number;
  steps?: NotificationPolicyStep[];
  notification_policy_steps?: NotificationPolicyStep[];
  created_at?: string;
  updated_at?: string;
}

@Injectable({ providedIn: 'root' })
export class NotificationPolicyService {
  constructor(private api: ApiService) {}

  getAll(params?: any): Observable<PaginatedResponse<NotificationPolicy>> {
    return this.api.get<any>('/notification-policies', params).pipe(
      map(data => ({
        items: data.notification_policies || data.items || [],
        pagination: data.pagination || { page: 1, limit: 999, total: (data.notification_policies || data.items || []).length, pages: 1 },
      }))
    );
  }

  get(id: string): Observable<NotificationPolicy> {
    return this.api.get<any>(`/notification-policies/${id}`).pipe(
      map(data => data.notification_policy || data)
    );
  }

  create(data: Partial<NotificationPolicy>): Observable<NotificationPolicy> {
    return this.api.post<NotificationPolicy>('/notification-policies', data);
  }

  update(id: string, data: Partial<NotificationPolicy>): Observable<NotificationPolicy> {
    return this.api.put<NotificationPolicy>(`/notification-policies/${id}`, data);
  }

  delete(id: string): Observable<void> {
    return this.api.delete<void>(`/notification-policies/${id}`);
  }
}
