import { Injectable } from '@angular/core';
import { ApiService, PaginatedResponse } from '../../core/services/api.service';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';

export interface StatusPage {
  id: number;
  public_id: string;
  name: string;
  slug: string;
  custom_domain: string | null;
  active: boolean;
  monitors: any;
  created: string;
  modified: string;
}

@Injectable({ providedIn: 'root' })
export class StatusPageService {
  constructor(private api: ApiService) {}

  getAll(params?: any): Observable<PaginatedResponse<StatusPage>> {
    return this.api.get<any>('/status-pages', params).pipe(
      map(data => ({
        items: data.status_pages || data.items || [],
        pagination: data.pagination || { page: 1, limit: 999, total: (data.status_pages || data.items || []).length, pages: 1 },
      }))
    );
  }

  get(id: string): Observable<StatusPage> {
    return this.api.get<any>(`/status-pages/${id}`).pipe(
      map(data => data.status_page || data)
    );
  }

  create(data: Partial<StatusPage>): Observable<StatusPage> {
    return this.api.post<StatusPage>('/status-pages', data);
  }

  update(id: string, data: Partial<StatusPage>): Observable<StatusPage> {
    return this.api.put<StatusPage>(`/status-pages/${id}`, data);
  }

  delete(id: string): Observable<void> {
    return this.api.delete<void>(`/status-pages/${id}`);
  }
}
