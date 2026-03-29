import { Injectable } from '@angular/core';
import {
  ApiService,
  PaginatedResponse,
} from '../../core/services/api.service';
import { Observable } from 'rxjs';
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
  }): Observable<PaginatedResponse<CheckWithMonitor>> {
    return this.api.get<PaginatedResponse<CheckWithMonitor>>(
      '/checks',
      params,
    );
  }
}
