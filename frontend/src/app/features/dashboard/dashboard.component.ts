import { Component, OnInit, OnDestroy, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import {
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonMenuButton,
  IonButtons,
  IonGrid,
  IonRow,
  IonCol,
  IonCard,
  IonCardHeader,
  IonCardTitle,
  IonCardContent,
  IonList,
  IonItem,
  IonLabel,
  IonBadge,
  IonChip,
  IonSkeletonText,
  IonRefresher,
  IonRefresherContent,
  ToastController,
} from '@ionic/angular/standalone';
import {
  DashboardService,
  DashboardSummary,
  UptimeData,
  ResponseTimeData,
  RecentCheck,
  RecentAlert,
} from './dashboard.service';
import { forkJoin, Subscription } from 'rxjs';
import { SseService } from '../../core/services/sse.service';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [
    CommonModule,
    IonHeader,
    IonToolbar,
    IonTitle,
    IonContent,
    IonMenuButton,
    IonButtons,
    IonGrid,
    IonRow,
    IonCol,
    IonCard,
    IonCardHeader,
    IonCardTitle,
    IonCardContent,
    IonList,
    IonItem,
    IonLabel,
    IonBadge,
    IonChip,
    IonSkeletonText,
    IonRefresher,
    IonRefresherContent,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-menu-button></ion-menu-button>
        </ion-buttons>
        <ion-title>Dashboard</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <ion-refresher slot="fixed" (ionRefresh)="onRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

      <!-- Summary Cards -->
      <ion-grid>
        <ion-row>
          <ion-col size="6" sizeMd="3">
            <ion-card class="summary-card">
              <ion-card-content>
                @if (loading()) {
                  <ion-skeleton-text [animated]="true" style="width: 40%; height: 2rem; margin: 0 auto;"></ion-skeleton-text>
                } @else {
                  <div class="summary-value">{{ summary()?.monitors?.total ?? 0 }}</div>
                }
                <div class="summary-label">Total Monitors</div>
              </ion-card-content>
            </ion-card>
          </ion-col>
          <ion-col size="6" sizeMd="3">
            <ion-card class="summary-card card-success">
              <ion-card-content>
                @if (loading()) {
                  <ion-skeleton-text [animated]="true" style="width: 40%; height: 2rem; margin: 0 auto;"></ion-skeleton-text>
                } @else {
                  <div class="summary-value">{{ summary()?.monitors?.up ?? 0 }}</div>
                }
                <div class="summary-label">Up</div>
              </ion-card-content>
            </ion-card>
          </ion-col>
          <ion-col size="6" sizeMd="3">
            <ion-card class="summary-card card-danger">
              <ion-card-content>
                @if (loading()) {
                  <ion-skeleton-text [animated]="true" style="width: 40%; height: 2rem; margin: 0 auto;"></ion-skeleton-text>
                } @else {
                  <div class="summary-value">{{ summary()?.monitors?.down ?? 0 }}</div>
                }
                <div class="summary-label">Down</div>
              </ion-card-content>
            </ion-card>
          </ion-col>
          <ion-col size="6" sizeMd="3">
            <ion-card class="summary-card card-warning">
              <ion-card-content>
                @if (loading()) {
                  <ion-skeleton-text [animated]="true" style="width: 40%; height: 2rem; margin: 0 auto;"></ion-skeleton-text>
                } @else {
                  <div class="summary-value">{{ summary()?.monitors?.degraded ?? 0 }}</div>
                }
                <div class="summary-label">Degraded</div>
              </ion-card-content>
            </ion-card>
          </ion-col>
        </ion-row>
      </ion-grid>

      <!-- Active Incidents + SLA Status -->
      <ion-grid>
        <ion-row>
          <ion-col size="12" sizeMd="6">
            <ion-card>
              <ion-card-header>
                <ion-card-title>Active Incidents</ion-card-title>
              </ion-card-header>
              <ion-card-content>
                @if (loading()) {
                  <ion-skeleton-text [animated]="true" style="width: 100%; height: 3rem;"></ion-skeleton-text>
                } @else {
                  @if ((summary()?.active_incidents?.total ?? 0) === 0) {
                    <p class="empty-text">No active incidents</p>
                  } @else {
                    <div class="incident-summary">
                      <div class="incident-total">{{ summary()?.active_incidents?.total }} active</div>
                      <div class="severity-badges">
                        @if ((summary()?.active_incidents?.by_severity?.critical ?? 0) > 0) {
                          <ion-badge color="danger">Critical: {{ summary()?.active_incidents?.by_severity?.critical }}</ion-badge>
                        }
                        @if ((summary()?.active_incidents?.by_severity?.major ?? 0) > 0) {
                          <ion-badge color="warning">Major: {{ summary()?.active_incidents?.by_severity?.major }}</ion-badge>
                        }
                        @if ((summary()?.active_incidents?.by_severity?.minor ?? 0) > 0) {
                          <ion-badge color="medium">Minor: {{ summary()?.active_incidents?.by_severity?.minor }}</ion-badge>
                        }
                        @if ((summary()?.active_incidents?.by_severity?.maintenance ?? 0) > 0) {
                          <ion-badge color="tertiary">Maintenance: {{ summary()?.active_incidents?.by_severity?.maintenance }}</ion-badge>
                        }
                      </div>
                    </div>
                  }
                }
              </ion-card-content>
            </ion-card>
          </ion-col>
          <ion-col size="12" sizeMd="6">
            <ion-card>
              <ion-card-header>
                <ion-card-title>SLA Status</ion-card-title>
              </ion-card-header>
              <ion-card-content>
                @if (loading()) {
                  <ion-skeleton-text [animated]="true" style="width: 100%; height: 3rem;"></ion-skeleton-text>
                } @else {
                  <div class="sla-summary">
                    <ion-chip color="success">
                      <ion-label>Compliant: {{ summary()?.sla?.compliant ?? 0 }}</ion-label>
                    </ion-chip>
                    <ion-chip color="warning">
                      <ion-label>At Risk: {{ summary()?.sla?.at_risk ?? 0 }}</ion-label>
                    </ion-chip>
                    <ion-chip color="danger">
                      <ion-label>Breached: {{ summary()?.sla?.breached ?? 0 }}</ion-label>
                    </ion-chip>
                  </div>
                }
              </ion-card-content>
            </ion-card>
          </ion-col>
        </ion-row>
      </ion-grid>

      <!-- Uptime per Monitor -->
      <ion-card>
        <ion-card-header>
          <ion-card-title>Uptime (Last 24h)</ion-card-title>
        </ion-card-header>
        <ion-card-content>
          @if (loading()) {
            @for (i of skeletonRows; track i) {
              <div class="uptime-row">
                <ion-skeleton-text [animated]="true" style="width: 150px; height: 1rem;"></ion-skeleton-text>
                <ion-skeleton-text [animated]="true" style="flex: 1; height: 8px;"></ion-skeleton-text>
                <ion-skeleton-text [animated]="true" style="width: 60px; height: 1rem;"></ion-skeleton-text>
              </div>
            }
          } @else {
            @for (item of uptimeData(); track item.id) {
              <div class="uptime-row">
                <span class="uptime-name">{{ item.name }}</span>
                <div class="uptime-bar-container">
                  <div
                    class="uptime-bar"
                    [style.width.%]="item.uptime"
                    [class.bar-success]="item.uptime >= 99"
                    [class.bar-warning]="item.uptime >= 95 && item.uptime < 99"
                    [class.bar-danger]="item.uptime < 95"
                  ></div>
                </div>
                <span class="uptime-percent">{{ item.uptime | number : '1.1-1' }}%</span>
              </div>
            } @empty {
              <p class="empty-text">No monitors configured yet.</p>
            }
          }
        </ion-card-content>
      </ion-card>

      <!-- Response Times -->
      <ion-card>
        <ion-card-header>
          <ion-card-title>Response Times (Avg, Last 24h)</ion-card-title>
        </ion-card-header>
        <ion-card-content>
          @if (loading()) {
            @for (i of skeletonRows; track i) {
              <div class="response-row">
                <ion-skeleton-text [animated]="true" style="width: 150px; height: 1rem;"></ion-skeleton-text>
                <ion-skeleton-text [animated]="true" style="flex: 1; height: 20px;"></ion-skeleton-text>
                <ion-skeleton-text [animated]="true" style="width: 60px; height: 1rem;"></ion-skeleton-text>
              </div>
            }
          } @else {
            @for (item of responseTimeData(); track item.id) {
              <div class="response-row">
                <span class="uptime-name">{{ item.name }}</span>
                <div class="response-bar-container">
                  <div
                    class="response-bar"
                    [style.width.%]="getResponseBarWidth(item.avg_response_time)"
                    [class.bar-success]="item.avg_response_time < 300"
                    [class.bar-warning]="item.avg_response_time >= 300 && item.avg_response_time < 1000"
                    [class.bar-danger]="item.avg_response_time >= 1000"
                  ></div>
                </div>
                <span class="uptime-percent">{{ item.avg_response_time }}ms</span>
              </div>
            } @empty {
              <p class="empty-text">No response time data available.</p>
            }
          }
        </ion-card-content>
      </ion-card>

      <!-- Recent Checks + Recent Alerts -->
      <ion-grid>
        <ion-row>
          <ion-col size="12" sizeLg="6">
            <ion-card>
              <ion-card-header>
                <ion-card-title>Recent Checks</ion-card-title>
              </ion-card-header>
              <ion-card-content class="list-card-content">
                <ion-list>
                  @if (loading()) {
                    @for (i of skeletonRows; track i) {
                      <ion-item>
                        <ion-skeleton-text [animated]="true" style="width: 60px; height: 1.2rem;" slot="start"></ion-skeleton-text>
                        <ion-label>
                          <ion-skeleton-text [animated]="true" style="width: 60%; height: 1rem;"></ion-skeleton-text>
                          <ion-skeleton-text [animated]="true" style="width: 40%; height: 0.8rem;"></ion-skeleton-text>
                        </ion-label>
                      </ion-item>
                    }
                  } @else {
                    @for (check of recentChecks(); track check.id) {
                      <ion-item>
                        <ion-badge
                          [color]="check.status === 'success' ? 'success' : 'danger'"
                          slot="start"
                        >
                          {{ check.status }}
                        </ion-badge>
                        <ion-label>
                          <h3>{{ check.monitor?.name }}</h3>
                          <p>{{ check.response_time }}ms &mdash; {{ check.checked_at | date : 'short' }}</p>
                        </ion-label>
                      </ion-item>
                    } @empty {
                      <ion-item>
                        <ion-label>No recent checks</ion-label>
                      </ion-item>
                    }
                  }
                </ion-list>
              </ion-card-content>
            </ion-card>
          </ion-col>
          <ion-col size="12" sizeLg="6">
            <ion-card>
              <ion-card-header>
                <ion-card-title>Recent Alerts</ion-card-title>
              </ion-card-header>
              <ion-card-content class="list-card-content">
                <ion-list>
                  @if (loading()) {
                    @for (i of skeletonRows; track i) {
                      <ion-item>
                        <ion-skeleton-text [animated]="true" style="width: 60px; height: 1.2rem;" slot="start"></ion-skeleton-text>
                        <ion-label>
                          <ion-skeleton-text [animated]="true" style="width: 60%; height: 1rem;"></ion-skeleton-text>
                          <ion-skeleton-text [animated]="true" style="width: 40%; height: 0.8rem;"></ion-skeleton-text>
                        </ion-label>
                      </ion-item>
                    }
                  } @else {
                    @for (alert of recentAlerts(); track alert.id) {
                      <ion-item>
                        <ion-badge
                          [color]="alert.status === 'sent' ? 'success' : 'danger'"
                          slot="start"
                        >
                          {{ alert.status }}
                        </ion-badge>
                        <ion-label>
                          <h3>{{ alert.monitor?.name }} &mdash; {{ alert.alert_rule?.name }}</h3>
                          <p>{{ alert.channel }} &mdash; {{ alert.created | date : 'short' }}</p>
                        </ion-label>
                      </ion-item>
                    } @empty {
                      <ion-item>
                        <ion-label>No recent alerts</ion-label>
                      </ion-item>
                    }
                  }
                </ion-list>
              </ion-card-content>
            </ion-card>
          </ion-col>
        </ion-row>
      </ion-grid>
    </ion-content>
  `,
  styles: [
    `
      .summary-card {
        text-align: center;
      }
      .summary-value {
        font-family: 'DM Sans', sans-serif;
        font-size: 2rem;
        font-weight: 700;
      }
      .summary-label {
        font-size: 0.8rem;
        color: var(--ion-color-medium);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-top: 4px;
      }
      .card-success .summary-value {
        color: var(--ion-color-success);
      }
      .card-danger .summary-value {
        color: var(--ion-color-danger);
      }
      .card-warning .summary-value {
        color: var(--ion-color-warning);
      }

      .incident-summary {
        display: flex;
        flex-direction: column;
        gap: 12px;
      }
      .incident-total {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--ion-color-danger);
      }
      .severity-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
      }

      .sla-summary {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: center;
      }

      .uptime-row,
      .response-row {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
      }
      .uptime-name {
        width: 150px;
        font-size: 0.875rem;
        flex-shrink: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      .uptime-bar-container,
      .response-bar-container {
        flex: 1;
        height: 8px;
        background: var(--ion-color-light);
        border-radius: 4px;
        overflow: hidden;
      }
      .response-bar-container {
        height: 20px;
      }
      .uptime-bar,
      .response-bar {
        height: 100%;
        border-radius: 4px;
        transition: width 0.3s ease;
      }
      .bar-success {
        background: var(--ion-color-success);
      }
      .bar-warning {
        background: var(--ion-color-warning);
      }
      .bar-danger {
        background: var(--ion-color-danger);
      }
      .uptime-percent {
        width: 70px;
        text-align: right;
        font-weight: 600;
        font-size: 0.875rem;
        flex-shrink: 0;
      }

      .empty-text {
        color: var(--ion-color-medium);
        text-align: center;
        padding: 1rem;
      }

      .list-card-content {
        padding: 0;
      }

      ion-badge {
        text-transform: capitalize;
      }
    `,
  ],
})
export class DashboardComponent implements OnInit, OnDestroy {
  summary = signal<DashboardSummary | null>(null);
  uptimeData = signal<UptimeData[]>([]);
  responseTimeData = signal<ResponseTimeData[]>([]);
  recentChecks = signal<RecentCheck[]>([]);
  recentAlerts = signal<RecentAlert[]>([]);
  loading = signal(true);

  skeletonRows = [1, 2, 3, 4, 5];

  private maxResponseTime = 2000;
  private sseSubscription: Subscription | null = null;

  constructor(
    private dashboardService: DashboardService,
    private sseService: SseService,
    private toastCtrl: ToastController,
  ) {}

  ngOnInit(): void {
    this.loadData();
    this.connectSSE();
  }

  ngOnDestroy(): void {
    this.sseSubscription?.unsubscribe();
  }

  private connectSSE(): void {
    this.sseSubscription = this.sseService.connect().subscribe({
      next: (event) => {
        if (event.type === 'monitor_status') {
          // Refresh summary counts when a monitor status changes
          this.dashboardService
            .getSummary()
            .subscribe((data) => this.summary.set(data));
        }
        if (event.type === 'incident_created') {
          this.toastCtrl
            .create({
              message: `New incident: ${event.data.title}`,
              color: 'danger',
              duration: 5000,
              position: 'top',
            })
            .then((t) => t.present());
          // Refresh all data
          this.loadData();
        }
      },
      error: () => {
        // SSE connection failed — silent fallback to manual refresh
      },
    });
  }

  loadData(): void {
    this.loading.set(true);

    forkJoin({
      summary: this.dashboardService.getSummary(),
      uptime: this.dashboardService.getUptime(),
      responseTimes: this.dashboardService.getResponseTimes(),
      recentChecks: this.dashboardService.getRecentChecks(),
      recentAlerts: this.dashboardService.getRecentAlerts(),
    }).subscribe({
      next: (data) => {
        this.summary.set(data.summary);
        this.uptimeData.set(data.uptime);
        this.responseTimeData.set(data.responseTimes);
        this.recentChecks.set(data.recentChecks);
        this.recentAlerts.set(data.recentAlerts);

        // Calculate max response time for bar scaling
        const times = data.responseTimes.map((r) => r.avg_response_time);
        if (times.length > 0) {
          this.maxResponseTime = Math.max(...times, 100);
        }

        this.loading.set(false);
      },
      error: () => {
        this.loading.set(false);
      },
    });
  }

  onRefresh(event: any): void {
    forkJoin({
      summary: this.dashboardService.getSummary(),
      uptime: this.dashboardService.getUptime(),
      responseTimes: this.dashboardService.getResponseTimes(),
      recentChecks: this.dashboardService.getRecentChecks(),
      recentAlerts: this.dashboardService.getRecentAlerts(),
    }).subscribe({
      next: (data) => {
        this.summary.set(data.summary);
        this.uptimeData.set(data.uptime);
        this.responseTimeData.set(data.responseTimes);
        this.recentChecks.set(data.recentChecks);
        this.recentAlerts.set(data.recentAlerts);
        event.target.complete();
      },
      error: () => {
        event.target.complete();
      },
    });
  }

  getResponseBarWidth(avgTime: number): number {
    if (this.maxResponseTime <= 0) return 0;
    return Math.min((avgTime / this.maxResponseTime) * 100, 100);
  }
}
