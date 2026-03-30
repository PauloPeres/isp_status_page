import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons,
  IonCard, IonCardHeader, IonCardTitle, IonCardContent,
  IonGrid, IonRow, IonCol, IonBadge, IonIcon, IonButton, IonChip, IonLabel,
  IonSkeletonText, IonList, IonItem, IonProgressBar,
  IonRefresher, IonRefresherContent,
  ToastController,
} from '@ionic/angular/standalone';
import { ApiService } from '../../core/services/api.service';
import { addIcons } from 'ionicons';
import {
  businessOutline, peopleOutline, pulseOutline, alertCircleOutline,
  trendingUpOutline, checkmarkCircleOutline, rocketOutline, statsChartOutline,
} from 'ionicons/icons';

addIcons({
  'business-outline': businessOutline,
  'people-outline': peopleOutline,
  'pulse-outline': pulseOutline,
  'alert-circle-outline': alertCircleOutline,
  'trending-up-outline': trendingUpOutline,
  'checkmark-circle-outline': checkmarkCircleOutline,
  'rocket-outline': rocketOutline,
  'stats-chart-outline': statsChartOutline,
});

@Component({
  selector: 'app-super-admin-dashboard',
  standalone: true,
  imports: [
    CommonModule, RouterLink,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons,
    IonCard, IonCardHeader, IonCardTitle, IonCardContent,
    IonGrid, IonRow, IonCol, IonBadge, IonIcon, IonButton, IonChip, IonLabel,
    IonSkeletonText, IonList, IonItem, IonProgressBar,
    IonRefresher, IonRefresherContent,
  ],
  template: `
    <ion-header>
      <ion-toolbar color="dark">
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>Platform Overview</ion-title>
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
        <!-- Revenue KPIs -->
        <ion-card>
          <ion-card-header><ion-card-title>Revenue</ion-card-title></ion-card-header>
          <ion-card-content>
            <ion-grid class="no-pad">
              <ion-row>
                <ion-col size="6" sizeMd="3">
                  <div class="kpi-cell">
                    <div class="kpi-value success-text">\${{ data()!.metrics.mrr | number:'1.0-0' }}</div>
                    <div class="kpi-label">MRR</div>
                  </div>
                </ion-col>
                <ion-col size="6" sizeMd="3">
                  <div class="kpi-cell">
                    <div class="kpi-value">\${{ data()!.metrics.arr | number:'1.0-0' }}</div>
                    <div class="kpi-label">ARR</div>
                  </div>
                </ion-col>
                <ion-col size="6" sizeMd="3">
                  <div class="kpi-cell">
                    <div class="kpi-value">\${{ data()!.metrics.arpu | number:'1.2-2' }}</div>
                    <div class="kpi-label">ARPU</div>
                  </div>
                </ion-col>
                <ion-col size="6" sizeMd="3">
                  <div class="kpi-cell">
                    <div class="kpi-value">\${{ data()!.metrics.arppu | number:'1.2-2' }}</div>
                    <div class="kpi-label">ARPPU</div>
                  </div>
                </ion-col>
              </ion-row>
            </ion-grid>
          </ion-card-content>
        </ion-card>

        <!-- Users & Growth KPIs -->
        <ion-card>
          <ion-card-header><ion-card-title>Users & Growth</ion-card-title></ion-card-header>
          <ion-card-content>
            <ion-grid class="no-pad">
              <ion-row>
                <ion-col size="4" sizeMd="2">
                  <div class="kpi-cell">
                    <div class="kpi-value">{{ data()!.metrics.dau }}</div>
                    <div class="kpi-label">DAU</div>
                  </div>
                </ion-col>
                <ion-col size="4" sizeMd="2">
                  <div class="kpi-cell">
                    <div class="kpi-value">{{ data()!.metrics.wau }}</div>
                    <div class="kpi-label">WAU</div>
                  </div>
                </ion-col>
                <ion-col size="4" sizeMd="2">
                  <div class="kpi-cell">
                    <div class="kpi-value">{{ data()!.metrics.mau }}</div>
                    <div class="kpi-label">MAU</div>
                  </div>
                </ion-col>
                <ion-col size="4" sizeMd="2">
                  <div class="kpi-cell">
                    <div class="kpi-value">{{ data()!.metrics.total_users }}</div>
                    <div class="kpi-label">Total Users</div>
                  </div>
                </ion-col>
                <ion-col size="4" sizeMd="2">
                  <div class="kpi-cell">
                    <div class="kpi-value">{{ data()!.metrics.total_organizations }}</div>
                    <div class="kpi-label">Total Orgs</div>
                  </div>
                </ion-col>
                <ion-col size="4" sizeMd="2">
                  <div class="kpi-cell">
                    <div class="kpi-value">{{ data()!.metrics.recent_signups_30d }}</div>
                    <div class="kpi-label">New (30d)</div>
                  </div>
                </ion-col>
              </ion-row>
            </ion-grid>
          </ion-card-content>
        </ion-card>

        <!-- Conversion & Retention -->
        <ion-card>
          <ion-card-header><ion-card-title>Conversion & Retention</ion-card-title></ion-card-header>
          <ion-card-content>
            <ion-grid class="no-pad">
              <ion-row>
                <ion-col size="6" sizeMd="3">
                  <div class="kpi-cell">
                    <div class="kpi-value success-text">{{ data()!.metrics.conversion_rate }}%</div>
                    <div class="kpi-label">Conversion Rate</div>
                  </div>
                </ion-col>
                <ion-col size="6" sizeMd="3">
                  <div class="kpi-cell">
                    <div class="kpi-value">{{ data()!.metrics.paid_organizations }}</div>
                    <div class="kpi-label">Paid Orgs</div>
                  </div>
                </ion-col>
                <ion-col size="6" sizeMd="3">
                  <div class="kpi-cell">
                    <div class="kpi-value" [class.danger-text]="data()!.metrics.churn_risk > 0">{{ data()!.metrics.churn_risk }}</div>
                    <div class="kpi-label">Churn Risk</div>
                  </div>
                </ion-col>
                <ion-col size="6" sizeMd="3">
                  <div class="kpi-cell">
                    <div class="kpi-value" [class.danger-text]="data()!.metrics.active_incidents > 0">{{ data()!.metrics.active_incidents }}</div>
                    <div class="kpi-label">Active Incidents</div>
                  </div>
                </ion-col>
              </ion-row>
            </ion-grid>
          </ion-card-content>
        </ion-card>

        <!-- Platform Activity -->
        <ion-card>
          <ion-card-header><ion-card-title>Platform Activity</ion-card-title></ion-card-header>
          <ion-card-content>
            <ion-grid class="no-pad">
              <ion-row>
                <ion-col size="6" sizeMd="3">
                  <div class="kpi-cell">
                    <div class="kpi-value">{{ data()!.metrics.total_monitors }}</div>
                    <div class="kpi-label">Total Monitors</div>
                  </div>
                </ion-col>
                <ion-col size="6" sizeMd="3">
                  <div class="kpi-cell">
                    <div class="kpi-value success-text">{{ data()!.metrics.active_monitors }}</div>
                    <div class="kpi-label">Active Monitors</div>
                  </div>
                </ion-col>
                <ion-col size="6" sizeMd="3">
                  <div class="kpi-cell">
                    <div class="kpi-value">{{ data()!.metrics.checks_last_24h | number }}</div>
                    <div class="kpi-label">Checks (24h)</div>
                  </div>
                </ion-col>
                <ion-col size="6" sizeMd="3">
                  <div class="kpi-cell">
                    @if (data()!.metrics.mau > 0) {
                      <div class="kpi-value">{{ (data()!.metrics.dau / data()!.metrics.mau * 100) | number:'1.0-0' }}%</div>
                    } @else {
                      <div class="kpi-value">-</div>
                    }
                    <div class="kpi-label">DAU/MAU Ratio</div>
                  </div>
                </ion-col>
              </ion-row>
            </ion-grid>
          </ion-card-content>
        </ion-card>

        <!-- Plan Distribution + Monitor Health -->
        <ion-grid>
          <ion-row>
            <ion-col size="12" sizeMd="6">
              <ion-card>
                <ion-card-header>
                  <ion-card-title>Plan Distribution</ion-card-title>
                </ion-card-header>
                <ion-card-content>
                  @if (data()!.plan_distribution && data()!.plan_distribution.length > 0) {
                    @for (plan of data()!.plan_distribution; track plan.plan) {
                      <div class="dist-row">
                        <div class="dist-label">
                          <ion-badge [color]="getPlanColor(plan.plan)">{{ plan.plan }}</ion-badge>
                        </div>
                        <div class="dist-bar-wrap">
                          <div class="dist-bar" [style.width.%]="getPlanPercent(plan.count)" [style.background]="getPlanBarColor(plan.plan)"></div>
                        </div>
                        <div class="dist-count">{{ plan.count }}</div>
                      </div>
                    }
                  } @else {
                    <p class="empty-text">No plan data available</p>
                  }
                </ion-card-content>
              </ion-card>
            </ion-col>
            <ion-col size="12" sizeMd="6">
              <ion-card>
                <ion-card-header>
                  <ion-card-title>Monitor Health</ion-card-title>
                </ion-card-header>
                <ion-card-content>
                  @if (data()!.monitors_by_status) {
                    <div class="health-grid">
                      <div class="health-item">
                        <div class="health-value" style="color: var(--ion-color-success)">{{ data()!.monitors_by_status.up }}</div>
                        <div class="health-label">Up</div>
                      </div>
                      <div class="health-item">
                        <div class="health-value" style="color: var(--ion-color-danger)">{{ data()!.monitors_by_status.down }}</div>
                        <div class="health-label">Down</div>
                      </div>
                      <div class="health-item">
                        <div class="health-value" style="color: var(--ion-color-warning)">{{ data()!.monitors_by_status.degraded }}</div>
                        <div class="health-label">Degraded</div>
                      </div>
                      <div class="health-item">
                        <div class="health-value" style="color: var(--ion-color-medium)">{{ data()!.monitors_by_status.unknown }}</div>
                        <div class="health-label">Unknown</div>
                      </div>
                    </div>
                    @if (data()!.metrics.active_monitors > 0) {
                      <div class="health-bar">
                        <div class="health-seg" style="background: var(--ion-color-success)" [style.flex]="data()!.monitors_by_status.up"></div>
                        <div class="health-seg" style="background: var(--ion-color-danger)" [style.flex]="data()!.monitors_by_status.down"></div>
                        <div class="health-seg" style="background: var(--ion-color-warning)" [style.flex]="data()!.monitors_by_status.degraded"></div>
                        <div class="health-seg" style="background: var(--ion-color-medium)" [style.flex]="data()!.monitors_by_status.unknown"></div>
                      </div>
                    }
                  }
                </ion-card-content>
              </ion-card>
            </ion-col>
          </ion-row>
        </ion-grid>

        <!-- Quick Actions -->
        <ion-card>
          <ion-card-header>
            <ion-card-title>Quick Actions</ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <div class="actions-grid">
              <ion-button fill="outline" size="small" routerLink="/super-admin/organizations">Organizations</ion-button>
              <ion-button fill="outline" size="small" routerLink="/super-admin/users">Users</ion-button>
              <ion-button fill="outline" size="small" routerLink="/super-admin/plans">Plans</ion-button>
              <ion-button fill="outline" size="small" routerLink="/super-admin/revenue">Revenue</ion-button>
              <ion-button fill="outline" size="small" routerLink="/super-admin/health">Health</ion-button>
            </div>
          </ion-card-content>
        </ion-card>
      }
    </ion-content>
  `,
  styles: [`
    .no-pad { --ion-grid-padding: 0; }
    .kpi-cell { text-align: center; padding: 12px 4px; }
    .kpi-value { font-family: 'DM Sans', sans-serif; font-size: 1.5rem; font-weight: 700; margin: 2px 0; }
    .kpi-label { font-size: 0.65rem; color: var(--ion-color-medium); text-transform: uppercase; letter-spacing: 0.05em; }
    .success-text { color: var(--ion-color-success); }
    .danger-text { color: var(--ion-color-danger); }
    .kpi-icon { font-size: 1.5rem; margin-bottom: 4px; }

    .dist-row { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
    .dist-label { width: 90px; flex-shrink: 0; }
    .dist-bar-wrap { flex: 1; height: 12px; background: var(--ion-color-light); border-radius: 6px; overflow: hidden; }
    .dist-bar { height: 100%; border-radius: 6px; min-width: 4px; transition: width 0.3s; }
    .dist-count { width: 40px; text-align: right; font-weight: 600; font-size: 0.9rem; flex-shrink: 0; }

    .health-grid { display: flex; justify-content: space-around; text-align: center; margin-bottom: 16px; }
    .health-value { font-family: 'DM Sans', sans-serif; font-size: 1.5rem; font-weight: 700; }
    .health-label { font-size: 0.7rem; text-transform: uppercase; color: var(--ion-color-medium); }
    .health-bar { display: flex; height: 10px; border-radius: 5px; overflow: hidden; gap: 2px; }
    .health-seg { min-width: 2px; border-radius: 5px; }

    .actions-grid { display: flex; flex-wrap: wrap; gap: 8px; }
    .empty-text { text-align: center; color: var(--ion-color-medium); padding: 1rem; }
  `],
})
export class SuperAdminDashboardComponent implements OnInit {
  data = signal<any>(null);
  loading = signal(true);

