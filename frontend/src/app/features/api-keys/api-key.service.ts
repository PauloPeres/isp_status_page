import { Injectable } from '@angular/core';
import { ApiService, PaginatedResponse } from '../../core/services/api.service';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';

export interface ApiKey {
  id: number;
  public_id: string;
  name: string;
  prefix: string;
  permissions: string[];
  last_used_at: string | null;
  expires_at: string | null;
  created_at: string;
}

export interface ApiKeyCreateResponse {
  id: number;
  public_id: string;
  name: string;
  key: string;
  prefix: string;
  permissions: string[];
}

@Injectable({ providedIn: 'root' })
export class ApiKeyService {
  constructor(private api: ApiService) {}

  getAll(params?: any): Observable<PaginatedResponse<ApiKey>> {
    return this.api.get<any>('/api-keys', params).pipe(
      map(data => {
        const raw = data.api_keys || data.items || [];
        const items = raw.map((item: any) => ({
          ...item,
          permissions: this.parsePermissions(item.permissions),
        }));
        return {
          items,
          pagination: data.pagination || { page: 1, limit: 999, total: raw.length, pages: 1 },
        };
      })
    );
  }

  private parsePermissions(perms: any): string[] {
    if (Array.isArray(perms)) return perms;
    if (typeof perms === 'string') {
      try { const parsed = JSON.parse(perms); return Array.isArray(parsed) ? parsed : [perms]; }
      catch { return perms.split(',').map((s: string) => s.trim()).filter(Boolean); }
    }
    return [];
  }

  create(data: { name: string; permissions: string[]; expires_at?: string }): Observable<ApiKeyCreateResponse> {
    return this.api.post<ApiKeyCreateResponse>('/api-keys', data);
  }

  delete(id: string): Observable<void> {
    return this.api.delete<void>(`/api-keys/${id}`);
  }
}
