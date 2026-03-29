import { Injectable } from '@angular/core';
import {
  ApiService,
  PaginatedResponse,
} from '../../core/services/api.service';
import { Observable } from 'rxjs';
import { Monitor } from '../../core/models/monitor.model';

@Injectable({ providedIn: 'root' })
export class MonitorService {
  constructor(private api: ApiService) {}

  getMonitors(params?: {
    page?: number;
    limit?: number;
    search?: string;
    tag?: string;
    type?: string;
    status?: string;
    active?: boolean;
  }): Observable<PaginatedResponse<Monitor>> {
    return this.api.get<PaginatedResponse<Monitor>>('/monitors', params);
  }

  getMonitor(id: number): Observable<any> {
    return this.api.get(`/monitors/${id}`);
  }

  createMonitor(data: Partial<Monitor>): Observable<Monitor> {
    return this.api.post<Monitor>('/monitors', data);
  }

  updateMonitor(id: number, data: Partial<Monitor>): Observable<Monitor> {
    return this.api.put<Monitor>(`/monitors/${id}`, data);
  }

  deleteMonitor(id: number): Observable<void> {
    return this.api.delete<void>(`/monitors/${id}`);
  }

  pauseMonitor(id: number): Observable<void> {
    return this.api.post<void>(`/monitors/${id}/pause`);
  }

  resumeMonitor(id: number): Observable<void> {
    return this.api.post<void>(`/monitors/${id}/resume`);
  }

  getChecks(
    id: number,
    params?: { page?: number; limit?: number },
  ): Observable<PaginatedResponse<any>> {
    return this.api.get<PaginatedResponse<any>>(
      `/monitors/${id}/checks`,
      params,
    );
  }
}
