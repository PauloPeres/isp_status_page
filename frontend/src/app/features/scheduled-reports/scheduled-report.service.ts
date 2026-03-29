import { Injectable } from '@angular/core';
import { ApiService, PaginatedResponse } from '../../core/services/api.service';
import { Observable } from 'rxjs';

export interface ScheduledReport {
  id: number;
  name: string;
  report_type: string;
  frequency: 'daily' | 'weekly' | 'monthly';
  recipients: string[];
  next_send_at: string;
  last_sent_at: string | null;
  active: boolean;
  created_at: string;
  updated_at: string;
}

@Injectable({ providedIn: 'root' })
export class ScheduledReportService {
  constructor(private api: ApiService) {}

  getAll(params?: any): Observable<PaginatedResponse<ScheduledReport>> {
    return this.api.get<PaginatedResponse<ScheduledReport>>('/scheduled-reports', params);
  }

  get(id: number): Observable<ScheduledReport> {
    return this.api.get<ScheduledReport>(`/scheduled-reports/${id}`);
  }

  create(data: Partial<ScheduledReport>): Observable<ScheduledReport> {
    return this.api.post<ScheduledReport>('/scheduled-reports', data);
  }

  update(id: number, data: Partial<ScheduledReport>): Observable<ScheduledReport> {
    return this.api.put<ScheduledReport>(`/scheduled-reports/${id}`, data);
  }

  delete(id: number): Observable<void> {
    return this.api.delete<void>(`/scheduled-reports/${id}`);
  }

  sendNow(id: number): Observable<{ success: boolean; message: string }> {
    return this.api.post<{ success: boolean; message: string }>(`/scheduled-reports/${id}/send-now`);
  }

  preview(id: number): Observable<any> {
    return this.api.get(`/scheduled-reports/${id}/preview`);
  }
}
