import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

@Injectable({ providedIn: 'root' })
export class ReportService {
  constructor(private http: HttpClient) {}

  downloadUptime(params: { start: string; end: string }): Observable<Blob> {
    return this.downloadCsv('/reports/uptime', params);
  }

  downloadIncidents(params: { start: string; end: string }): Observable<Blob> {
    return this.downloadCsv('/reports/incidents', params);
  }

  downloadResponseTimes(params: { start: string; end: string }): Observable<Blob> {
    return this.downloadCsv('/reports/response-times', params);
  }

  private downloadCsv(
    path: string,
    params: { start: string; end: string },
  ): Observable<Blob> {
    let httpParams = new HttpParams();
    if (params.start) httpParams = httpParams.set('start', params.start);
    if (params.end) httpParams = httpParams.set('end', params.end);

    return this.http.get(`${environment.apiUrl}${path}`, {
      params: httpParams,
      responseType: 'blob',
    });
  }
}
