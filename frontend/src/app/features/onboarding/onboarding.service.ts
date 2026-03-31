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
      channels: this.api
        .get<any>('/notification-channels', { limit: 1 })
        .pipe(catchError(() => of(null))),
      policies: this.api
        .get<any>('/notification-policies', { limit: 1 })
        .pipe(catchError(() => of(null))),
      users: this.api
        .get<any>('/users', { limit: 1 })
        .pipe(catchError(() => of(null))),
      statusPages: this.api
        .get<any>('/status-pages', { limit: 1 })
        .pipe(catchError(() => of(null))),
      invitations: this.api
        .get<any>('/invitations', { limit: 1 })
        .pipe(catchError(() => of(null))),
    }).subscribe({
      next: (data) => {
        const monitorCount = data.summary?.monitors?.total ?? 0;
        const channelCount = data.channels?.pagination?.total ?? data.channels?.notification_channels?.length ?? data.channels?.items?.length ?? 0;
        const policyCount = data.policies?.pagination?.total ?? data.policies?.notification_policies?.length ?? data.policies?.items?.length ?? 0;
        const userCount = data.users?.pagination?.total ?? data.users?.users?.length ?? data.users?.items?.length ?? 0;
        const statusPageCount = data.statusPages?.pagination?.total ?? data.statusPages?.status_pages?.length ?? data.statusPages?.items?.length ?? 0;

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
            id: 'channel',
            title: 'Set Up a Notification Channel',
            description: 'Configure how you want to be notified — email, Slack, Telegram, SMS, or others.',
            icon: 'megaphone-outline',
            completed: channelCount > 0,
            route: '/channels/new',
          },
          {
            id: 'policy',
            title: 'Create a Notification Policy',
            description: 'Define your alert chain — who gets notified, when, and how urgently.',
            icon: 'notifications-outline',
            completed: policyCount > 0,
            route: '/notifications/new',
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
            completed: userCount > 1 || (data.invitations?.invitations?.length ?? data.invitations?.items?.length ?? 0) > 0,
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
