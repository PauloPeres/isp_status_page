import { Injectable } from '@angular/core';
import { ApiService, PaginatedResponse } from '../../core/services/api.service';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';

export interface User {
  id: number;
  name: string;
  email: string;
  role: 'owner' | 'admin' | 'member' | 'viewer';
  avatar_url: string | null;
  last_login_at: string | null;
  created_at: string;
}

export interface Invitation {
  id: number;
  email: string;
  role: string;
  status: 'pending' | 'accepted' | 'expired';
  invited_by: string;
  created_at: string;
  expires_at: string;
}

@Injectable({ providedIn: 'root' })
export class UserService {
  constructor(private api: ApiService) {}

  getAll(params?: any): Observable<PaginatedResponse<User>> {
    return this.api.get<any>('/users', params).pipe(
      map(data => ({
        items: data.users || data.items || [],
        pagination: data.pagination || { page: 1, limit: 999, total: (data.users || data.items || []).length, pages: 1 },
      }))
    );
  }

  get(id: number): Observable<User> {
    return this.api.get<User>(`/users/${id}`);
  }

  updateRole(id: number, role: string): Observable<User> {
    return this.api.put<User>(`/users/${id}/role`, { role });
  }

  remove(id: number): Observable<void> {
    return this.api.delete<void>(`/users/${id}`);
  }

  getInvitations(params?: any): Observable<PaginatedResponse<Invitation>> {
    return this.api.get<any>('/invitations', params).pipe(
      map(data => ({
        items: data.invitations || data.items || [],
        pagination: data.pagination || { page: 1, limit: 999, total: (data.invitations || data.items || []).length, pages: 1 },
      }))
    );
  }

  sendInvitation(data: { email: string; role: string }): Observable<Invitation> {
    return this.api.post<any>('/invitations', data).pipe(
      map(d => d.invitation || d)
    );
  }

  revokeInvitation(id: number): Observable<void> {
    return this.api.delete<void>(`/invitations/${id}`);
  }

  resendInvitation(id: number): Observable<void> {
    return this.api.post<void>(`/invitations/${id}/resend`);
  }
}
