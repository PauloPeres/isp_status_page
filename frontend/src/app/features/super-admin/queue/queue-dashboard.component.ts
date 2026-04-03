import { Component, OnInit, OnDestroy, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons,
  IonCard, IonCardHeader, IonCardTitle, IonCardContent,
  IonGrid, IonRow, IonCol, IonBadge, IonIcon, IonButton, IonLabel,
  IonSkeletonText, IonList, IonItem, IonNote, IonChip,
  IonRefresher, IonRefresherContent,
  ToastController, AlertController,
} from '@ionic/angular/standalone';
import { ApiService } from '../../../core/services/api.service';
import { addIcons } from 'ionicons';
import {
  layersOutline, timerOutline, alertCircleOutline, refreshOutline,
  trashOutline, reloadOutline, checkmarkCircleOutline, closeCircleOutline,
  serverOutline, pulseOutline,
} from 'ionicons/icons';

addIcons({
  'layers-outline': layersOutline,
  'timer-outline': timerOutline,
  'alert-circle-outline': alertCircleOutline,
  'refresh-outline': refreshOutline,
  'trash-outline': trashOutline,
  'reload-outline': reloadOutline,
  'checkmark-circle-outline': checkmarkCircleOutline,
  'close-circle-outline': closeCircleOutline,
  'server-outline': serverOutline,
  'pulse-outline': pulseOutline,
});

interface QueueInfo {
  depth: number;
  name: string;
}

interface FailedJobSummary {
  id: number;
  class: string;
  queue: string;
  exception: string | null;
  created: string | null;
}

interface QueueDashboardData {
  queues: { [key: string]: QueueInfo };
  scheduler: { last_run: string | null; status: string };
  workers: { active: number; last_heartbeat: string | null };
  failed_jobs: { total: number; recent: FailedJobSummary[] };
  stats: { jobs_processed_24h: number; jobs_failed_24h: number };
}

interface FailedJobDetail {
  id: number;
  class: string;
  method: string;
  queue: string;
  config: string;
  exception: string | null;
  created: string | null;
}

interface FailedJobsResponse {
  failed_jobs: FailedJobDetail[];
  pagination: { page: number; limit: number; total: number; pages: number };
}

