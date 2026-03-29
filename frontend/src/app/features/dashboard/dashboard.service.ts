import { Injectable } from '@angular/core';
import { ApiService } from '../../core/services/api.service';
import { Observable } from 'rxjs';
import { map } from 'rxjs/operators';

export interface DashboardSummary {
  monitors: {
    total: number;
    up: number;
    down: number;
    degraded: number;
    unknown: number;
  };
  active_incidents: {
    total: number;
    by_severity: {
      critical: number;
      major: number;
      minor: number;
      maintenance: number;
    };
  };
  sla: {
    compliant?: number;
    at_risk?: number;
    breached?: number;
  };
}

export interface UptimeData {
  id: number;
  name: string;
  uptime: number;
}

export interface ResponseTimeData {
  id: number;
  name: string;
  avg_response_time: number;
}

export interface RecentCheck {
  id: number;
  monitor: { name: string };
  status: string;
  response_time: number;
  checked_at: string;
}

export interface RecentAlert {
  id: number;
  alert_rule: { name: string };
  monitor: { name: string };
  channel: string;
  status: string;
  created: string;
}

@Injectable({ providedIn: 'root' })
export class DashboardService {
  constructor(private api: ApiService) {}

  getSummary(): Observable<DashboardSummary> {
    return this.api.get<DashboardSummary>('/dashboard/summary');
  }

  getUptime(days = 1): Observable<UptimeData[]> {
    return this.api
      .get<{ items: UptimeData[] }>('/dashboard/uptime', { days })
      .pipe(map((res) => res.items));
  }

  getResponseTimes(days = 1): Observable<ResponseTimeData[]> {
    return this.api
      .get<{ items: ResponseTimeData[] }>('/dashboard/response-times', { days })
      .pipe(map((res) => res.items));
  }

  getRecentChecks(): Observable<RecentCheck[]> {
    return this.api
      .get<{ items: RecentCheck[] }>('/dashboard/recent-checks')
      .pipe(map((res) => res.items));
  }

  getRecentAlerts(): Observable<RecentAlert[]> {
    return this.api
      .get<{ items: RecentAlert[] }>('/dashboard/recent-alerts')
      .pipe(map((res) => res.items));
  }
}