  constructor(private api: ApiService, private toastCtrl: ToastController) {}

  ngOnInit(): void { this.load(); }

  load(): void {
    this.loading.set(true);
    this.api.get<any>('/super-admin/dashboard').subscribe({
      next: (d) => { this.data.set(d); this.loading.set(false); },
      error: async (err: any) => {
        this.loading.set(false);
        const toast = await this.toastCtrl.create({ message: err?.message || 'Failed to load dashboard', color: 'danger', duration: 4000, position: 'bottom' });
        await toast.present();
      },
    });
  }

  onRefresh(event: any): void {
    this.api.get<any>('/super-admin/dashboard').subscribe({
      next: (d) => { this.data.set(d); event.target.complete(); },
      error: () => event.target.complete(),
    });
  }

  getPlanColor(plan: string): string {
    switch (plan) {
      case 'free': return 'medium';
      case 'pro': return 'primary';
      case 'business': return 'success';
      case 'enterprise': return 'warning';
      default: return 'tertiary';
    }
  }

  getPlanBarColor(plan: string): string {
    switch (plan) {
      case 'free': return 'var(--ion-color-medium)';
      case 'pro': return 'var(--ion-color-primary)';
      case 'business': return 'var(--ion-color-success)';
      case 'enterprise': return 'var(--ion-color-warning)';
      default: return 'var(--ion-color-tertiary)';
    }
  }

  getPlanPercent(count: number): number {
    const total = this.data()?.plan_distribution?.reduce((sum: number, p: any) => sum + p.count, 0) || 1;
    return Math.max((count / total) * 100, 5);
  }
}