@Component({
  selector: 'app-queue-dashboard',
  standalone: true,
  imports: [
    CommonModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons,
    IonCard, IonCardHeader, IonCardTitle, IonCardContent,
    IonGrid, IonRow, IonCol, IonBadge, IonIcon, IonButton, IonLabel,
    IonSkeletonText, IonList, IonItem, IonNote, IonChip,
    IonRefresher, IonRefresherContent,
  ],
  template: `
    <ion-header>
      <ion-toolbar color="dark">
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>Queue Dashboard</ion-title>
        <ion-buttons slot="end">
          <ion-button (click)="load()" title="Refresh">
            <ion-icon name="refresh-outline" slot="icon-only"></ion-icon>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <ion-refresher slot="fixed" (ionRefresh)="onRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

      @if (loading()) {
        <ion-grid>
          <ion-row>
            @for (i of [1,2,3,4]; track i) {
              <ion-col size="6" sizeMd="3">
                <ion-card><ion-card-content style="text-align: center; padding: 20px">
                  <ion-skeleton-text [animated]="true" style="width: 50%; height: 2rem; margin: 0 auto 8px"></ion-skeleton-text>
                  <ion-skeleton-text [animated]="true" style="width: 70%; height: 0.8rem; margin: 0 auto"></ion-skeleton-text>
                </ion-card-content></ion-card>
              </ion-col>
            }
          </ion-row>
        </ion-grid>
      } @else if (data()) {
        <!-- Top KPI row -->
        <ion-grid class="no-pad">
          <ion-row>
            <ion-col size="6" sizeMd="3">
              <ion-card>
                <ion-card-content class="kpi-card">
                  <ion-icon name="layers-outline" class="kpi-icon" style="color: var(--ion-color-primary)"></ion-icon>
                  <div class="kpi-value">{{ totalQueueDepth() }}</div>
                  <div class="kpi-label">Queued Jobs</div>
                </ion-card-content>
              </ion-card>
            </ion-col>
            <ion-col size="6" sizeMd="3">
              <ion-card>
                <ion-card-content class="kpi-card">
                  <ion-icon name="pulse-outline" class="kpi-icon" [style.color]="data()!.scheduler.status === 'running' ? 'var(--ion-color-success)' : 'var(--ion-color-danger)'"></ion-icon>
                  <div class="kpi-value">
                    <ion-badge [color]="data()!.scheduler.status === 'running' ? 'success' : 'danger'">
                      {{ data()!.scheduler.status === 'running' ? 'Running' : 'Stopped' }}
                    </ion-badge>
                  </div>
                  <div class="kpi-label">Scheduler</div>
                </ion-card-content>
              </ion-card>
            </ion-col>
            <ion-col size="6" sizeMd="3">
              <ion-card>
                <ion-card-content class="kpi-card">
                  <ion-icon name="alert-circle-outline" class="kpi-icon" [style.color]="data()!.failed_jobs.total > 0 ? 'var(--ion-color-danger)' : 'var(--ion-color-success)'"></ion-icon>
                  <div class="kpi-value" [class.danger-text]="data()!.failed_jobs.total > 0">{{ data()!.failed_jobs.total }}</div>
                  <div class="kpi-label">Failed Jobs</div>
                </ion-card-content>
              </ion-card>
            </ion-col>
            <ion-col size="6" sizeMd="3">
              <ion-card>
                <ion-card-content class="kpi-card">
                  <ion-icon name="checkmark-circle-outline" class="kpi-icon" style="color: var(--ion-color-success)"></ion-icon>
                  <div class="kpi-value">{{ data()!.stats.jobs_processed_24h | number }}</div>
                  <div class="kpi-label">Processed (24h)</div>
                </ion-card-content>
              </ion-card>
            </ion-col>
          </ion-row>
        </ion-grid>

        <!-- Queue Depths -->
        <ion-card>
          <ion-card-header>
            <ion-card-title>Queue Depths</ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <ion-grid class="no-pad">
              <ion-row>
                @for (q of queueEntries(); track q.name) {
                  <ion-col size="6">
                    <div class="queue-depth-cell">
                      <div class="queue-name">{{ q.name }}</div>
                      <div class="queue-bar-wrap">
                        <div class="queue-bar" [style.width.%]="getDepthBarWidth(q.depth)" [style.background]="getDepthColor(q.depth)"></div>
                      </div>
                      <div class="queue-count" [style.color]="getDepthColor(q.depth)">{{ q.depth }}</div>
                    </div>
                  </ion-col>
                }
              </ion-row>
            </ion-grid>
          </ion-card-content>
        </ion-card>

        <!-- Scheduler & Stats -->
        <ion-grid>
          <ion-row>
            <ion-col size="12" sizeMd="6">
              <ion-card>
                <ion-card-header><ion-card-title>Scheduler Info</ion-card-title></ion-card-header>
                <ion-card-content>
                  <ion-list lines="none">
                    <ion-item>
                      <ion-label>Status</ion-label>
                      <ion-badge slot="end" [color]="data()!.scheduler.status === 'running' ? 'success' : 'danger'">
                        {{ data()!.scheduler.status }}
                      </ion-badge>
                    </ion-item>
                    <ion-item>
                      <ion-label>Last Heartbeat</ion-label>
                      <ion-note slot="end">{{ data()!.scheduler.last_run ? formatDate(data()!.scheduler.last_run!) : 'Never' }}</ion-note>
                    </ion-item>
                    <ion-item>
                      <ion-label>Workers Active</ion-label>
                      <ion-note slot="end">{{ data()!.workers.active }}</ion-note>
                    </ion-item>
                  </ion-list>
                </ion-card-content>
              </ion-card>
            </ion-col>
            <ion-col size="12" sizeMd="6">
              <ion-card>
                <ion-card-header><ion-card-title>24h Statistics</ion-card-title></ion-card-header>
                <ion-card-content>
                  <ion-list lines="none">
                    <ion-item>
                      <ion-label>Jobs Processed</ion-label>
                      <ion-note slot="end" style="color: var(--ion-color-success); font-weight: 600">{{ data()!.stats.jobs_processed_24h | number }}</ion-note>
                    </ion-item>
                    <ion-item>
                      <ion-label>Jobs Failed</ion-label>
                      <ion-note slot="end" [style.color]="data()!.stats.jobs_failed_24h > 0 ? 'var(--ion-color-danger)' : 'var(--ion-color-success)'" style="font-weight: 600">{{ data()!.stats.jobs_failed_24h }}</ion-note>
                    </ion-item>
                    <ion-item>
                      <ion-label>Success Rate</ion-label>
                      <ion-note slot="end" style="font-weight: 600">{{ getSuccessRate() }}%</ion-note>
                    </ion-item>
                  </ion-list>
                </ion-card-content>
              </ion-card>
            </ion-col>
          </ion-row>
        </ion-grid>

        <!-- Recent Failed Jobs -->
        <ion-card>
          <ion-card-header>
            <ion-card-title style="display: flex; justify-content: space-between; align-items: center;">
              Recent Failed Jobs
              @if (data()!.failed_jobs.total > 0) {
                <ion-button fill="outline" size="small" color="danger" (click)="confirmPurge()">
                  <ion-icon name="trash-outline" slot="start"></ion-icon>
                  Purge All
                </ion-button>
              }
            </ion-card-title>
          </ion-card-header>
          <ion-card-content>
            @if (data()!.failed_jobs.recent.length === 0) {
              <p class="empty-text">No failed jobs -- all clear!</p>
            } @else {
              <ion-list>
                @for (job of data()!.failed_jobs.recent; track job.id) {
                  <ion-item>
                    <ion-label>
                      <h3 style="font-weight: 600">{{ shortClassName(job.class) }}</h3>
                      <p>Queue: {{ job.queue }} &middot; {{ job.created ? formatDate(job.created) : 'Unknown time' }}</p>
                      @if (job.exception) {
                        <p class="exception-text">{{ job.exception }}</p>
                      }
                    </ion-label>
                    <ion-button slot="end" fill="clear" color="primary" (click)="retryJob(job.id)" title="Retry">
                      <ion-icon name="reload-outline" slot="icon-only"></ion-icon>
                    </ion-button>
                  </ion-item>
                }
              </ion-list>
            }
          </ion-card-content>
        </ion-card>

        <div class="auto-refresh-note">
          Auto-refreshes every 10 seconds
        </div>
      }
    </ion-content>
  `,
  styles: [`
    .no-pad { --ion-grid-padding: 0; }
    .kpi-card { text-align: center; padding: 16px 8px; }
    .kpi-icon { font-size: 1.8rem; margin-bottom: 4px; }
    .kpi-value { font-family: 'DM Sans', sans-serif; font-size: 1.5rem; font-weight: 700; margin: 4px 0; }
    .kpi-label { font-size: 0.65rem; color: var(--ion-color-medium); text-transform: uppercase; letter-spacing: 0.05em; }
    .danger-text { color: var(--ion-color-danger); }
    .success-text { color: var(--ion-color-success); }

    .queue-depth-cell { padding: 12px 8px; }
    .queue-name { font-weight: 600; font-size: 0.9rem; margin-bottom: 6px; text-transform: capitalize; }
    .queue-bar-wrap { height: 10px; background: var(--ion-color-light); border-radius: 5px; overflow: hidden; margin-bottom: 4px; }
    .queue-bar { height: 100%; border-radius: 5px; min-width: 2px; transition: width 0.3s; }
    .queue-count { font-weight: 700; font-size: 1.1rem; }

    .exception-text { font-size: 0.75rem; color: var(--ion-color-danger); font-family: monospace; white-space: pre-wrap; word-break: break-word; max-height: 60px; overflow: hidden; margin-top: 4px; }
    .empty-text { text-align: center; color: var(--ion-color-medium); padding: 1rem; }
    .auto-refresh-note { text-align: center; color: var(--ion-color-medium); font-size: 0.75rem; padding: 8px 0 16px; }
  `],
})
export class QueueDashboardComponent implements OnInit, OnDestroy {
  data = signal<QueueDashboardData | null>(null);
  loading = signal(true);
  private refreshInterval: ReturnType<typeof setInterval> | null = null;

