import { Injectable } from '@angular/core';
import { ApiService, PaginatedResponse } from '../../core/services/api.service';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';

export interface EmailLog {
  id: number;
  to_email: string;
  subject: string;
  status: string;
  error_message?: string;
  created: string;
}

@Injectable({ providedIn: 'root' })
export class EmailLogService {
  constructor(private api: ApiService) {}

  getAll(params?: any): Observable<PaginatedResponse<EmailLog>> {
    return this.api.get<any>('/email-logs', params).pipe(
      map(data => ({
        items: data.email_logs || data.items || [],
        pagination: data.pagination || { page: 1, limit: 999, total: (data.email_logs || data.items || []).length, pages: 1 },
      }))
    );
  }
}
