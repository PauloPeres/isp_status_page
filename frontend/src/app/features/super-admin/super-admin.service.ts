import { Injectable } from '@angular/core';
import { ApiService, PaginatedResponse } from '../../core/services/api.service';
import { Observable } from 'rxjs';

export interface AdminDashboard {
  mrr: number;
  arr: number;
  total_organizations: number;
  total_users: number;
  total_monitors: number;
  active_incidents: number;
  plan_distribution: { plan: string; count: number }[];
}

export interface AdminOrg {
  id: number;
  name: string;
  slug: string;
  plan: string;
  user_count: number;
  monitor_count: number;
  owner_email: string;
  created_at: string;
}

export interface AdminUser {
  id: number;
  username: string;
  email: string;
  is_super_admin: boolean;
  organizations: { id: number; name: string; role: string }[];
  last_login_at: string | null;
  created_at: string;
}

export interface AdminRevenue {
  mrr: number;
  arr: number;
  plan_breakdown: { plan: string; count: number; revenue: number }[];
  monthly_trend: { month: string; mrr: number }[];
}

export interface AdminHealth {
  cpu_usage: number;
  memory_usage: number;
  disk_usage: number;
  db_connections: number;
  queue_size: number;
  uptime_seconds: number;
  redis_connected: boolean;
  last_check_at: string;
}

export interface AdminSecurityLog {
  id: number;
  event: string;
  user_email: string;
  ip_address: string;
  details: string;
  created_at: string;
}

@Injectable({ providedIn: 'root' })
export class SuperAdminService {
  constructor(private api: ApiService) {}

  getDashboard(): Observable<AdminDashboard> {
    return this.api.get<AdminDashboard>('/admin/dashboard');
  }

  getOrganizations(params?: any): Observable<PaginatedResponse<AdminOrg>> {
    return this.api.get<PaginatedResponse<AdminOrg>>('/admin/organizations', params);
  }

  impersonateOrg(orgId: number): Observable<{ token: string }> {
    return this.api.post<{ token: string }>(`/admin/organizations/${orgId}/impersonate`);
  }

  getUsers(params?: any): Observable<PaginatedResponse<AdminUser>> {
    return this.api.get<PaginatedResponse<AdminUser>>('/admin/users', params);
  }

  getRevenue(): Observable<AdminRevenue> {
    return this.api.get<AdminRevenue>('/admin/revenue');
  }

  getHealth(): Observable<AdminHealth> {
    return this.api.get<AdminHealth>('/admin/health');
  }

  getSettings(): Observable<Record<string, any>> {
    return this.api.get<Record<string, any>>('/admin/settings');
  }

  updateSettings(data: Record<string, any>): Observable<void> {
    return this.api.put<void>('/admin/settings', data);
  }

  getSecurityLogs(params?: any): Observable<PaginatedResponse<AdminSecurityLog>> {
    return this.api.get<PaginatedResponse<AdminSecurityLog>>('/admin/security-logs', params);
  }
}