  constructor(
    private api: ApiService,
    private toastCtrl: ToastController,
    private alertCtrl: AlertController,
  ) {}

  ngOnInit(): void {
    this.load();
    this.refreshInterval = setInterval(() => this.silentRefresh(), 10000);
  }

  ngOnDestroy(): void {
    if (this.refreshInterval) {
      clearInterval(this.refreshInterval);
    }
  }

  load(): void {
    this.loading.set(true);
    this.api.get<QueueDashboardData>('/super-admin/queue').subscribe({
      next: (d) => { this.data.set(d); this.loading.set(false); },
      error: async (err: any) => {
        this.loading.set(false);
        const toast = await this.toastCtrl.create({
          message: err?.message || 'Failed to load queue data', color: 'danger', duration: 4000, position: 'bottom',
        });
        await toast.present();
      },
    });
  }

  silentRefresh(): void {
    this.api.get<QueueDashboardData>('/super-admin/queue').subscribe({
      next: (d) => this.data.set(d),
      error: () => {},
    });
  }

  onRefresh(event: any): void {
    this.api.get<QueueDashboardData>('/super-admin/queue').subscribe({
      next: (d) => { this.data.set(d); event.target.complete(); },
      error: () => event.target.complete(),
    });
  }

  totalQueueDepth(): number {
    const queues = this.data()?.queues;
    if (!queues) return 0;
    return Object.values(queues).reduce((sum, q) => sum + q.depth, 0);
  }

