import { Injectable } from '@angular/core';
import { ApiService, PaginatedResponse } from '../../core/services/api.service';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';

export interface MaintenanceWindow {
  id: number;
  title: string;
  description: string;
  status: 'scheduled' | 'in_progress' | 'completed' | 'cancelled';
  starts_at: string;
  ends_at: string;
  monitor_ids: string | null;
  auto_suppress_alerts: boolean;
  notify_subscribers: boolean;
  is_recurring: boolean;
  recurrence_pattern: string | null;
  recurrence_end_date: string | null;
  created: string;
  modified: string;
}

@Injectable({ providedIn: 'root' })
export class MaintenanceService {
  constructor(private api: ApiService) {}

  getAll(params?: any): Observable<PaginatedResponse<MaintenanceWindow>> {
    return this.api.get<any>('/maintenance-windows', params).pipe(
      map(data => ({
        items: data.maintenance_windows || data.items || [],
        pagination: data.pagination || { page: 1, limit: 999, total: (data.maintenance_windows || data.items || []).length, pages: 1 },
      }))
    );
  }

  get(id: number): Observable<MaintenanceWindow> {
    return this.api.get<any>(`/maintenance-windows/${id}`).pipe(
      map(data => data.maintenance_window || data)
    );
  }

  create(data: any): Observable<MaintenanceWindow> {
    return this.api.post<MaintenanceWindow>('/maintenance-windows', data);
  }

  update(id: number, data: any): Observable<MaintenanceWindow> {
    return this.api.put<MaintenanceWindow>(`/maintenance-windows/${id}`, data);
  }

  delete(id: number): Observable<void> {
    return this.api.delete<void>(`/maintenance-windows/${id}`);
  }
}
