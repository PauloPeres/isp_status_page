import { Injectable } from '@angular/core';
import { ApiService, PaginatedResponse } from '../../core/services/api.service';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';

export interface NotificationChannel {
  id: number;
  name: string;
  type: string;
  configuration: any;
  active: boolean;
  created: string;
}

@Injectable({ providedIn: 'root' })
export class ChannelService {
  constructor(private api: ApiService) {}

  getAll(params?: any): Observable<PaginatedResponse<NotificationChannel>> {
    return this.api.get<any>('/notification-channels', params).pipe(
      map(data => ({
        items: data.notification_channels || data.items || [],
        pagination: data.pagination || { page: 1, limit: 999, total: (data.notification_channels || data.items || []).length, pages: 1 },
      }))
    );
  }

  get(id: number): Observable<NotificationChannel> {
    return this.api.get<any>(`/notification-channels/${id}`).pipe(
      map(data => data.notification_channel || data)
    );
  }

  create(data: Partial<NotificationChannel>): Observable<NotificationChannel> {
    return this.api.post<NotificationChannel>('/notification-channels', data);
  }

  update(id: number, data: Partial<NotificationChannel>): Observable<NotificationChannel> {
    return this.api.put<NotificationChannel>(`/notification-channels/${id}`, data);
  }

  delete(id: number): Observable<void> {
    return this.api.delete<void>(`/notification-channels/${id}`);
  }

  test(id: number): Observable<{ success: boolean; message: string }> {
    return this.api.post<{ success: boolean; message: string }>(`/notification-channels/${id}/test`);
  }
}