  queueEntries(): QueueInfo[] {
    const queues = this.data()?.queues;
    if (!queues) return [];
    return Object.values(queues);
  }

  getDepthBarWidth(depth: number): number {
    if (depth === 0) return 0;
    const max = Math.max(50, ...this.queueEntries().map(q => q.depth));
    return Math.max((depth / max) * 100, 5);
  }

  getDepthColor(depth: number): string {
    if (depth === 0) return 'var(--ion-color-success)';
    if (depth < 10) return 'var(--ion-color-primary)';
    if (depth < 50) return 'var(--ion-color-warning)';
    return 'var(--ion-color-danger)';
  }

  getSuccessRate(): string {
    const d = this.data();
    if (!d) return '0';
    const total = d.stats.jobs_processed_24h + d.stats.jobs_failed_24h;
    if (total === 0) return '100';
    return ((d.stats.jobs_processed_24h / total) * 100).toFixed(1);
  }

  formatDate(iso: string): string {
    try {
      const date = new Date(iso);
      return date.toLocaleString();
    } catch {
      return iso;
    }
  }

  shortClassName(cls: string): string {
    if (!cls) return 'Unknown';
    const parts = cls.split('\\');
    return parts[parts.length - 1];
  }

  async retryJob(id: number): Promise<void> {
    this.api.post<any>(`/super-admin/queue/retry/${id}`, {}).subscribe({
      next: async () => {
        const toast = await this.toastCtrl.create({
          message: `Job #${id} re-queued`, color: 'success', duration: 2000, position: 'bottom',
        });
        await toast.present();
        this.silentRefresh();
      },
      error: async (err: any) => {
        const toast = await this.toastCtrl.create({
          message: err?.message || 'Failed to retry job', color: 'danger', duration: 3000, position: 'bottom',
        });
        await toast.present();
      },
    });
  }

  async confirmPurge(): Promise<void> {
    const alert = await this.alertCtrl.create({
      header: 'Purge Failed Jobs',
      message: 'This will permanently delete all failed jobs. Are you sure?',
      buttons: [
        { text: 'Cancel', role: 'cancel' },
        {
          text: 'Purge',
          role: 'destructive',
          handler: () => this.purgeFailedJobs(),
        },
      ],
    });
    await alert.present();
  }

  private purgeFailedJobs(): void {
    this.api.delete<any>('/super-admin/queue/failed-jobs').subscribe({
      next: async (res: any) => {
        const toast = await this.toastCtrl.create({
          message: res?.message || 'Failed jobs purged', color: 'success', duration: 2000, position: 'bottom',
        });
        await toast.present();
        this.silentRefresh();
      },
      error: async (err: any) => {
        const toast = await this.toastCtrl.create({
          message: err?.message || 'Failed to purge jobs', color: 'danger', duration: 3000, position: 'bottom',
        });
        await toast.present();
      },
    });
  }
}
