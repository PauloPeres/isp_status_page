import { Component, OnInit, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { ViewWillEnter } from '@ionic/angular';
import {
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonButtons,
  IonBackButton,
  IonButton,
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardContent,
  IonGrid,
  IonRow,
  IonCol,
  IonList,
  IonItem,
  IonLabel,
  IonBadge,
  IonChip,
  IonIcon,
  IonNote,
  IonRefresher,
  IonRefresherContent,
  IonSkeletonText,
  AlertController,
} from '@ionic/angular/standalone';
import { Router } from '@angular/router';
import { MonitorService } from './monitor.service';
import { Monitor, MonitorStatus } from '../../core/models/monitor.model';
import { forkJoin } from 'rxjs';
import { addIcons } from 'ionicons';
import {
  createOutline,
  trashOutline,
  pauseOutline,
  playOutline,
} from 'ionicons/icons';

addIcons({ createOutline, trashOutline, pauseOutline, playOutline });

interface UptimeHistoryEntry {
  date: string;
  uptime: number;
  checks: number;
}

interface RegionBreakdown {
  region_id: number;
  region_name: string;
  region_code: string;
  uptime: number;
  avg_response_time: number | null;
  total_checks: number;
}

interface MonitorDetailData {
  monitor: Monitor;
  uptime_24h: number;
  avg_response_time: number | null;
  total_checks_24h: number;
  uptime_history: UptimeHistoryEntry[];
  sla: any | null;
  region_breakdown: RegionBreakdown[];
}

interface Check {
  id: number;
  status: string;
  response_time: number | null;
  checked_at: string;
  status_code?: number;
  error_message?: string;
}

@Component({
  selector: 'app-monitor-detail',
  standalone: true,
  imports: [
    CommonModule,
    RouterLink,
    IonHeader,
    IonToolbar,
    IonTitle,
    IonContent,
    IonButtons,
    IonBackButton,
    IonButton,
    IonCard,
    IonCardHeader,
    IonCardTitle,
    IonCardContent,
    IonGrid,
    IonRow,
    IonCol,
    IonList,
    IonItem,
    IonLabel,
    IonBadge,
    IonChip,
    IonIcon,
    IonNote,
    IonRefresher,
    IonRefresherContent,
    IonSkeletonText,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button defaultHref="/monitors"></ion-back-button>
        </ion-buttons>
        <ion-title>{{ detail()?.monitor?.name ?? 'Monitor Detail' }}</ion-title>
        <ion-buttons slot="end">
          @if (detail()?.monitor) {
            <ion-button
              [routerLink]="['/monitors', detail()!.monitor.id, 'edit']"
            >
              <ion-icon slot="icon-only" name="create-outline"></ion-icon>
            </ion-button>
            <ion-button (click)="onDelete()" color="danger">
              <ion-icon slot="icon-only" name="trash-outline"></ion-icon>
            </ion-button>
          }
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <ion-refresher slot="fixed" (ionRefresh)="onRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

      @if (loading()) {
        <ion-card>
          <ion-card-content>
            <ion-skeleton-text
              [animated]="true"
              style="width: 60%; height: 1.5rem"
            ></ion-skeleton-text>
            <ion-skeleton-text
              [animated]="true"
              style="width: 40%; height: 1rem; margin-top: 8px"
            ></ion-skeleton-text>
          </ion-card-content>
        </ion-card>
      } @else if (error()) {
        <ion-card>
          <ion-card-content class="error-state">
            <p>Failed to load monitor details.</p>
            <ion-button fill="outline" (click)="loadData()">Retry</ion-button>
          </ion-card-content>
        </ion-card>
      } @else if (detail()) {
        <!-- Status + Info Card -->
        <ion-card>
          <ion-card-content>
            <div class="monitor-header">
              <div class="status-section">
                <ion-badge
                  [color]="getStatusColor(detail()!.monitor.status)"
                  class="status-badge"
                >
                  {{ detail()!.monitor.status | uppercase }}
                </ion-badge>
                @if (!detail()!.monitor.active) {
                  <ion-chip color="medium" style="height: 24px">
                    Paused
                  </ion-chip>
                }
              </div>
              <div class="actions-section">
                @if (detail()!.monitor.active) {
                  <ion-button
                    fill="outline"
                    size="small"
                    color="warning"
                    (click)="onPause()"
                  >
                    <ion-icon
                      slot="start"
                      name="pause-outline"
                    ></ion-icon>
                    Pause
                  </ion-button>
                } @else {
                  <ion-button
                    fill="outline"
                    size="small"
                    color="success"
                    (click)="onResume()"
                  >
                    <ion-icon
                      slot="start"
                      name="play-outline"
                    ></ion-icon>
                    Resume
                  </ion-button>
                }
              </div>
            </div>
            @if (detail()!.monitor.description) {
              <p class="description">{{ detail()!.monitor.description }}</p>
            }
            <div class="info-chips">
              <ion-chip color="primary" style="height: 24px">
                {{ detail()!.monitor.type }}
              </ion-chip>
              <ion-chip color="medium" style="height: 24px">
                Every {{ detail()!.monitor.check_interval }}s
              </ion-chip>
              @for (tag of parseTags(detail()!.monitor.tags); track tag) {
                <ion-chip color="tertiary" style="height: 24px">
                  {{ tag }}
                </ion-chip>
              }
            </div>
          </ion-card-content>
        </ion-card>

        <!-- Stats Cards -->
        <ion-grid>
          <ion-row>
            <ion-col size="4">
              <ion-card class="stat-card">
                <ion-card-content>
                  <div class="stat-value"
                    [class.success]="detail()!.uptime_24h >= 99"
                    [class.warning-text]="detail()!.uptime_24h >= 95 && detail()!.uptime_24h < 99"
                    [class.danger-text]="detail()!.uptime_24h < 95"
                  >
                    {{ detail()!.uptime_24h | number: '1.1-2' }}%
                  </div>
                  <div class="stat-label">Uptime (24h)</div>
                </ion-card-content>
              </ion-card>
            </ion-col>
            <ion-col size="4">
              <ion-card class="stat-card">
                <ion-card-content>
                  <div class="stat-value">
                    {{
                      detail()!.avg_response_time !== null
                        ? (detail()!.avg_response_time | number: '1.0-0') +
                          'ms'
                        : 'N/A'
                    }}
                  </div>
                  <div class="stat-label">Avg Response</div>
                </ion-card-content>
              </ion-card>
            </ion-col>
            <ion-col size="4">
              <ion-card class="stat-card">
                <ion-card-content>
                  <div class="stat-value">
                    {{ detail()!.total_checks_24h }}
                  </div>
                  <div class="stat-label">Checks (24h)</div>
                </ion-card-content>
              </ion-card>
            </ion-col>
          </ion-row>
        </ion-grid>

        <!-- 30-Day Uptime History -->
        <ion-card>
          <ion-card-header>
            <ion-card-title>30-Day Uptime</ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <div class="uptime-history">
              @for (day of detail()!.uptime_history; track day.date) {
                <div
                  class="uptime-day"
                  [class.day-success]="day.uptime >= 99"
                  [class.day-warning]="day.uptime >= 95 && day.uptime < 99"
                  [class.day-danger]="day.uptime < 95 && day.checks > 0"
                  [class.day-empty]="day.checks === 0"
                  [title]="day.date + ': ' + day.uptime + '% (' + day.checks + ' checks)'"
                ></div>
              }
            </div>
          </ion-card-content>
        </ion-card>

        <!-- SLA Info -->
        @if (detail()!.sla) {
          <ion-card>
            <ion-card-header>
              <ion-card-title>SLA: {{ detail()!.sla.sla_name }}</ion-card-title>
            </ion-card-header>
            <ion-card-content>
              <ion-grid>
                <ion-row>
                  <ion-col size="6">
                    <div class="stat-label">Current Uptime</div>
                    <div class="stat-value">
                      {{ detail()!.sla.current_uptime | number: '1.2-2' }}%
                    </div>
                  </ion-col>
                  <ion-col size="6">
                    <div class="stat-label">Target</div>
                    <div class="stat-value">
                      {{ detail()!.sla.target_uptime | number: '1.2-2' }}%
                    </div>
                  </ion-col>
                </ion-row>
                <ion-row>
                  <ion-col size="12">
                    <ion-badge
                      [color]="
                        detail()!.sla.status === 'compliant'
                          ? 'success'
                          : detail()!.sla.status === 'at_risk'
                            ? 'warning'
                            : 'danger'
                      "
                    >
                      {{ detail()!.sla.status | uppercase }}
                    </ion-badge>
                  </ion-col>
                </ion-row>
              </ion-grid>
            </ion-card-content>
          </ion-card>
        }

        <!-- Region Breakdown (C-01) -->
        @if (detail()!.region_breakdown && detail()!.region_breakdown.length > 0) {
          <ion-card>
            <ion-card-header>
              <ion-card-title>Check Regions (24h)</ion-card-title>
            </ion-card-header>
            <ion-card-content class="list-card-content">
              <ion-list>
                @for (region of detail()!.region_breakdown; track region.region_id) {
                  <ion-item>
                    <ion-label>
                      <h3>{{ region.region_name }}</h3>
                      <p>
                        {{ region.uptime | number: '1.1-2' }}% uptime
                        @if (region.avg_response_time !== null) {
                          &mdash; {{ region.avg_response_time | number: '1.0-0' }}ms avg
                        }
                        &mdash; {{ region.total_checks }} checks
                      </p>
                    </ion-label>
                    <ion-badge
                      slot="end"
                      [color]="region.uptime >= 99 ? 'success' : region.uptime >= 95 ? 'warning' : 'danger'"
                    >
                      {{ region.region_code }}
                    </ion-badge>
                  </ion-item>
                }
              </ion-list>
            </ion-card-content>
          </ion-card>
        }

        <!-- Response Time Chart -->
        @if (recentChecks().length) {
          <ion-card>
            <ion-card-header>
              <ion-card-title>Response Times</ion-card-title>
            </ion-card-header>
            <ion-card-content>
              <div class="response-chart">
                @for (check of recentChecks(); track check.id) {
                  <div class="chart-bar-wrap" [title]="check.checked_at + ': ' + (check.response_time ?? 0) + 'ms'">
                    <div class="chart-bar"
                      [style.height.%]="getBarHeight(check.response_time)"
                      [class.bar-good]="check.response_time !== null && check.response_time < 300"
                      [class.bar-warn]="check.response_time !== null && check.response_time >= 300 && check.response_time < 1000"
                      [class.bar-bad]="check.response_time === null || check.response_time >= 1000"
                    ></div>
                  </div>
                }
              </div>

              <!-- Quick Stats -->
              <div class="check-stats">
                <div class="check-stat">
                  <span class="check-stat-value" [class.success-text]="getSuccessRate() >= 99">{{ getSuccessRate() | number:'1.0-0' }}%</span>
                  <span class="check-stat-label">Success Rate</span>
                </div>
                <div class="check-stat">
                  <span class="check-stat-value">{{ getAvgResponseTime() | number:'1.0-0' }}ms</span>
                  <span class="check-stat-label">Avg Response</span>
                </div>
                <div class="check-stat">
                  <span class="check-stat-value">{{ getLastCheckTime() }}</span>
                  <span class="check-stat-label">Last Check</span>
                </div>
              </div>
            </ion-card-content>
          </ion-card>
        }

        <!-- Recent Failures -->
        <ion-card>
          <ion-card-header>
            <ion-card-title>Recent Failures</ion-card-title>
          </ion-card-header>
          <ion-card-content class="list-card-content">
            <ion-list>
              @for (check of getRecentFailures(); track check.id) {
                <ion-item>
                  <ion-badge color="danger" slot="start">{{ check.status }}</ion-badge>
                  <ion-label>
                    <h3>{{ check.response_time ? check.response_time + 'ms' : 'Timeout' }}</h3>
                    <p>{{ check.checked_at | date: 'medium' }}</p>
                    @if (check.error_message) {
                      <p class="error-text">{{ check.error_message }}</p>
                    }
                  </ion-label>
                </ion-item>
              } @empty {
                <ion-item>
                  <ion-label style="text-align: center; color: var(--ion-color-success)">No failures in recent checks</ion-label>
                </ion-item>
              }
            </ion-list>
          </ion-card-content>
        </ion-card>
      }
    </ion-content>
  `,
  styles: [
    `
      .monitor-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
      }
      .status-section {
        display: flex;
        align-items: center;
        gap: 8px;
      }
      .status-badge {
        font-size: 0.9rem;
        padding: 6px 16px;
      }
      .description {
        color: var(--ion-color-medium);
        margin: 8px 0 12px;
      }
      .info-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
      }

      .stat-card {
        text-align: center;
      }
      .stat-value {
        font-family: 'DM Sans', sans-serif;
        font-size: 1.4rem;
        font-weight: 700;
      }
      .stat-value.success {
        color: var(--ion-color-success);
      }
      .warning-text {
        color: var(--ion-color-warning);
      }
      .danger-text {
        color: var(--ion-color-danger);
      }
      .error-state {
        text-align: center;
        padding: 2rem 1rem;
        color: var(--ion-color-medium);
      }
      .stat-label {
        font-size: 0.75rem;
        color: var(--ion-color-medium);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-top: 4px;
      }

      .uptime-history {
        display: flex;
        gap: 2px;
        flex-wrap: wrap;
      }
      .uptime-day {
        width: 16px;
        height: 24px;
        border-radius: 2px;
        cursor: pointer;
      }
      .day-success {
        background: var(--ion-color-success);
      }
      .day-warning {
        background: var(--ion-color-warning);
      }
      .day-danger {
        background: var(--ion-color-danger);
      }
      .day-empty {
        background: var(--ion-color-light);
      }

      .list-card-content {
        padding: 0;
      }

      .error-text {
        color: var(--ion-color-danger);
        font-size: 0.75rem;
      }

      ion-badge {
        text-transform: capitalize;
      }

      .response-chart {
        display: flex;
        align-items: flex-end;
        gap: 2px;
        height: 80px;
        padding: 0 4px;
      }
      .chart-bar-wrap {
        flex: 1;
        height: 100%;
        display: flex;
        align-items: flex-end;
        cursor: pointer;
      }
      .chart-bar {
        width: 100%;
        border-radius: 2px 2px 0 0;
        min-height: 4px;
        transition: height 0.2s;
      }
      .bar-good { background: var(--ion-color-success); }
      .bar-warn { background: var(--ion-color-warning); }
      .bar-bad { background: var(--ion-color-danger); }

      .check-stats {
        display: flex;
        justify-content: space-around;
        margin-top: 16px;
        padding-top: 12px;
        border-top: 1px solid var(--ion-color-light);
      }
      .check-stat {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
      }
      .check-stat-value {
        font-family: 'DM Sans', sans-serif;
        font-size: 1.1rem;
        font-weight: 700;
      }
      .check-stat-label {
        font-size: 0.7rem;
        color: var(--ion-color-medium);
        text-transform: uppercase;
        letter-spacing: 0.05em;
      }
      .success-text {
        color: var(--ion-color-success);
      }
    `,
  ],
})
export class MonitorDetailComponent implements OnInit, ViewWillEnter {
  detail = signal<MonitorDetailData | null>(null);
  checks = signal<Check[]>([]);
  loading = signal(true);
  error = signal(false);

  private monitorId = 0;

  recentChecks = computed(() => this.checks().slice(0, 20));

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private monitorService: MonitorService,
    private alertCtrl: AlertController,
  ) {}

  ngOnInit(): void {
    this.monitorId = Number(this.route.snapshot.paramMap.get('id'));
  }

  ionViewWillEnter(): void {
    this.loadData();
  }

  loadData(): void {
    this.loading.set(true);
    this.error.set(false);

    forkJoin({
      detail: this.monitorService.getMonitor(this.monitorId),
      checks: this.monitorService.getChecks(this.monitorId, {
        page: 1,
        limit: 25,
      }),
    }).subscribe({
      next: (data) => {
        this.detail.set(data.detail as MonitorDetailData);
        this.checks.set(data.checks.items as Check[]);
        this.loading.set(false);
      },
      error: () => {
        this.error.set(true);
        this.loading.set(false);
      },
    });
  }

  onRefresh(event: any): void {
    forkJoin({
      detail: this.monitorService.getMonitor(this.monitorId),
      checks: this.monitorService.getChecks(this.monitorId, {
        page: 1,
        limit: 25,
      }),
    }).subscribe({
      next: (data) => {
        this.detail.set(data.detail as MonitorDetailData);
        this.checks.set(data.checks.items as Check[]);
        event.target.complete();
      },
      error: () => {
        event.target.complete();
      },
    });
  }

  getBarHeight(ms: number | null): number {
    if (ms === null || ms === 0) return 5;
    const checks = this.recentChecks();
    const maxMs = Math.max(...checks.map(c => c.response_time ?? 0), 1);
    return Math.max(5, (ms / maxMs) * 100);
  }

  getSuccessRate(): number {
    const checks = this.checks();
    if (!checks.length) return 0;
    const successes = checks.filter(c => c.status === 'success').length;
    return (successes / checks.length) * 100;
  }

  getAvgResponseTime(): number {
    const checks = this.checks().filter(c => c.response_time !== null);
    if (!checks.length) return 0;
    const sum = checks.reduce((acc, c) => acc + (c.response_time ?? 0), 0);
    return sum / checks.length;
  }

  getLastCheckTime(): string {
    const checks = this.checks();
    if (!checks.length) return 'Never';
    const last = new Date(checks[0].checked_at);
    const diff = Math.floor((Date.now() - last.getTime()) / 1000);
    if (diff < 60) return diff + 's ago';
    if (diff < 3600) return Math.floor(diff / 60) + 'min ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    return Math.floor(diff / 86400) + 'd ago';
  }

  getRecentFailures(): Check[] {
    return this.checks()
      .filter(c => c.status !== 'success')
      .slice(0, 10);
  }

  onPause(): void {
    this.monitorService.pauseMonitor(this.monitorId).subscribe(() => {
      this.detail.update((d) =>
        d
          ? { ...d, monitor: { ...d.monitor, active: false } }
          : d,
      );
    });
  }

  onResume(): void {
    this.monitorService.resumeMonitor(this.monitorId).subscribe(() => {
      this.detail.update((d) =>
        d
          ? { ...d, monitor: { ...d.monitor, active: true } }
          : d,
      );
    });
  }

  async onDelete(): Promise<void> {
    const monitor = this.detail()?.monitor;
    if (!monitor) return;

    const alert = await this.alertCtrl.create({
      header: 'Delete Monitor',
      message: `Are you sure you want to delete "${monitor.name}"? This action cannot be undone.`,
      buttons: [
        { text: 'Cancel', role: 'cancel' },
        {
          text: 'Delete',
          role: 'destructive',
          handler: () => {
            this.monitorService.deleteMonitor(monitor.id).subscribe(() => {
              this.router.navigate(['/monitors']);
            });
          },
        },
      ],
    });
    await alert.present();
  }

  parseTags(tags: any): string[] {
    if (!tags) return [];
    if (Array.isArray(tags)) return tags;
    if (typeof tags === 'string') {
      try {
        const parsed = JSON.parse(tags);
        if (Array.isArray(parsed)) return parsed;
      } catch {}
      return tags.split(',').map((t: string) => t.trim()).filter((t: string) => t);
    }
    return [];
  }

  getStatusColor(status: MonitorStatus): string {
    switch (status) {
      case 'up':
        return 'success';
      case 'down':
        return 'danger';
      case 'degraded':
        return 'warning';
      default:
        return 'medium';
    }
  }
}
