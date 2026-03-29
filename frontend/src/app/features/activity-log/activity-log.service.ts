import { Injectable } from '@angular/core';
import { ApiService, PaginatedResponse } from '../../core/services/api.service';
import { Observable } from 'rxjs';

export interface ActivityLogEntry {
  id: number;
  event_type: string;
  description: string;
  user_id: number | null;
  user_name: string | null;
  ip_address: string;
  metadata: Record<string, any>;
  created_at: string;
}

@Injectable({ providedIn: 'root' })
export class ActivityLogService {
  constructor(private api: ApiService) {}

  getAll(params?: { event_type?: string; page?: number; limit?: number }): Observable<PaginatedResponse<ActivityLogEntry>> {
    return this.api.get<PaginatedResponse<ActivityLogEntry>>('/activity-log', params);
  }
}
