import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { ViewWillEnter } from '@ionic/angular';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
  IonCard, IonCardHeader, IonCardTitle, IonCardContent,
  IonGrid, IonRow, IonCol, IonBadge, IonIcon, IonChip, IonLabel,
  IonSegment, IonSegmentButton, IonSkeletonText,
  IonRefresher, IonRefresherContent, IonList, IonItem, IonNote,
  ToastController,
} from '@ionic/angular/standalone';
import { ApiService } from '../../core/services/api.service';
import { addIcons } from 'ionicons';
import { printOutline, createOutline, downloadOutline } from 'ionicons/icons';

addIcons({
  'print-outline': printOutline,
  'create-outline': createOutline,
  'download-outline': downloadOutline,
});

@Component({
  selector: 'app-sla-detail',
  standalone: true,
  imports: [
    CommonModule, FormsModule, RouterLink,
    IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
    IonCard, IonCardHeader, IonCardTitle, IonCardContent,
    IonGrid, IonRow, IonCol, IonBadge, IonIcon, IonChip, IonLabel,
    IonSegment, IonSegmentButton, IonSkeletonText,
    IonRefresher, IonRefresherContent, IonList, IonItem, IonNote,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button defaultHref="/sla"></ion-back-button>
        </ion-buttons>
        <ion-title>{{ sla()?.name ?? 'SLA Report' }}</ion-title>
        <ion-buttons slot="end">
          @if (sla()) {
            <ion-button (click)="onPrint()">
              <ion-icon name="print-outline" slot="icon-only"></ion-icon>
            </ion-button>
            <ion-button [routerLink]="['/sla', sla()!.id, 'edit']">
              <ion-icon name="create-outline" slot="icon-only"></ion-icon>
            </ion-button>
          }
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding" id="sla-report-content">
      <ion-refresher slot="fixed" (ionRefresh)="onRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

      @if (loading()) {
        <ion-card><ion-card-content>
          <ion-skeleton-text [animated]="true" style="width: 60%; height: 2rem"></ion-skeleton-text>
          <ion-skeleton-text [animated]="true" style="width: 40%; height: 1rem; margin-top: 8px"></ion-skeleton-text>
        </ion-card-content></ion-card>
      } @else if (sla()) {
        <!-- Period Selector -->
        <ion-segment [(ngModel)]="period" (ionChange)="loadReport()">
          <ion-segment-button value="this_month"><ion-label>This Month</ion-label></ion-segment-button>
          <ion-segment-button value="last_month"><ion-label>Last Month</ion-label></ion-segment-button>
          <ion-segment-button value="last_week"><ion-label>Last Week</ion-label></ion-segment-button>
          <ion-segment-button value="last_90d"><ion-label>90 Days</ion-label></ion-segment-button>
        </ion-segment>

        <!-- SLA Summary Card -->
        <ion-card style="margin-top: 16px">
          <ion-card-content>
            <div class="sla-hero">
              <div class="hero-stat">
                <div class="hero-value" [class.success-text]="getStatus() === 'compliant'" [class.warning-text]="getStatus() === 'at_risk'" [class.danger-text]="getStatus() === 'breached'">
                  {{ report()?.current_uptime ?? 0 | number:'1.3-3' }}%
                </div>
                <div class="hero-label">Actual Uptime</div>
              </div>
              <div class="hero-stat">
                <div class="hero-value">{{ sla()!.target_uptime | number:'1.2-2' }}%</div>
                <div class="hero-label">Target</div>
              </div>
              <div class="hero-stat">
                <ion-badge [color]="getStatusColor()" style="font-size: 1rem; padding: 8px 16px">
                  {{ getStatus() | uppercase }}
                </ion-badge>
                <div class="hero-label" style="margin-top: 8px">Status</div>
              </div>
            </div>
          </ion-card-content>
        </ion-card>

        <!-- Details -->
        <ion-grid>
          <ion-row>
            <ion-col size="6" sizeMd="3">
              <ion-card class="stat-card"><ion-card-content>
                <div class="stat-value">{{ report()?.total_checks ?? 0 | number }}</div>
                <div class="stat-label">Total Checks</div>
              </ion-card-content></ion-card>
            </ion-col>
            <ion-col size="6" sizeMd="3">
              <ion-card class="stat-card"><ion-card-content>
                <div class="stat-value success-text">{{ report()?.successful_checks ?? 0 | number }}</div>
                <div class="stat-label">Successful</div>
              </ion-card-content></ion-card>
            </ion-col>
            <ion-col size="6" sizeMd="3">
              <ion-card class="stat-card"><ion-card-content>
                <div class="stat-value danger-text">{{ report()?.failed_checks ?? 0 | number }}</div>
                <div class="stat-label">Failed</div>
              </ion-card-content></ion-card>
            </ion-col>
            <ion-col size="6" sizeMd="3">
              <ion-card class="stat-card"><ion-card-content>
                <div class="stat-value">{{ report()?.total_downtime_minutes ?? 0 | number:'1.0-0' }}m</div>
                <div class="stat-label">Downtime</div>
              </ion-card-content></ion-card>
            </ion-col>
          </ion-row>
        </ion-grid>

        <!-- Downtime Budget -->
        @if (report()?.downtime_budget_minutes) {
          <ion-card>
            <ion-card-header><ion-card-title>Downtime Budget</ion-card-title></ion-card-header>
            <ion-card-content>
              <div class="budget-bar-container">
                <div class="budget-bar-used" [style.width.%]="getBudgetUsed()" [class.bar-danger]="getBudgetUsed() > 80"></div>
              </div>
              <p style="text-align: center; margin-top: 8px; font-size: 0.85rem; color: var(--ion-color-medium)">
                {{ report()?.total_downtime_minutes | number:'1.0-0' }} of {{ report()?.downtime_budget_minutes | number:'1.0-0' }} minutes used
                ({{ getBudgetUsed() | number:'1.0-0' }}%)
              </p>
            </ion-card-content>
          </ion-card>
        }

        <!-- SLA Info -->
        <ion-card>
          <ion-card-header><ion-card-title>SLA Definition</ion-card-title></ion-card-header>
          <ion-card-content>
            <ion-list lines="none">
              <ion-item><ion-label>Name</ion-label><ion-note slot="end">{{ sla()!.name }}</ion-note></ion-item>
              <ion-item><ion-label>Target Uptime</ion-label><ion-note slot="end">{{ sla()!.target_uptime }}%</ion-note></ion-item>
              <ion-item><ion-label>Period</ion-label><ion-note slot="end">{{ sla()!.measurement_period | titlecase }}</ion-note></ion-item>
              <ion-item><ion-label>Monitor</ion-label><ion-note slot="end">{{ report()?.monitor_name ?? 'N/A' }}</ion-note></ion-item>
            </ion-list>
          </ion-card-content>
        </ion-card>

        <!-- Period Info -->
        @if (report()?.period) {
          <ion-card>
            <ion-card-header><ion-card-title>Reporting Period</ion-card-title></ion-card-header>
            <ion-card-content>
              <p>{{ report()!.period.start | date:'mediumDate' }} — {{ report()!.period.end | date:'mediumDate' }}</p>
            </ion-card-content>
          </ion-card>
        }
      }
    </ion-content>
  `,
  styles: [`
    .sla-hero { display: flex; justify-content: space-around; text-align: center; padding: 16px 0; }
    .hero-value { font-family: 'DM Sans', sans-serif; font-size: 2rem; font-weight: 700; }
    .hero-label { font-size: 0.75rem; color: var(--ion-color-medium); text-transform: uppercase; margin-top: 4px; }
    .success-text { color: var(--ion-color-success); }
    .warning-text { color: var(--ion-color-warning); }
    .danger-text { color: var(--ion-color-danger); }
    .stat-card ion-card-content { text-align: center; padding: 12px 8px; }
    .stat-value { font-family: 'DM Sans', sans-serif; font-size: 1.4rem; font-weight: 700; }
    .stat-label { font-size: 0.7rem; color: var(--ion-color-medium); text-transform: uppercase; margin-top: 4px; }
    .budget-bar-container { height: 12px; background: var(--ion-color-light); border-radius: 6px; overflow: hidden; }
    .budget-bar-used { height: 100%; background: var(--ion-color-success); border-radius: 6px; transition: width 0.3s; }
    .budget-bar-used.bar-danger { background: var(--ion-color-danger); }
  `],
})
export class SlaDetailComponent implements OnInit, ViewWillEnter {
  sla = signal<any>(null);
  report = signal<any>(null);
  loading = signal(true);
  period = 'this_month';

  private slaId = 0;

  constructor(
    private route: ActivatedRoute,
    private api: ApiService,
    private toastCtrl: ToastController,
  ) {}

  ngOnInit(): void {
    this.slaId = Number(this.route.snapshot.paramMap.get('id'));
  }

  ionViewWillEnter(): void {
    this.loadSla();
  }

  loadSla(): void {
    this.loading.set(true);
    this.api.get<any>(`/sla/${this.slaId}`).subscribe({
      next: (data) => {
        this.sla.set(data.sla || data);
        this.loadReport();
      },
      error: () => { this.loading.set(false); },
    });
  }

  loadReport(): void {
    const params = this.getPeriodParams();
    this.api.get<any>(`/sla/${this.slaId}/report`, params).subscribe({
      next: (data) => {
        this.report.set(data.report || data);
        this.loading.set(false);
      },
      error: () => {
        this.report.set(null);
        this.loading.set(false);
      },
    });
  }

  onRefresh(event: any): void {
    this.api.get<any>(`/sla/${this.slaId}`).subscribe({
      next: (data) => {
        this.sla.set(data.sla || data);
        this.loadReport();
        event.target.complete();
      },
      error: () => event.target.complete(),
    });
  }

  onPrint(): void {
    window.print();
  }

  getStatus(): string {
    const actual = this.report()?.current_uptime ?? 0;
    const target = this.sla()?.target_uptime ?? 99.9;
    if (actual >= target) return 'compliant';
    if (actual >= target - 0.5) return 'at_risk';
    return 'breached';
  }

  getStatusColor(): string {
    const s = this.getStatus();
    if (s === 'compliant') return 'success';
    if (s === 'at_risk') return 'warning';
    return 'danger';
  }

  getBudgetUsed(): number {
    const used = this.report()?.total_downtime_minutes ?? 0;
    const budget = this.report()?.downtime_budget_minutes ?? 1;
    return Math.min((used / budget) * 100, 100);
  }

  private getPeriodParams(): any {
    const now = new Date();
    switch (this.period) {
      case 'this_month':
        return { from: `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-01` };
      case 'last_month': {
        const lm = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        const lmEnd = new Date(now.getFullYear(), now.getMonth(), 0);
        return {
          from: `${lm.getFullYear()}-${String(lm.getMonth() + 1).padStart(2, '0')}-01`,
          to: `${lmEnd.getFullYear()}-${String(lmEnd.getMonth() + 1).padStart(2, '0')}-${String(lmEnd.getDate()).padStart(2, '0')}`,
        };
      }
      case 'last_week': {
        const lw = new Date(now);
        lw.setDate(lw.getDate() - 7);
        return { from: lw.toISOString().split('T')[0] };
      }
      case 'last_90d': {
        const d90 = new Date(now);
        d90.setDate(d90.getDate() - 90);
        return { from: d90.toISOString().split('T')[0] };
      }
      default:
        return {};
    }
  }
}
