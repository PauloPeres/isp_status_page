import { Injectable } from '@angular/core';
import {
  ApiService,
  PaginatedResponse,
} from '../../core/services/api.service';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';
import { Check } from '../../core/models/check.model';

export interface CheckWithMonitor extends Check {
  monitor?: { id: number; name: string };
}

@Injectable({ providedIn: 'root' })
export class CheckService {
  constructor(private api: ApiService) {}

  getChecks(params?: {
    page?: number;
    limit?: number;
    monitor_id?: number;
    status?: string;
    from?: string;
    to?: string;
  }): Observable<PaginatedResponse<CheckWithMonitor>> {
    return this.api.get<any>('/checks', params).pipe(
      map(data => ({
        items: data.checks || data.items || [],
        pagination: data.pagination || { page: 1, limit: 50, total: (data.checks || data.items || []).length, pages: 1 },
      }))
    );
  }
}
