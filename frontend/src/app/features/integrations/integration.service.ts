import { Injectable } from '@angular/core';
import { ApiService, PaginatedResponse } from '../../core/services/api.service';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';

export interface Integration {
  id: number;
  public_id: string;
  name: string;
  type: string;
  configuration: any;
  active: boolean;
  last_test_at: string | null;
  last_test_status: string | null;
  created_at: string;
  updated_at: string;
}

@Injectable({ providedIn: 'root' })
export class IntegrationService {
  constructor(private api: ApiService) {}

  getAll(params?: any): Observable<PaginatedResponse<Integration>> {
    return this.api.get<any>('/integrations', params).pipe(
      map(data => ({
        items: data.integrations || data.items || [],
        pagination: data.pagination || { page: 1, limit: 999, total: (data.integrations || data.items || []).length, pages: 1 },
      }))
    );
  }

  get(id: string): Observable<Integration> {
    return this.api.get<any>(`/integrations/${id}`).pipe(
      map(data => data.integration || data)
    );
  }

  create(data: Partial<Integration>): Observable<Integration> {
    return this.api.post<Integration>('/integrations', data);
  }

  update(id: string, data: Partial<Integration>): Observable<Integration> {
    return this.api.put<Integration>(`/integrations/${id}`, data);
  }

  delete(id: string): Observable<void> {
    return this.api.delete<void>(`/integrations/${id}`);
  }

  testConnection(id: string): Observable<{ success: boolean; message: string }> {
    return this.api.post<{ success: boolean; message: string }>(`/integrations/${id}/test`);
  }
}
