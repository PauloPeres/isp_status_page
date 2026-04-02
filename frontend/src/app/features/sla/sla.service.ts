import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { ApiService, PaginatedResponse } from '../../core/services/api.service';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';
import { environment } from '../../../environments/environment';

export interface Sla {
  id: number;
  name: string;
  monitor_id: number;
  monitor_name?: string;
  target_uptime: number;
  actual_uptime?: number;
  measurement_period: string;
  period?: string; // alias
  breach_notification?: boolean;
  warning_threshold?: number;
  active?: boolean;
  status?: 'compliant' | 'at_risk' | 'breached';
  created: string;
  modified: string;
}

@Injectable({ providedIn: 'root' })
export class SlaService {
  constructor(private api: ApiService, private http: HttpClient) {}

  getAll(params?: any): Observable<PaginatedResponse<Sla>> {
    return this.api.get<any>('/sla', params).pipe(
      map(data => ({
        items: data.slas || data.items || [],
        pagination: data.pagination || { page: 1, limit: 999, total: (data.slas || data.items || []).length, pages: 1 },
      }))
    );
  }

  get(id: number): Observable<Sla> {
    return this.api.get<any>(`/sla/${id}`).pipe(
      map(data => data.sla || data)
    );
  }

  create(data: Partial<Sla>): Observable<Sla> {
    return this.api.post<Sla>('/sla', data);
  }

  update(id: number, data: Partial<Sla>): Observable<Sla> {
    return this.api.put<Sla>(`/sla/${id}`, data);
  }

  delete(id: number): Observable<void> {
    return this.api.delete<void>(`/sla/${id}`);
  }

  getReport(id: number, params?: any): Observable<any> {
    return this.api.get(`/sla/${id}/report`, params);
  }

  /**
   * Download SLA report as PDF or CSV blob (bypasses JSON ApiService).
   */
  exportReport(id: number, format: 'pdf' | 'csv' = 'pdf'): Observable<Blob> {
    const params = new HttpParams().set('format', format);
    return this.http.get(`${environment.apiUrl}/sla/${id}/export`, {
      params,
      responseType: 'blob',
    });
  }
}
