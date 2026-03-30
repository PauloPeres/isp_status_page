import { Injectable } from '@angular/core';
import { ApiService, PaginatedResponse } from './api.service';
import { Observable } from 'rxjs';

export interface CheckRegion {
  id: number;
  name: string;
  code: string;
  endpoint_url: string | null;
  active: boolean;
  created: string;
}

export interface RegionBreakdown {
  region_id: number;
  region_name: string;
  region_code: string;
  uptime: number;
  avg_response_time: number | null;
  total_checks: number;
}

@Injectable({ providedIn: 'root' })
export class CheckRegionService {
  constructor(private api: ApiService) {}

  getAll(params?: any): Observable<PaginatedResponse<CheckRegion>> {
    return this.api.get<PaginatedResponse<CheckRegion>>('/check-regions', params);
  }

  get(id: number): Observable<CheckRegion> {
    return this.api.get<CheckRegion>(`/check-regions/${id}`);
  }
}
