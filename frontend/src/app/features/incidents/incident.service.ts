import { Injectable } from '@angular/core';
import {
  ApiService,
  PaginatedResponse,
} from '../../core/services/api.service';
import { Observable } from 'rxjs';
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
    return this.api.get<Incident>(`/incidents/${id}`);
  }

  createIncident(data: Partial<Incident>): Observable<Incident> {
    return this.api.post<Incident>('/incidents', data);
  }

  updateIncident(id: number, data: Partial<Incident>): Observable<Incident> {
    return this.api.put<Incident>(`/incidents/${id}`, data);
  }

  acknowledgeIncident(id: number): Observable<void> {
    return this.api.post<void>(`/incidents/${id}/acknowledge`);
  }

  addUpdate(
    id: number,
    data: { status: string; message: string; is_public?: boolean },
  ): Observable<IncidentTimelineEntry> {
    return this.api.post<IncidentTimelineEntry>(
      `/incidents/${id}/updates`,
      data,
    );
  }
}
