import { Injectable } from '@angular/core';
import {
  ApiService,
  PaginatedResponse,
} from '../../core/services/api.service';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';
import {
  Incident,
  IncidentTimelineEntry,
} from '../../core/models/incident.model';

@Injectable({ providedIn: 'root' })
export class IncidentService {
  constructor(private api: ApiService) {}

  getIncidents(params?: {
    page?: number;
    limit?: number;
    search?: string;
    status?: string;
    severity?: string;
    monitor_id?: number;
  }): Observable<PaginatedResponse<Incident>> {
    return this.api.get<PaginatedResponse<Incident>>('/incidents', params);
  }

  getIncident(id: number): Observable<Incident> {
    return this.api.get<any>(`/incidents/${id}`).pipe(
      map(data => ({ ...(data.incident || data), timeline: data.updates || data.timeline || (data.incident || data).timeline || [] })),
    );
  }

  createIncident(data: Partial<Incident>): Observable<Incident> {
    return this.api.post<any>('/incidents', data).pipe(
      map(data => data.incident || data),
    );
  }

  updateIncident(id: number, data: Partial<Incident>): Observable<Incident> {
    return this.api.put<Incident>(`/incidents/${id}`, data);
  }

  acknowledgeIncident(id: number): Observable<Incident> {
    return this.api.post<any>(`/incidents/${id}/acknowledge`).pipe(
      map(data => data.incident || data),
    );
  }

  addUpdate(
    id: number,
    data: { status: string; message: string; is_public?: boolean },
  ): Observable<IncidentTimelineEntry> {
    return this.api.post<any>(
      `/incidents/${id}/updates`,
      data,
    ).pipe(
      map(data => data.update || data),
    );
  }
}
