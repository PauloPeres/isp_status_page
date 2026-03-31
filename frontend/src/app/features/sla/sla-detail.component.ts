import { Component, OnInit, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { ViewWillEnter } from '@ionic/angular';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
  IonCard, IonCardHeader, IonCardTitle, IonCardContent, IonCardSubtitle,
  IonGrid, IonRow, IonCol, IonBadge, IonIcon, IonChip, IonLabel,
  IonSegment, IonSegmentButton, IonSkeletonText,
  IonRefresher, IonRefresherContent, IonList, IonItem, IonNote,
  IonProgressBar,
  ToastController,
} from '@ionic/angular/standalone';
import { ApiService } from '../../core/services/api.service';
import { addIcons } from 'ionicons';
import { printOutline, createOutline, downloadOutline, shieldCheckmarkOutline, warningOutline, closeCircleOutline } from 'ionicons/icons';

addIcons({
  'print-outline': printOutline,
  'create-outline': createOutline,
  'download-outline': downloadOutline,
  'shield-checkmark-outline': shieldCheckmarkOutline,
  'warning-outline': warningOutline,
  'close-circle-outline': closeCircleOutline,
});

@Component({
  selector: 'app-sla-detail',
  standalone: true,
  imports: [
    CommonModule, FormsModule, RouterLink,
    IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
    IonCard, IonCardHeader, IonCardTitle, IonCardContent, IonCardSubtitle,
    IonGrid, IonRow, IonCol, IonBadge, IonIcon, IonChip, IonLabel,
    IonSegment, IonSegmentButton, IonSkeletonText,
    IonRefresher, IonRefresherContent, IonList, IonItem, IonNote,
    IonProgressBar,
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
            <ion-button (click)="onPrint()" class="no-print">
              <ion-icon name="print-outline" slot="icon-only"></ion-icon>
            </ion-button>
            <ion-button [routerLink]="['/sla', sla()!.id, 'edit']" class="no-print">
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
          <ion-skeleton-text [animated]="true" style="width: 80%; height: 1rem; margin-top: 8px"></ion-skeleton-text>
        </ion-card-content></ion-card>
      } @else if (sla()) {

        <!-- Period Selector -->
        <div class="period-selector no-print">
          <ion-segment [(ngModel)]="period" (ionChange)="loadReport()">
            <ion-segment-button value="this_month"><ion-label>This Month</ion-label></ion-segment-button>
            <ion-segment-button value="last_month"><ion-label>Last Month</ion-label></ion-segment-button>
            <ion-segment-button value="last_week"><ion-label>Last Week</ion-label></ion-segment-button>
            <ion-segment-button value="last_90d"><ion-label>90 Days</ion-label></ion-segment-button>
          </ion-segment>
          <div class="period-dates">{{ periodLabel() }}</div>
        </div>

        <!-- Print Header (visible only in print) -->
        <div class="print-header">
          <h1>{{ sla()!.name }} &mdash; SLA Report</h1>
          <p>{{ periodLabel() }} &bull; Generated {{ today | date:'mediumDate' }}</p>
        </div>

        <!-- ===== 1. EXECUTIVE SUMMARY (HERO) ===== -->
        <ion-card class="hero-card">
          <ion-card-content>
            <div class="hero-grid">
              <!-- Status Badge -->
              <div class="hero-block hero-status-block">
                <ion-badge [color]="getStatusColor()" class="status-badge">
                  @if (getStatus() === 'compliant') {
                    <ion-icon name="shield-checkmark-outline"></ion-icon>
                  } @else if (getStatus() === 'at_risk') {
                    <ion-icon name="warning-outline"></ion-icon>
                  } @else {
                    <ion-icon name="close-circle-outline"></ion-icon>
                  }
                  {{ getStatus() | uppercase }}
                </ion-badge>
                <div class="hero-label">SLA Status</div>
              </div>

              <!-- Actual Uptime -->
              <div class="hero-block">
                <div class="hero-value" [class]="getUptimeClass()">
                  {{ report()?.current_uptime ?? 0 | number:'1.3-3' }}%
                </div>
                <div class="hero-label">Actual Uptime</div>
              </div>

              <!-- Target Uptime -->
              <div class="hero-block">
                <div class="hero-value">{{ sla()!.target_uptime | number:'1.2-2' }}%</div>
                <div class="hero-label">Target Uptime</div>
              </div>

              <!-- Total Downtime -->
              <div class="hero-block">
                <div class="hero-value">{{ formatDuration(report()?.total_downtime_minutes ?? 0) }}</div>
                <div class="hero-label">Total Downtime</div>
              </div>

              <!-- Incidents -->
              <div class="hero-block">
                <div class="hero-value">{{ getIncidentCount() }}</div>
                <div class="hero-label">Incidents</div>
              </div>

              <!-- Longest Incident -->
              <div class="hero-block">
                <div class="hero-value">{{ formatDuration(getLongestIncident()) }}</div>
                <div class="hero-label">Longest Incident</div>
              </div>
            </div>

            <!-- Downtime Budget Bar -->
            <div class="budget-section">
              <div class="budget-header">
                <span class="budget-title">Downtime Budget</span>
                <span class="budget-numbers">
                  {{ formatDuration(report()?.total_downtime_minutes ?? 0) }}
                  used of
                  {{ formatDuration(getAllowedDowntime()) }}
                  allowed
                </span>
              </div>
              <div class="budget-bar-container">
                <div class="budget-bar-used"
                     [style.width.%]="getBudgetUsedPct()"
                     [class.bar-warning]="getBudgetUsedPct() > 50 && getBudgetUsedPct() <= 80"
                     [class.bar-danger]="getBudgetUsedPct() > 80">
                </div>
              </div>
              <div class="budget-footer">
                <span>{{ getBudgetUsedPct() | number:'1.1-1' }}% consumed</span>
                <span>{{ formatDuration(getRemainingBudget()) }} remaining</span>
              </div>
            </div>
          </ion-card-content>
        </ion-card>

        <!-- ===== 2. KEY METRICS ROW ===== -->
        <ion-grid class="metrics-grid">
          <ion-row>
            <ion-col size="6" sizeMd="3">
              <ion-card class="metric-card"><ion-card-content>
                <div class="stat-value">{{ formatDuration(getMTBF()) }}</div>
                <div class="stat-label">MTBF</div>
                <div class="stat-sublabel">Mean Time Between Failures</div>
              </ion-card-content></ion-card>
            </ion-col>
            <ion-col size="6" sizeMd="3">
              <ion-card class="metric-card"><ion-card-content>
                <div class="stat-value">{{ formatDuration(getMTTR()) }}</div>
                <div class="stat-label">MTTR</div>
                <div class="stat-sublabel">Mean Time to Repair</div>
              </ion-card-content></ion-card>
            </ion-col>
            <ion-col size="6" sizeMd="3">
              <ion-card class="metric-card"><ion-card-content>
                <div class="stat-value">{{ getAvailabilityExclMaint() | number:'1.3-3' }}%</div>
                <div class="stat-label">Availability</div>
                <div class="stat-sublabel">Excluding Maintenance</div>
              </ion-card-content></ion-card>
            </ion-col>
            <ion-col size="6" sizeMd="3">
              <ion-card class="metric-card"><ion-card-content>
                <div class="stat-value">{{ report()?.total_checks ?? 0 | number }}</div>
                <div class="stat-label">Total Checks</div>
                <div class="stat-sublabel">
                  <span class="mini-success">{{ report()?.successful_checks ?? 0 | number }}</span>
                  /
                  <span class="mini-danger">{{ report()?.failed_checks ?? 0 | number }}</span>
                </div>
              </ion-card-content></ion-card>
            </ion-col>
          </ion-row>
        </ion-grid>

        <!-- ===== 3. DAILY AVAILABILITY CALENDAR ===== -->
        <ion-card>
          <ion-card-header>
            <ion-card-title>Daily Availability</ion-card-title>
            <ion-card-subtitle>Day-by-day breakdown for the selected period</ion-card-subtitle>
          </ion-card-header>
          <ion-card-content>
            @if (getDailyBreakdown().length > 0) {
              <div class="daily-list">
                @for (day of getDailyBreakdown(); track day.date) {
                  <div class="daily-row" [class.daily-perfect]="day.uptime >= 100"
                       [class.daily-amber]="day.uptime < (sla()?.target_uptime ?? 99.9) && day.uptime >= 98"
                       [class.daily-red]="day.uptime < 98">
                    <div class="daily-dot"
                         [class.dot-green]="day.uptime >= 100"
                         [class.dot-amber]="day.uptime < (sla()?.target_uptime ?? 99.9) && day.uptime >= 98"
                         [class.dot-red]="day.uptime < 98"></div>
                    <div class="daily-date">{{ day.date | date:'EEE, MMM d' }}</div>
                    <div class="daily-uptime" [class]="getDayUptimeClass(day.uptime)">
                      {{ day.uptime | number:'1.2-2' }}%
                    </div>
                    <div class="daily-downtime">
                      @if (day.downtime_minutes > 0) {
                        {{ formatDuration(day.downtime_minutes) }} down
                      } @else {
                        &mdash;
                      }
                    </div>
                    <div class="daily-incidents">
                      @if (day.incidents > 0) {
                        {{ day.incidents }} incident{{ day.incidents > 1 ? 's' : '' }}
                      }
                    </div>
                  </div>
                }
              </div>
            } @else {
              <div class="empty-state">
                <p>Daily breakdown not available for this period.</p>
              </div>
            }
          </ion-card-content>
        </ion-card>

        <!-- ===== 4. RESPONSE TIME STATS ===== -->
        <ion-card>
          <ion-card-header>
            <ion-card-title>Response Time</ion-card-title>
            <ion-card-subtitle>Performance statistics for the selected period</ion-card-subtitle>
          </ion-card-header>
          <ion-card-content>
            <div class="response-stats">
              <div class="response-stat">
                <div class="stat-value" [class]="getResponseClass(getResponseAvg())">
                  {{ getResponseAvg() > 0 ? (getResponseAvg() | number:'1.0-0') + 'ms' : 'N/A' }}
                </div>
                <div class="stat-label">Average</div>
              </div>
              <div class="response-stat">
                <div class="stat-value" [class]="getResponseClass(getResponseP95())">
                  {{ getResponseP95() > 0 ? (getResponseP95() | number:'1.0-0') + 'ms' : 'N/A' }}
                </div>
                <div class="stat-label">P95</div>
              </div>
              <div class="response-stat">
                <div class="stat-value" [class]="getResponseClass(getResponseMax())">
                  {{ getResponseMax() > 0 ? (getResponseMax() | number:'1.0-0') + 'ms' : 'N/A' }}
                </div>
                <div class="stat-label">Max</div>
              </div>
            </div>
          </ion-card-content>
        </ion-card>

        <!-- ===== SLA DEFINITION ===== -->
        <ion-card>
          <ion-card-header>
            <ion-card-title>SLA Definition</ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <ion-list lines="none">
              <ion-item><ion-label>Name</ion-label><ion-note slot="end">{{ sla()!.name }}</ion-note></ion-item>
              <ion-item><ion-label>Target Uptime</ion-label><ion-note slot="end">{{ sla()!.target_uptime }}%</ion-note></ion-item>
              <ion-item><ion-label>Period</ion-label><ion-note slot="end">{{ sla()!.measurement_period | titlecase }}</ion-note></ion-item>
              <ion-item><ion-label>Monitor</ion-label><ion-note slot="end">{{ report()?.monitor_name ?? 'N/A' }}</ion-note></ion-item>
            </ion-list>
          </ion-card-content>
        </ion-card>
      }
    </ion-content>
  `,
  styles: [`
    /* ===== PERIOD SELECTOR ===== */
    .period-selector { margin-bottom: 16px; }
    .period-dates {
      text-align: center;
      font-size: 0.8rem;
      color: var(--ion-color-medium);
      margin-top: 8px;
      font-weight: 500;
      letter-spacing: 0.02em;
    }

    /* ===== PRINT HEADER (hidden on screen) ===== */
    .print-header { display: none; }

    /* ===== HERO CARD ===== */
    .hero-card { margin-top: 0; }
    .hero-card ion-card-content { padding: 20px 16px; }

    .hero-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 16px 12px;
      text-align: center;
      padding-bottom: 20px;
      border-bottom: 1px solid var(--ion-color-light-shade);
    }

    .hero-block { display: flex; flex-direction: column; align-items: center; justify-content: center; }

    .hero-status-block { grid-column: 1 / -1; margin-bottom: 4px; }

    .status-badge {
      font-size: 1.1rem;
      padding: 10px 24px;
      border-radius: 8px;
      font-weight: 700;
      letter-spacing: 0.05em;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .status-badge ion-icon { font-size: 1.2rem; }

    .hero-value {
      font-family: 'DM Sans', monospace;
      font-size: 1.6rem;
      font-weight: 700;
      line-height: 1.2;
    }
    .hero-label {
      font-size: 0.7rem;
      color: var(--ion-color-medium);
      text-transform: uppercase;
      margin-top: 4px;
      letter-spacing: 0.04em;
      font-weight: 600;
    }

    .success-text { color: var(--ion-color-success); }
    .warning-text { color: var(--ion-color-warning-shade); }
    .danger-text { color: var(--ion-color-danger); }

    /* ===== BUDGET SECTION ===== */
    .budget-section { margin-top: 20px; }
    .budget-header {
      display: flex;
      justify-content: space-between;
      align-items: baseline;
      margin-bottom: 8px;
    }
    .budget-title {
      font-size: 0.8rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      color: var(--ion-color-dark);
    }
    .budget-numbers {
      font-size: 0.75rem;
      color: var(--ion-color-medium);
    }
    .budget-bar-container {
      height: 14px;
      background: var(--ion-color-light);
      border-radius: 7px;
      overflow: hidden;
    }
    .budget-bar-used {
      height: 100%;
      background: var(--ion-color-success);
      border-radius: 7px;
      transition: width 0.4s ease;
    }
    .budget-bar-used.bar-warning { background: var(--ion-color-warning); }
    .budget-bar-used.bar-danger { background: var(--ion-color-danger); }
    .budget-footer {
      display: flex;
      justify-content: space-between;
      font-size: 0.72rem;
      color: var(--ion-color-medium);
      margin-top: 6px;
    }

    /* ===== KEY METRICS ===== */
    .metrics-grid { --ion-grid-padding: 0; }
    .metric-card { margin: 4px 0; }
    .metric-card ion-card-content { text-align: center; padding: 14px 8px; }
    .stat-value {
      font-family: 'DM Sans', monospace;
      font-size: 1.3rem;
      font-weight: 700;
    }
    .stat-label {
      font-size: 0.75rem;
      color: var(--ion-color-dark);
      text-transform: uppercase;
      margin-top: 4px;
      font-weight: 700;
      letter-spacing: 0.03em;
    }
    .stat-sublabel {
      font-size: 0.65rem;
      color: var(--ion-color-medium);
      margin-top: 2px;
    }
    .mini-success { color: var(--ion-color-success); font-weight: 600; }
    .mini-danger { color: var(--ion-color-danger); font-weight: 600; }

    /* ===== DAILY AVAILABILITY ===== */
    .daily-list { display: flex; flex-direction: column; gap: 2px; }
    .daily-row {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 8px 12px;
      border-radius: 6px;
      font-size: 0.82rem;
      background: var(--ion-color-light);
    }
    .daily-row.daily-perfect { background: rgba(67, 160, 71, 0.06); }
    .daily-row.daily-amber { background: rgba(253, 216, 53, 0.12); }
    .daily-row.daily-red { background: rgba(229, 57, 53, 0.08); }

    .daily-dot {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      flex-shrink: 0;
      background: var(--ion-color-medium);
    }
    .daily-dot.dot-green { background: var(--ion-color-success); }
    .daily-dot.dot-amber { background: var(--ion-color-warning); }
    .daily-dot.dot-red { background: var(--ion-color-danger); }

    .daily-date { flex: 1; font-weight: 500; min-width: 100px; }
    .daily-uptime { font-weight: 700; min-width: 64px; text-align: right; font-family: 'DM Sans', monospace; }
    .daily-downtime { min-width: 80px; text-align: right; color: var(--ion-color-medium); font-size: 0.75rem; }
    .daily-incidents { min-width: 80px; text-align: right; color: var(--ion-color-medium); font-size: 0.75rem; }

    .empty-state {
      text-align: center;
      padding: 24px 16px;
      color: var(--ion-color-medium);
      font-size: 0.85rem;
    }

    /* ===== RESPONSE TIME ===== */
    .response-stats {
      display: flex;
      justify-content: space-around;
      text-align: center;
      padding: 8px 0;
    }
    .response-stat { flex: 1; }
    .response-green { color: var(--ion-color-success); }
    .response-amber { color: var(--ion-color-warning-shade); }
    .response-red { color: var(--ion-color-danger); }

    /* ===== PRINT STYLES ===== */
    @media print {
      ion-header, ion-refresher, ion-segment, .no-print, .period-selector { display: none !important; }
      ion-content { --overflow: visible; }
      ion-card { break-inside: avoid; box-shadow: none; border: 1px solid #ddd; margin: 8px 0; }
      .stat-value, .hero-value { color: #000 !important; }
      .print-header { display: block; text-align: center; margin-bottom: 16px; border-bottom: 2px solid #333; padding-bottom: 12px; }
      .print-header h1 { font-size: 1.4rem; margin: 0 0 4px; }
      .print-header p { font-size: 0.85rem; color: #666; margin: 0; }
      .hero-grid { border-bottom-color: #ddd; }
      .daily-row { background: #f9f9f9 !important; border: 1px solid #eee; }
      .daily-row.daily-red { background: #fff0f0 !important; }
      .budget-bar-container { border: 1px solid #ccc; }
      .status-badge { border: 2px solid currentColor; }
    }
  `],
})
export class SlaDetailComponent implements OnInit, ViewWillEnter {
  sla = signal<any>(null);
  report = signal<any>(null);
  loading = signal(true);
  period = 'this_month';
  today = new Date();

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

  // ===== STATUS HELPERS =====

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

  getUptimeClass(): string {
    const s = this.getStatus();
    if (s === 'compliant') return 'hero-value success-text';
    if (s === 'at_risk') return 'hero-value warning-text';
    return 'hero-value danger-text';
  }

  // ===== DURATION FORMATTING =====

  formatDuration(minutes: number): string {
    if (minutes == null || minutes <= 0) return '0m';
    const d = Math.floor(minutes / 1440);
    const h = Math.floor((minutes % 1440) / 60);
    const m = Math.round(minutes % 60);
    const parts: string[] = [];
    if (d > 0) parts.push(`${d}d`);
    if (h > 0) parts.push(`${h}h`);
    if (m > 0 || parts.length === 0) parts.push(`${m}m`);
    return parts.join(' ');
  }

  // ===== DOWNTIME BUDGET =====

  getAllowedDowntime(): number {
    // If API provides it, use it. Otherwise, calculate.
    if (this.report()?.downtime_budget_minutes) {
      return this.report().downtime_budget_minutes;
    }
    const periodMinutes = this.getTotalPeriodMinutes();
    const target = this.sla()?.target_uptime ?? 99.9;
    return periodMinutes * (1 - target / 100);
  }

  getBudgetUsedPct(): number {
    const used = this.report()?.total_downtime_minutes ?? 0;
    const allowed = this.getAllowedDowntime();
    if (allowed <= 0) return used > 0 ? 100 : 0;
    return Math.min((used / allowed) * 100, 100);
  }

  getRemainingBudget(): number {
    const allowed = this.getAllowedDowntime();
    const used = this.report()?.total_downtime_minutes ?? 0;
    return Math.max(allowed - used, 0);
  }

  // ===== KEY METRICS =====

  getIncidentCount(): number {
    return this.report()?.incident_count ?? this.report()?.failed_checks ?? 0;
  }

  getLongestIncident(): number {
    return this.report()?.longest_incident_minutes ?? 0;
  }

  getMTBF(): number {
    const outages = this.getIncidentCount();
    if (outages <= 0) return this.getTotalPeriodMinutes();
    const totalUptime = this.getTotalPeriodMinutes() - (this.report()?.total_downtime_minutes ?? 0);
    return totalUptime / outages;
  }

  getMTTR(): number {
    const outages = this.getIncidentCount();
    if (outages <= 0) return 0;
    return (this.report()?.total_downtime_minutes ?? 0) / outages;
  }

  getAvailabilityExclMaint(): number {
    const r = this.report();
    if (!r) return 0;
    // If the API provides maintenance_minutes, exclude them from total period
    const maintMinutes = r.maintenance_minutes ?? 0;
    const totalMinutes = this.getTotalPeriodMinutes() - maintMinutes;
    if (totalMinutes <= 0) return 100;
    const downtime = r.total_downtime_minutes ?? 0;
    return ((totalMinutes - downtime) / totalMinutes) * 100;
  }

  // ===== DAILY BREAKDOWN =====

  getDailyBreakdown(): any[] {
    return this.report()?.daily_breakdown ?? [];
  }

  getDayUptimeClass(uptime: number): string {
    if (uptime >= 100) return 'daily-uptime success-text';
    const target = this.sla()?.target_uptime ?? 99.9;
    if (uptime < 98) return 'daily-uptime danger-text';
    if (uptime < target) return 'daily-uptime warning-text';
    return 'daily-uptime success-text';
  }

  // ===== RESPONSE TIME =====

  getResponseAvg(): number {
    return this.report()?.avg_response_ms ?? this.report()?.avg_response_time ?? 0;
  }

  getResponseP95(): number {
    return this.report()?.p95_response_ms ?? 0;
  }

  getResponseMax(): number {
    return this.report()?.max_response_ms ?? 0;
  }

  getResponseClass(ms: number): string {
    if (ms <= 0) return 'stat-value';
    if (ms < 100) return 'stat-value response-green';
    if (ms <= 500) return 'stat-value response-amber';
    return 'stat-value response-red';
  }

  // ===== PERIOD =====

  periodLabel(): string {
    const { from, to } = this.getPeriodDates();
    const opts: Intl.DateTimeFormatOptions = { month: 'long', day: 'numeric', year: 'numeric' };
    return `${from.toLocaleDateString('en-US', opts)} \u2014 ${to.toLocaleDateString('en-US', opts)}`;
  }

  private getTotalPeriodMinutes(): number {
    // Use API period if available
    if (this.report()?.period?.start && this.report()?.period?.end) {
      const start = new Date(this.report().period.start);
      const end = new Date(this.report().period.end);
      return (end.getTime() - start.getTime()) / 60000;
    }
    const { from, to } = this.getPeriodDates();
    return (to.getTime() - from.getTime()) / 60000;
  }

  private getPeriodDates(): { from: Date; to: Date } {
    const now = new Date();
    switch (this.period) {
      case 'this_month': {
        const from = new Date(now.getFullYear(), now.getMonth(), 1);
        const to = new Date(now.getFullYear(), now.getMonth() + 1, 0, 23, 59, 59);
        return { from, to };
      }
      case 'last_month': {
        const from = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        const to = new Date(now.getFullYear(), now.getMonth(), 0, 23, 59, 59);
        return { from, to };
      }
      case 'last_week': {
        const to = new Date(now);
        const from = new Date(now);
        from.setDate(from.getDate() - 7);
        return { from, to };
      }
      case 'last_90d': {
        const to = new Date(now);
        const from = new Date(now);
        from.setDate(from.getDate() - 90);
        return { from, to };
      }
      default:
        return { from: now, to: now };
    }
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
