import { Injectable } from '@angular/core';
import { ApiService } from '../../core/services/api.service';
import { Observable } from 'rxjs';

export interface ReportResult {
  data: any[];
  summary: Record<string, any>;
}

@Injectable({ providedIn: 'root' })
export class ReportService {
  constructor(private api: ApiService) {}

  getUptime(params: { start: string; end: string }): Observable<ReportResult> {
    return this.api.get<ReportResult>('/reports/uptime', params);
  }

  getIncidents(params: { start: string; end: string }): Observable<ReportResult> {
    return this.api.get<ReportResult>('/reports/incidents', params);
  }

  getResponseTimes(params: { start: string; end: string }): Observable<ReportResult> {
    return this.api.get<ReportResult>('/reports/response-times', params);
  }

  downloadUptime(params: { start: string; end: string }): Observable<Blob> {
    return this.api.get<Blob>('/reports/uptime/csv', params);
  }

  downloadIncidents(params: { start: string; end: string }): Observable<Blob> {
    return this.api.get<Blob>('/reports/incidents/csv', params);
  }

  downloadResponseTimes(params: { start: string; end: string }): Observable<Blob> {
    return this.api.get<Blob>('/reports/response-times/csv', params);
  }
}
