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
import { SlaService } from './sla.service';
import { addIcons } from 'ionicons';
import { printOutline, createOutline, downloadOutline, shieldCheckmarkOutline, warningOutline, closeCircleOutline, alertCircleOutline } from 'ionicons/icons';

addIcons({
  'print-outline': printOutline,
  'create-outline': createOutline,
  'download-outline': downloadOutline,
  'shield-checkmark-outline': shieldCheckmarkOutline,
  'warning-outline': warningOutline,
  'close-circle-outline': closeCircleOutline,
  'alert-circle-outline': alertCircleOutline,
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
    <ion-header class="no-print">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button defaultHref="/sla"></ion-back-button>
        </ion-buttons>
        <ion-title>{{ sla()?.name ?? 'SLA Report' }}</ion-title>
        <ion-buttons slot="end">
          @if (sla()) {
            <ion-button (click)="onDownloadPdf()">
              <ion-icon name="download-outline" slot="icon-only"></ion-icon>
            </ion-button>
            <ion-button [routerLink]="['/sla', sla()!.public_id, 'edit']">
              <ion-icon name="create-outline" slot="icon-only"></ion-icon>
            </ion-button>
          }
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding" id="sla-report-content">
      <ion-refresher slot="fixed" (ionRefresh)="onRefresh($event)" class="no-print">
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

        <!-- ===== PRINT HEADER (visible only in print) ===== -->
        <div class="print-only print-header">
          <div class="print-brand">KeepUp</div>
          <h1 class="print-title">{{ sla()!.name }} &mdash; SLA Compliance Report</h1>
          <p class="print-meta">Period: {{ periodLabel() }}</p>
          <p class="print-meta">Generated: {{ today | date:'longDate' }}</p>
        </div>

        <!-- ===== 1. EXECUTIVE SUMMARY (HERO) ===== -->
        <ion-card class="hero-card">
          <ion-card-content>
            <!-- Status Hero -->
            <div class="status-hero">
              <div class="status-icon-circle"
                   [class.circle-success]="getStatus() === 'compliant'"
                   [class.circle-warning]="getStatus() === 'at_risk'"
                   [class.circle-danger]="getStatus() === 'breached'">
                @if (getStatus() === 'compliant') {
                  <ion-icon name="shield-checkmark-outline"></ion-icon>
                } @else if (getStatus() === 'at_risk') {
                  <ion-icon name="warning-outline"></ion-icon>
                } @else {
                  <ion-icon name="alert-circle-outline"></ion-icon>
                }
              </div>
              <div class="status-hero-text">
                <div class="status-hero-label"
                     [class.text-success]="getStatus() === 'compliant'"
                     [class.text-warning]="getStatus() === 'at_risk'"
                     [class.text-danger]="getStatus() === 'breached'">
                  @if (getStatus() === 'compliant') { Compliant }
                  @else if (getStatus() === 'at_risk') { At Risk }
                  @else { Breached }
                </div>
                <div class="status-hero-desc">
                  @if (getStatus() === 'compliant') {
                    Your service met its uptime target this period
                  } @else if (getStatus() === 'at_risk') {
                    Your service is close to missing its uptime target
                  } @else {
                    Your service missed its uptime target this period
                  }
                </div>
              </div>
            </div>

            <div class="hero-grid">
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
                     [class.bar-green]="getBudgetUsedPct() <= 50"
                     [class.bar-gradient-mid]="getBudgetUsedPct() > 50 && getBudgetUsedPct() <= 80"
                     [class.bar-gradient-high]="getBudgetUsedPct() > 80">
                  @if (getBudgetUsedPct() >= 15) {
                    <span class="budget-bar-pct">{{ getBudgetUsedPct() | number:'1.0-0' }}%</span>
                  }
                </div>
              </div>
              <div class="budget-footer">
                <span>{{ getBudgetUsedPct() | number:'1.1-1' }}% consumed</span>
                <span class="budget-remaining">
                  {{ formatDuration(report()?.total_downtime_minutes ?? 0) }} min used of {{ formatDuration(getAllowedDowntime()) }} min budget
                </span>
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
        <ion-card class="daily-card">
          <ion-card-header>
            <ion-card-title>Daily Availability</ion-card-title>
            <ion-card-subtitle>Day-by-day breakdown for the selected period</ion-card-subtitle>
          </ion-card-header>
          <ion-card-content>
            @if (getDailyBreakdown().length > 0) {
              <!-- Mini heatmap -->
              <div class="heatmap-row">
                @for (day of getDailyBreakdown(); track day.date) {
                  <div class="heatmap-cell"
                       [class.heatmap-green]="day.uptime >= (sla()?.target_uptime ?? 99.9)"
                       [class.heatmap-amber]="day.uptime < (sla()?.target_uptime ?? 99.9) && day.uptime >= 98"
                       [class.heatmap-red]="day.uptime < 98"
                       [title]="(day.date | date:'MMM d') + ': ' + (day.uptime | number:'1.2-2') + '%'">
                  </div>
                }
              </div>

              <!-- Table header -->
              <div class="daily-header">
                <div class="daily-hdr-dot"></div>
                <div class="daily-hdr-date">Date</div>
                <div class="daily-hdr-uptime">Uptime</div>
                <div class="daily-hdr-downtime">Downtime</div>
                <div class="daily-hdr-incidents">Incidents</div>
              </div>

              <div class="daily-list">
                @for (day of getDailyBreakdown(); track day.date; let idx = $index) {
                  <div class="daily-row"
                       [class.daily-perfect]="day.uptime >= 100"
                       [class.daily-amber]="day.uptime < (sla()?.target_uptime ?? 99.9) && day.uptime >= 98"
                       [class.daily-red]="day.uptime < 98"
                       [class.daily-row-alt]="idx % 2 === 1">
                    <div class="daily-dot"
                         [class.dot-green]="day.uptime >= (sla()?.target_uptime ?? 99.9)"
                         [class.dot-amber]="day.uptime < (sla()?.target_uptime ?? 99.9) && day.uptime >= 98"
                         [class.dot-red]="day.uptime < 98"></div>
                    <div class="daily-date">{{ day.date | date:'EEE, MMM d' }}</div>
                    <div class="daily-uptime" [class]="getDayUptimeClass(day.uptime)">
                      {{ day.uptime | number:'1.2-2' }}%
                    </div>
                    <div class="daily-downtime">
                      @if (day.downtime_minutes > 0) {
                        {{ formatDuration(day.downtime_minutes) }}
                      } @else {
                        &mdash;
                      }
                    </div>
                    <div class="daily-incidents">
                      @if (day.incidents > 0) {
                        {{ day.incidents }}
                      } @else {
                        &mdash;
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

        <!-- ===== INCIDENTS IN PERIOD ===== -->
        <ion-card>
          <ion-card-header>
            <ion-card-title>Incidents in Period</ion-card-title>
            <ion-card-subtitle>Incidents that occurred during the selected period</ion-card-subtitle>
          </ion-card-header>
          <ion-card-content>
            @if (getIncidents().length === 0) {
              <p style="color: var(--ion-color-success); text-align: center; padding: 16px;">No incidents during this period</p>
            } @else {
              <div class="incidents-table">
                <div class="incidents-header">
                  <div class="inc-col-title">Title</div>
                  <div class="inc-col-severity">Severity</div>
                  <div class="inc-col-status">Status</div>
                  <div class="inc-col-started">Started</div>
                  <div class="inc-col-resolved">Resolved</div>
                  <div class="inc-col-duration">Duration</div>
                </div>
                @for (inc of getIncidents(); track inc.id; let idx = $index) {
                  <div class="incidents-row" [class.incidents-row-alt]="idx % 2 === 1">
                    <div class="inc-col-title">{{ inc.title }}</div>
                    <div class="inc-col-severity">
                      <ion-badge [color]="getSeverityColor(inc.severity)">{{ inc.severity }}</ion-badge>
                    </div>
                    <div class="inc-col-status">
                      <ion-badge [color]="getIncidentStatusColor(inc.status)">{{ inc.status }}</ion-badge>
                    </div>
                    <div class="inc-col-started">{{ inc.started_at }}</div>
                    <div class="inc-col-resolved">{{ inc.resolved_at ?? 'Ongoing' }}</div>
                    <div class="inc-col-duration">{{ formatDuration(inc.duration_minutes) }}</div>
                  </div>
                }
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
                <div class="response-bar-wrapper">
                  <div class="response-bar"
                       [style.width.%]="getResponseBarWidth(getResponseAvg())"
                       [class.rbar-green]="getResponseAvg() > 0 && getResponseAvg() < 200"
                       [class.rbar-amber]="getResponseAvg() >= 200 && getResponseAvg() < 500"
                       [class.rbar-red]="getResponseAvg() >= 500">
                  </div>
                </div>
              </div>
              <div class="response-stat">
                <div class="stat-value" [class]="getResponseClass(getResponseP95())">
                  {{ getResponseP95() > 0 ? (getResponseP95() | number:'1.0-0') + 'ms' : 'N/A' }}
                </div>
                <div class="stat-label">P95</div>
                <div class="response-bar-wrapper">
                  <div class="response-bar"
                       [style.width.%]="getResponseBarWidth(getResponseP95())"
                       [class.rbar-green]="getResponseP95() > 0 && getResponseP95() < 200"
                       [class.rbar-amber]="getResponseP95() >= 200 && getResponseP95() < 500"
                       [class.rbar-red]="getResponseP95() >= 500">
                  </div>
                </div>
              </div>
              <div class="response-stat">
                <div class="stat-value" [class]="getResponseClass(getResponseMax())">
                  {{ getResponseMax() > 0 ? (getResponseMax() | number:'1.0-0') + 'ms' : 'N/A' }}
                </div>
                <div class="stat-label">Max</div>
                <div class="response-bar-wrapper">
                  <div class="response-bar"
                       [style.width.%]="getResponseBarWidth(getResponseMax())"
                       [class.rbar-green]="getResponseMax() > 0 && getResponseMax() < 200"
                       [class.rbar-amber]="getResponseMax() >= 200 && getResponseMax() < 500"
                       [class.rbar-red]="getResponseMax() >= 500">
                  </div>
                </div>
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

        <!-- ===== PRINT FOOTER ===== -->
        <div class="print-only print-footer">
          Generated by KeepUp (usekeeup.com) on {{ today | date:'longDate' }}
        </div>
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

    .print-only { display: none; }

    /* ===== STATUS HERO ===== */
    .status-hero { display: flex; align-items: center; gap: 16px; padding-bottom: 20px; margin-bottom: 16px; border-bottom: 1px solid var(--ion-color-light-shade); }

    .status-icon-circle {
      width: 56px; height: 56px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .status-icon-circle ion-icon { font-size: 28px; color: #fff; }
    .circle-success { background: #00C853; }
    .circle-warning { background: #F9A825; }
    .circle-danger { background: #FF1744; }

    .status-hero-text { flex: 1; }

    .status-hero-label { font-size: 1.4rem; font-weight: 800; letter-spacing: 0.02em; }
    .text-success { color: #00C853; }
    .text-warning { color: #F9A825; }
    .text-danger { color: #FF1744; }
    .status-hero-desc { font-size: 0.85rem; color: var(--ion-color-medium); margin-top: 2px; }

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

    .success-text { color: #00C853; }
    .warning-text { color: #F9A825; }
    .danger-text { color: #FF1744; }

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
    .budget-bar-container { height: 20px; background: var(--ion-color-light); border-radius: 10px; overflow: hidden; }
    .budget-bar-used { height: 100%; border-radius: 10px; transition: width 0.4s ease; display: flex; align-items: center; justify-content: center; }
    .budget-bar-pct { font-size: 0.65rem; font-weight: 700; color: #fff; text-shadow: 0 1px 2px rgba(0,0,0,0.3); }
    .bar-green { background: linear-gradient(90deg, #00C853, #69F0AE); }
    .bar-gradient-mid { background: linear-gradient(90deg, #00C853, #F9A825); }
    .bar-gradient-high { background: linear-gradient(90deg, #F9A825, #FF1744); }
    .budget-footer { display: flex; justify-content: space-between; font-size: 0.72rem; color: var(--ion-color-medium); margin-top: 6px; }
    .budget-remaining { font-weight: 600; color: var(--ion-color-dark); }

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
    .mini-success { color: #00C853; font-weight: 600; }
    .mini-danger { color: #FF1744; font-weight: 600; }

    /* ===== HEATMAP ===== */
    .heatmap-row { display: flex; flex-wrap: wrap; gap: 3px; margin-bottom: 16px; padding: 12px; background: var(--ion-color-light); border-radius: 8px; }
    .heatmap-cell { width: 14px; height: 14px; border-radius: 3px; background: var(--ion-color-medium-tint); }
    .heatmap-green { background: #00C853; }
    .heatmap-amber { background: #F9A825; }
    .heatmap-red { background: #FF1744; }

    /* ===== DAILY AVAILABILITY ===== */
    .daily-card { page-break-inside: avoid; }

    .daily-header {
      display: flex; align-items: center; gap: 10px; padding: 8px 12px;
      font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
      letter-spacing: 0.04em; color: var(--ion-color-medium);
      border-bottom: 2px solid var(--ion-color-light-shade); margin-bottom: 2px;
    }
    .daily-hdr-dot { width: 12px; flex-shrink: 0; }
    .daily-hdr-date { flex: 1; min-width: 100px; }
    .daily-hdr-uptime { min-width: 64px; text-align: right; }
    .daily-hdr-downtime, .daily-hdr-incidents { min-width: 80px; text-align: right; }

    .daily-list { display: flex; flex-direction: column; gap: 1px; }
    .daily-row { display: flex; align-items: center; gap: 10px; padding: 8px 12px; border-radius: 4px; font-size: 0.82rem; }
    .daily-row-alt { background: var(--ion-color-light); }
    .daily-row.daily-perfect { background: rgba(0,200,83,0.05); }
    .daily-row.daily-perfect.daily-row-alt { background: rgba(0,200,83,0.09); }
    .daily-row.daily-amber { background: rgba(249,168,37,0.08); }
    .daily-row.daily-amber.daily-row-alt { background: rgba(249,168,37,0.12); }
    .daily-row.daily-red { background: rgba(255,23,68,0.06); }
    .daily-row.daily-red.daily-row-alt { background: rgba(255,23,68,0.10); }
    .daily-dot { width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; background: var(--ion-color-medium); }
    .daily-dot.dot-green { background: #00C853; }
    .daily-dot.dot-amber { background: #F9A825; }
    .daily-dot.dot-red { background: #FF1744; }
    .daily-date { flex: 1; font-weight: 500; min-width: 100px; }
    .daily-uptime { font-weight: 700; min-width: 64px; text-align: right; font-family: 'DM Sans', monospace; }
    .daily-downtime, .daily-incidents { min-width: 80px; text-align: right; color: var(--ion-color-medium); font-size: 0.75rem; }

    .empty-state {
      text-align: center;
      padding: 24px 16px;
      color: var(--ion-color-medium);
      font-size: 0.85rem;
    }

    /* ===== INCIDENTS TABLE ===== */
    .incidents-table { overflow-x: auto; }
    .incidents-header {
      display: flex; align-items: center; gap: 8px; padding: 8px 12px;
      font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
      letter-spacing: 0.04em; color: var(--ion-color-medium);
      border-bottom: 2px solid var(--ion-color-light-shade); margin-bottom: 2px;
    }
    .incidents-row {
      display: flex; align-items: center; gap: 8px; padding: 8px 12px;
      font-size: 0.82rem; border-radius: 4px;
    }
    .incidents-row-alt { background: var(--ion-color-light); }
    .inc-col-title { flex: 2; min-width: 120px; font-weight: 500; }
    .inc-col-severity { flex: 0.8; min-width: 70px; text-align: center; }
    .inc-col-status { flex: 0.8; min-width: 80px; text-align: center; }
    .inc-col-started { flex: 1.2; min-width: 120px; font-size: 0.75rem; color: var(--ion-color-medium); }
    .inc-col-resolved { flex: 1.2; min-width: 120px; font-size: 0.75rem; color: var(--ion-color-medium); }
    .inc-col-duration { flex: 0.7; min-width: 60px; text-align: right; font-weight: 600; font-family: 'DM Sans', monospace; }

    /* ===== RESPONSE TIME ===== */
    .response-stats { display: flex; justify-content: space-around; text-align: center; padding: 8px 0; gap: 12px; }
    .response-stat { flex: 1; }
    .response-green { color: #00C853; }
    .response-amber { color: #F9A825; }
    .response-red { color: #FF1744; }
    .response-bar-wrapper { height: 8px; background: var(--ion-color-light); border-radius: 4px; overflow: hidden; margin-top: 8px; }
    .response-bar { height: 100%; border-radius: 4px; transition: width 0.4s ease; }
    .rbar-green { background: #00C853; }
    .rbar-amber { background: #F9A825; }
    .rbar-red { background: #FF1744; }

    /* ===== PRINT STYLES ===== */
    @media print {
      /* Hide navigation and interactive elements */
      ion-header, ion-menu, ion-toolbar, ion-refresher,
      ion-segment, ion-menu-button, ion-back-button,
      .no-print, .period-selector { display: none !important; }

      /* Show print-only elements */
      .print-only { display: block !important; }

      /* Force clean background */
      * { background: white !important; color: black !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

      ion-content { --overflow: visible; --background: white; }

      /* Cards */
      ion-card {
        break-inside: avoid;
        page-break-inside: avoid;
        box-shadow: none !important;
        border: 1px solid #ddd;
        margin: 8px 0;
      }

      /* Restore colors for print */
      .success-text, .text-success, .mini-success, .hero-value.success-text { color: #00C853 !important; }
      .warning-text, .text-warning, .hero-value.warning-text { color: #F9A825 !important; }
      .danger-text, .text-danger, .mini-danger, .hero-value.danger-text { color: #FF1744 !important; }
      .status-icon-circle { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
      .circle-success, .bar-green, .heatmap-green, .dot-green, .rbar-green { background: #00C853 !important; }
      .circle-warning, .bar-gradient-mid, .heatmap-amber, .dot-amber, .rbar-amber { background: #F9A825 !important; }
      .circle-danger, .bar-gradient-high, .heatmap-red, .dot-red, .rbar-red { background: #FF1744 !important; }
      .status-icon-circle ion-icon, .budget-bar-pct { color: #fff !important; }

      .stat-value, .hero-value { color: #000 !important; }
      .print-header { display: block !important; text-align: center; margin-bottom: 20px; border-bottom: 3px solid #1A2332; padding-bottom: 16px; }
      .print-brand { font-size: 1.6rem; font-weight: 900; color: #1A2332 !important; letter-spacing: 0.05em; margin-bottom: 8px; }
      .print-title { font-size: 1.3rem; font-weight: 700; color: #1A2332 !important; margin: 0 0 6px; }
      .print-meta { font-size: 0.85rem; color: #666 !important; margin: 2px 0; }
      .print-footer { display: block !important; text-align: center; font-size: 0.75rem; color: #999 !important; border-top: 1px solid #ddd; padding-top: 12px; margin-top: 24px; }
      .daily-card, .daily-list, .print-footer { page-break-inside: avoid; }
      .daily-row { background: #f9f9f9 !important; border-bottom: 1px solid #eee; }
      .daily-row.daily-row-alt { background: #fff !important; }
      .daily-row.daily-red { background: #fff0f0 !important; }
      .hero-grid { border-bottom-color: #ddd; }
      .budget-bar-container { border: 1px solid #ccc; background: #f0f0f0 !important; }
      .heatmap-row { background: #f5f5f5 !important; }
    }
  `],
})
export class SlaDetailComponent implements OnInit, ViewWillEnter {
  sla = signal<any>(null);
  report = signal<any>(null);
  loading = signal(true);
  period = 'this_month';
  today = new Date();

  private slaId = '';

  constructor(
    private route: ActivatedRoute,
    private api: ApiService,
    private slaService: SlaService,
    private toastCtrl: ToastController,
  ) {}

  ngOnInit(): void {
    this.slaId = this.route.snapshot.paramMap.get('id') ?? '';
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

  onDownloadPdf(): void {
    if (!this.sla()) return;
    this.slaService.exportReport(this.sla()!.public_id, 'pdf').subscribe({
      next: (blob: Blob) => {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `sla-report-${this.sla()!.name.toLowerCase().replace(/[^a-z0-9]+/g, '-')}.pdf`;
        a.click();
        URL.revokeObjectURL(url);
      },
      error: async () => {
        const toast = await this.toastCtrl.create({
          message: 'Failed to generate PDF. Please try again.',
          color: 'danger',
          duration: 3000,
          position: 'bottom',
        });
        await toast.present();
      },
    });
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
    const m = Math.floor(minutes % 60);
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
    const maintMinutes = r.maintenance_minutes ?? 0;
    const totalMinutes = this.getTotalPeriodMinutes() - maintMinutes;
    if (totalMinutes <= 0) return 100;
    const downtime = r.total_downtime_minutes ?? 0;
    return ((totalMinutes - downtime) / totalMinutes) * 100;
  }

  // ===== INCIDENTS =====

  getIncidents(): any[] {
    return this.report()?.incidents || [];
  }

  getSeverityColor(severity: string): string {
    switch (severity?.toLowerCase()) {
      case 'critical': return 'danger';
      case 'major': return 'warning';
      case 'minor': return 'medium';
      default: return 'medium';
    }
  }

  getIncidentStatusColor(status: string): string {
    switch (status?.toLowerCase()) {
      case 'resolved': return 'success';
      case 'investigating': return 'warning';
      case 'open': return 'danger';
      default: return 'medium';
    }
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
    if (ms < 200) return 'stat-value response-green';
    if (ms < 500) return 'stat-value response-amber';
    return 'stat-value response-red';
  }

  getResponseBarWidth(ms: number): number {
    if (ms <= 0) return 0;
    // Scale: 1000ms = 100% width
    return Math.min((ms / 1000) * 100, 100);
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
