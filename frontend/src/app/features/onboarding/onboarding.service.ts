import { Injectable, signal } from '@angular/core';
import { ApiService } from '../../core/services/api.service';
import { forkJoin, of, catchError } from 'rxjs';
import { map } from 'rxjs/operators';

export interface OnboardingStep {
  id: string;
  title: string;
  description: string;
  icon: string;
  completed: boolean;
  route: string;
}

export interface OnboardingProgress {
  steps: OnboardingStep[];
  completedCount: number;
  totalCount: number;
  allDone: boolean;
}

@Injectable({ providedIn: 'root' })
export class OnboardingService {
  private readonly STORAGE_KEY = 'onboarding_dismissed';

  progress = signal<OnboardingProgress | null>(null);
  loading = signal(false);

  constructor(private api: ApiService) {}

  isDismissed(): boolean {
    return localStorage.getItem(this.STORAGE_KEY) === 'true';
  }

  dismiss(): void {
    localStorage.setItem(this.STORAGE_KEY, 'true');
  }

  reset(): void {
    localStorage.removeItem(this.STORAGE_KEY);
  }

  shouldShow(): boolean {
    if (this.isDismissed()) return false;
    const p = this.progress();
    return p !== null && !p.allDone;
  }

  loadProgress(): void {
    this.loading.set(true);

    forkJoin({
      summary: this.api
        .get<any>('/dashboard/summary')
        .pipe(catchError(() => of(null))),
      alertRules: this.api
        .get<any>('/alert-rules', { limit: 1 })
        .pipe(catchError(() => of(null))),
      users: this.api
        .get<any>('/users', { limit: 1 })
        .pipe(catchError(() => of(null))),
      statusPages: this.api
        .get<any>('/status-pages', { limit: 1 })
        .pipe(catchError(() => of(null))),
    }).subscribe({
      next: (data) => {
        const monitorCount = data.summary?.monitors?.total ?? 0;
        const alertRuleCount = data.alertRules?.pagination?.total ?? data.alertRules?.items?.length ?? 0;
        const userCount = data.users?.pagination?.total ?? data.users?.items?.length ?? 0;
        const statusPageCount = data.statusPages?.pagination?.total ?? data.statusPages?.items?.length ?? 0;

        const steps: OnboardingStep[] = [
          {
            id: 'monitor',
            title: 'Create Your First Monitor',
            description: 'Add a website, API, or server to start monitoring uptime and performance.',
            icon: 'pulse-outline',
            completed: monitorCount > 0,
            route: '/monitors/new',
          },
          {
            id: 'alert',
            title: 'Set Up Alerts',
            description: 'Get notified by email, Slack, Discord, or Telegram when something goes down.',
            icon: 'notifications-outline',
            completed: alertRuleCount > 0,
            route: '/alert-rules/new',
          },
          {
            id: 'status-page',
            title: 'Create a Status Page',
            description: 'Share a public status page with your users so they can check service health.',
            icon: 'globe-outline',
            completed: statusPageCount > 0,
            route: '/status-pages/new',
          },
          {
            id: 'team',
            title: 'Invite Your Team',
            description: 'Add team members to collaborate on monitoring and incident management.',
            icon: 'people-outline',
            completed: userCount > 1,
            route: '/invitations',
          },
        ];

        const completedCount = steps.filter((s) => s.completed).length;
        this.progress.set({
          steps,
          completedCount,
          totalCount: steps.length,
          allDone: completedCount === steps.length,
        });
        this.loading.set(false);
      },
      error: () => {
        this.loading.set(false);
      },
    });
  }
}
