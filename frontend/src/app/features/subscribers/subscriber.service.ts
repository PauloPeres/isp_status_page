import { Injectable } from '@angular/core';
import { ApiService, PaginatedResponse } from '../../core/services/api.service';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';

export interface Subscriber {
  id: number;
  email: string;
  verified: boolean;
  verified_at?: string;
  active: boolean;
  created: string;
}

@Injectable({ providedIn: 'root' })
export class SubscriberService {
  constructor(private api: ApiService) {}

  getAll(params?: any): Observable<PaginatedResponse<Subscriber>> {
    return this.api.get<any>('/subscribers', params).pipe(
      map(data => ({
        items: data.subscribers || data.items || [],
        pagination: data.pagination || { page: 1, limit: 999, total: (data.subscribers || data.items || []).length, pages: 1 },
      }))
    );
  }

  delete(id: number): Observable<void> {
    return this.api.delete<void>(`/subscribers/${id}`);
  }
}
