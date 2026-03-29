import { Injectable } from '@angular/core';
import { ApiService, PaginatedResponse } from '../../core/services/api.service';
import { Observable } from 'rxjs';

export interface ApiKey {
  id: number;
  name: string;
  prefix: string;
  permissions: string[];
  last_used_at: string | null;
  expires_at: string | null;
  created_at: string;
}

export interface ApiKeyCreateResponse {
  id: number;
  name: string;
  key: string;
  prefix: string;
  permissions: string[];
}

@Injectable({ providedIn: 'root' })
export class ApiKeyService {
  constructor(private api: ApiService) {}

  getAll(params?: any): Observable<PaginatedResponse<ApiKey>> {
    return this.api.get<PaginatedResponse<ApiKey>>('/api-keys', params);
  }

  create(data: { name: string; permissions: string[]; expires_at?: string }): Observable<ApiKeyCreateResponse> {
    return this.api.post<ApiKeyCreateResponse>('/api-keys', data);
  }

  delete(id: number): Observable<void> {
    return this.api.delete<void>(`/api-keys/${id}`);
  }
}
