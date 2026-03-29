import { Injectable } from '@angular/core';
import { ApiService, PaginatedResponse } from '../../core/services/api.service';
import { Observable } from 'rxjs';

export interface MaintenanceWindow {
  id: number;
  title: string;
  description: string;
  status: 'scheduled' | 'in_progress' | 'completed' | 'cancelled';
  start_at: string;
  end_at: string;
  monitors: { id: number; name: string }[];
  created_by: string;
  created_at: string;
  updated_at: string;
}

@Injectable({ providedIn: 'root' })
export class MaintenanceService {
  constructor(private api: ApiService) {}

  getAll(params?: any): Observable<PaginatedResponse<MaintenanceWindow>> {
    return this.api.get<PaginatedResponse<MaintenanceWindow>>('/maintenance', params);
  }

  get(id: number): Observable<MaintenanceWindow> {
    return this.api.get<MaintenanceWindow>(`/maintenance/${id}`);
  }

  create(data: Partial<MaintenanceWindow>): Observable<MaintenanceWindow> {
    return this.api.post<MaintenanceWindow>('/maintenance', data);
  }

  update(id: number, data: Partial<MaintenanceWindow>): Observable<MaintenanceWindow> {
    return this.api.put<MaintenanceWindow>(`/maintenance/${id}`, data);
  }

  delete(id: number): Observable<void> {
    return this.api.delete<void>(`/maintenance/${id}`);
  }
}
