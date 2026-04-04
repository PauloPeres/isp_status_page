import { Component, OnInit, signal, computed, ElementRef, ViewChild, AfterViewInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
  IonLabel, IonBadge, IonIcon, IonSpinner, IonSegment, IonSegmentButton,
  IonRefresher, IonRefresherContent, IonSelect, IonSelectOption, IonItem, IonNote,
  IonInfiniteScroll, IonInfiniteScrollContent,
  ToastController,
} from '@ionic/angular/standalone';
import {
  BillingService, CreditUsageResponse, CreditTransaction, VoiceCallLog, VoiceCallLogsResponse,
} from './billing.service';
import { addIcons } from 'ionicons';
import {
  walletOutline, trendingDownOutline, calendarOutline, flashOutline,
  callOutline, chatbubbleOutline, mailOutline, filterOutline,
  arrowForwardOutline, warningOutline, checkmarkCircleOutline,
  closeCircleOutline, arrowUpOutline, removeOutline, timeOutline,
  statsChartOutline, cardOutline,
} from 'ionicons/icons';

addIcons({
  walletOutline, trendingDownOutline, calendarOutline, flashOutline,
  callOutline, chatbubbleOutline, mailOutline, filterOutline,
  arrowForwardOutline, warningOutline, checkmarkCircleOutline,
  closeCircleOutline, arrowUpOutline, removeOutline, timeOutline,
  statsChartOutline, cardOutline,
});

@Component({
  selector: 'app-credit-usage',
  standalone: true,
  imports: [
    CommonModule, FormsModule, RouterLink,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
    IonLabel, IonBadge, IonIcon, IonSpinner, IonSegment, IonSegmentButton,
    IonRefresher, IonRefresherContent, IonSelect, IonSelectOption, IonItem, IonNote,
    IonInfiniteScroll, IonInfiniteScrollContent,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>Credit Usage</ion-title>
        <ion-buttons slot="end">
          <ion-button (click)="onBuyCredits()" fill="solid" color="primary" size="small">
            <ion-icon name="wallet-outline" slot="start"></ion-icon>
            Buy Credits
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content>
      <ion-refresher slot="fixed" (ionRefresh)="onRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

      @if (loading()) {
        <div class="cu-loader">
          <ion-spinner name="crescent"></ion-spinner>
        </div>
      } @else {
        <div class="cu-page">

          <!-- Balance + Summary Cards Row -->
          <div class="cu-summary-row">
            <div class="cu-card cu-balance-card">
              <div class="cu-balance-top">
                <div class="cu-balance-icon-wrap">
                  <ion-icon name="wallet-outline"></ion-icon>
                </div>
                <div class="cu-balance-info">
                  <span class="cu-balance-label">Credit Balance</span>
                  <span class="cu-balance-amount">{{ data()?.balance ?? 0 }}</span>
                </div>
              </div>
              @if (data()?.summary?.depletion_date) {
                <div class="cu-depletion">
                  <ion-icon name="warning-outline" color="warning"></ion-icon>
                  <span>At current rate, credits deplete by <strong>{{ data()!.summary.depletion_date }}</strong></span>
                </div>
              }
            </div>

            <div class="cu-card cu-stat-card">
              <div class="cu-stat-icon" style="background: rgba(234,67,53,0.1); color: #ea4335">
                <ion-icon name="trending-down-outline"></ion-icon>
              </div>
              <div class="cu-stat-value">{{ data()?.summary?.total_used_30d ?? 0 }}</div>
              <div class="cu-stat-label">Used (30 days)</div>
            </div>

            <div class="cu-card cu-stat-card">
              <div class="cu-stat-icon" style="background: rgba(41,121,255,0.1); color: #2979ff">
                <ion-icon name="stats-chart-outline"></ion-icon>
              </div>
              <div class="cu-stat-value">{{ data()?.summary?.avg_per_day ?? 0 }}</div>
              <div class="cu-stat-label">Avg / Day</div>
            </div>

            <div class="cu-card cu-stat-card">
              <div class="cu-stat-icon" style="background: rgba(251,188,4,0.1); color: #fbbc04">
                <ion-icon name="calendar-outline"></ion-icon>
              </div>
              <div class="cu-stat-value">{{ data()?.summary?.projected_monthly ?? 0 }}</div>
              <div class="cu-stat-label">Projected / Month</div>
            </div>
          </div>

          <!-- Usage Chart -->
          <div class="cu-card cu-chart-section">
            <h3 class="cu-section-title">
              <ion-icon name="stats-chart-outline"></ion-icon>
              Daily Credit Usage (Last 30 Days)
            </h3>
            <div class="cu-chart">
              @for (day of data()?.summary?.daily_usage ?? []; track day.date) {
                <div class="cu-bar-col" [title]="day.date + ': ' + day.credits + ' credits'">
                  <div class="cu-bar" [style.height.%]="getBarHeight(day.credits)"></div>
                  @if ($index % 5 === 0 || $index === 29) {
                    <span class="cu-bar-label">{{ day.date.substring(5) }}</span>
                  }
                </div>
              }
            </div>
          </div>

          <!-- Channel Breakdown + Channel Costs -->
          <div class="cu-two-col">
            <div class="cu-card cu-channel-breakdown">
              <h3 class="cu-section-title">
                <ion-icon name="flash-outline"></ion-icon>
                Breakdown by Channel
              </h3>
              <div class="cu-channel-bars">
                @for (ch of channelBreakdown(); track ch.name) {
                  <div class="cu-channel-row">
                    <div class="cu-channel-info">
                      <ion-badge [color]="ch.color" class="cu-channel-badge">{{ ch.label }}</ion-badge>
                      <span class="cu-channel-credits">{{ ch.credits }} credits</span>
                    </div>
                    <div class="cu-channel-bar-wrap">
                      <div class="cu-channel-bar" [style.width.%]="ch.percent" [style.background]="ch.barColor"></div>
                    </div>
                  </div>
                }
              </div>
            </div>

            <div class="cu-card cu-cost-card">
              <h3 class="cu-section-title">
                <ion-icon name="card-outline"></ion-icon>
                Channel Credit Costs
              </h3>
              <div class="cu-cost-grid">
                <div class="cu-cost-item">
                  <div class="cu-cost-icon" style="background: #e3f2fd; color: #1976d2">
                    <ion-icon name="mail-outline"></ion-icon>
                  </div>
                  <div class="cu-cost-detail">
                    <span class="cu-cost-name">SMS</span>
                    <span class="cu-cost-amount">1 credit</span>
                  </div>
                </div>
                <div class="cu-cost-item">
                  <div class="cu-cost-icon" style="background: #e8f5e9; color: #388e3c">
                    <ion-icon name="chatbubble-outline"></ion-icon>
                  </div>
                  <div class="cu-cost-detail">
                    <span class="cu-cost-name">WhatsApp</span>
                    <span class="cu-cost-amount">1 credit</span>
                  </div>
                </div>
                <div class="cu-cost-item">
                  <div class="cu-cost-icon" style="background: #fff3e0; color: #e65100">
                    <ion-icon name="call-outline"></ion-icon>
                  </div>
                  <div class="cu-cost-detail">
                    <span class="cu-cost-name">Voice Call</span>
                    <span class="cu-cost-amount">3 credits</span>
                  </div>
                </div>
              </div>

              @if (data()?.summary?.top_consumers && data()!.summary.top_consumers.length > 0) {
                <h4 class="cu-subsection-title">Top Consumers (30d)</h4>
                <div class="cu-consumer-list">
                  @for (tc of data()!.summary.top_consumers.slice(0, 5); track tc.description) {
                    <div class="cu-consumer-item">
                      <span class="cu-consumer-desc">{{ tc.description }}</span>
                      <ion-badge [color]="getChannelColor(tc.channel)" size="small">{{ tc.total_credits }}</ion-badge>
                    </div>
                  }
                </div>
              }
            </div>
          </div>

          <!-- Tabs: Transactions / Voice Calls -->
          <div class="cu-card cu-transactions-section">
            <div class="cu-tab-header">
              <ion-segment [(ngModel)]="activeTab" (ionChange)="onTabChange()">
                <ion-segment-button value="transactions">
                  <ion-label>Transactions</ion-label>
                </ion-segment-button>
                <ion-segment-button value="voice_calls">
                  <ion-label>Voice Call Logs</ion-label>
                </ion-segment-button>
              </ion-segment>
            </div>

            <!-- Filters -->
            @if (activeTab === 'transactions') {
              <div class="cu-filters">
                <ion-item lines="none" class="cu-filter-item">
                  <ion-select [(ngModel)]="channelFilter" (ionChange)="loadCreditUsage()" interface="popover" placeholder="All channels" label="Channel" labelPlacement="start">
                    <ion-select-option value="">All Channels</ion-select-option>
                    <ion-select-option value="sms">SMS</ion-select-option>
                    <ion-select-option value="whatsapp">WhatsApp</ion-select-option>
                    <ion-select-option value="voice_call">Voice Call</ion-select-option>
                  </ion-select>
                </ion-item>
              </div>

              <!-- Transaction Table -->
              <div class="cu-table-wrap">
                <table class="cu-table">
                  <thead>
                    <tr>
                      <th>Date</th>
                      <th>Channel</th>
                      <th>Description</th>
                      <th class="cu-th-right">Credits</th>
                    </tr>
                  </thead>
                  <tbody>
                    @for (tx of data()?.transactions ?? []; track tx.id) {
                      <tr>
                        <td class="cu-td-date">{{ tx.created | date:'MMM d, y h:mm a' }}</td>
                        <td>
                          @if (tx.channel) {
                            <ion-badge [color]="getChannelColor(tx.channel)" class="cu-channel-badge-sm">{{ formatChannel(tx.channel) }}</ion-badge>
                          } @else {
                            <ion-badge color="medium" class="cu-channel-badge-sm">{{ tx.type }}</ion-badge>
                          }
                        </td>
                        <td class="cu-td-desc">{{ tx.description || '--' }}</td>
                        <td class="cu-td-credits" [class.cu-credit-negative]="tx.amount < 0" [class.cu-credit-positive]="tx.amount > 0">
                          {{ tx.amount > 0 ? '+' : '' }}{{ tx.amount }}
                        </td>
                      </tr>
                    } @empty {
                      <tr><td colspan="4" class="cu-empty-row">No transactions found</td></tr>
                    }
                  </tbody>
                </table>
              </div>

              <!-- Pagination -->
              @if (data()?.pagination && data()!.pagination.pages > 1) {
                <div class="cu-pagination">
                  <ion-button fill="clear" size="small" [disabled]="currentPage <= 1" (click)="goToPage(currentPage - 1)">Previous</ion-button>
                  <span class="cu-page-info">Page {{ currentPage }} of {{ data()!.pagination.pages }}</span>
                  <ion-button fill="clear" size="small" [disabled]="currentPage >= data()!.pagination.pages" (click)="goToPage(currentPage + 1)">Next</ion-button>
                </div>
              }
            }

            @if (activeTab === 'voice_calls') {
              <!-- Voice Call Logs Table -->
              <div class="cu-table-wrap">
                <table class="cu-table">
                  <thead>
                    <tr>
                      <th>Date</th>
                      <th>Phone</th>
                      <th>Status</th>
                      <th>DTMF Result</th>
                      <th>Duration</th>
                      <th>Language</th>
                      <th class="cu-th-right">Credits</th>
                    </tr>
                  </thead>
                  <tbody>
                    @for (log of voiceCallLogs(); track log.id) {
                      <tr>
                        <td class="cu-td-date">{{ log.created | date:'MMM d, y h:mm a' }}</td>
                        <td class="cu-td-phone">{{ log.phone_number }}</td>
                        <td>
                          <ion-badge [color]="getStatusColor(log.status)" class="cu-status-badge">{{ log.status }}</ion-badge>
                        </td>
                        <td>
                          <span class="cu-dtmf" [class]="'cu-dtmf--' + getDtmfClass(log.dtmf_result)">
                            @if (log.dtmf_result === 'Acknowledged') {
                              <ion-icon name="checkmark-circle-outline" color="success"></ion-icon>
                            } @else if (log.dtmf_result === 'Escalated') {
                              <ion-icon name="arrow-up-outline" color="warning"></ion-icon>
                            } @else {
                              <ion-icon name="remove-outline" color="medium"></ion-icon>
                            }
                            {{ log.dtmf_result }}
                          </span>
                        </td>
                        <td>{{ log.duration_seconds ? log.duration_seconds + 's' : '--' }}</td>
                        <td>{{ log.tts_language || '--' }}</td>
                        <td class="cu-td-credits cu-credit-negative">-{{ log.cost_credits }}</td>
                      </tr>
                    } @empty {
                      <tr><td colspan="7" class="cu-empty-row">No voice call logs found</td></tr>
                    }
                  </tbody>
                </table>
              </div>

              @if (voiceCallPagination() && voiceCallPagination()!.pages > 1) {
                <div class="cu-pagination">
                  <ion-button fill="clear" size="small" [disabled]="vcPage <= 1" (click)="goToVcPage(vcPage - 1)">Previous</ion-button>
                  <span class="cu-page-info">Page {{ vcPage }} of {{ voiceCallPagination()!.pages }}</span>
                  <ion-button fill="clear" size="small" [disabled]="vcPage >= voiceCallPagination()!.pages" (click)="goToVcPage(vcPage + 1)">Next</ion-button>
                </div>
              }
            }
          </div>

        </div>
      }
    </ion-content>
  `,
  styles: [`
    .cu-loader {
      display: flex; align-items: center; justify-content: center;
      padding: 3rem; min-height: 200px;
    }
    .cu-page {
      max-width: 1100px; margin: 0 auto;
      padding: 1.25rem 1rem 2rem;
    }

    /* Summary Row */
    .cu-summary-row {
      display: grid;
      grid-template-columns: 2fr 1fr 1fr 1fr;
      gap: 12px;
      margin-bottom: 1rem;
    }

    .cu-card {
      background: var(--ion-card-background, #fff);
      border-radius: 14px;
      padding: 1.25rem 1.5rem;
      border: 1px solid var(--ion-border-color, rgba(0,0,0,0.08));
      box-shadow: 0 1px 4px rgba(0,0,0,0.04);
    }

    /* Balance Card */
    .cu-balance-card {
      display: flex; flex-direction: column; gap: 12px;
    }
    .cu-balance-top {
      display: flex; align-items: center; gap: 16px;
    }
    .cu-balance-icon-wrap {
      width: 48px; height: 48px; border-radius: 12px;
      background: rgba(41,121,255,0.1);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.5rem; color: var(--ion-color-primary);
    }
    .cu-balance-info {
      display: flex; flex-direction: column;
    }
    .cu-balance-label {
      font-size: 0.75rem; font-weight: 600; text-transform: uppercase;
      letter-spacing: 0.04em; color: var(--ion-color-medium);
    }
    .cu-balance-amount {
      font-family: 'DM Sans', system-ui, sans-serif;
      font-size: 2.5rem; font-weight: 800;
      color: var(--ion-text-color); line-height: 1.1;
    }
    .cu-depletion {
      display: flex; align-items: center; gap: 6px;
      font-size: 0.8rem; color: var(--ion-color-medium);
      background: rgba(251,188,4,0.08); padding: 8px 12px; border-radius: 8px;
    }
    .cu-depletion strong { color: var(--ion-text-color); }

    /* Stat Cards */
    .cu-stat-card {
      display: flex; flex-direction: column; align-items: center;
      justify-content: center; text-align: center; gap: 6px;
    }
    .cu-stat-icon {
      width: 40px; height: 40px; border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.2rem;
    }
    .cu-stat-value {
      font-family: 'DM Sans', system-ui, sans-serif;
      font-size: 1.5rem; font-weight: 800;
      color: var(--ion-text-color); line-height: 1;
    }
    .cu-stat-label {
      font-size: 0.7rem; font-weight: 600; text-transform: uppercase;
      letter-spacing: 0.03em; color: var(--ion-color-medium);
    }

    /* Chart */
    .cu-chart-section { margin-bottom: 1rem; }
    .cu-section-title {
      font-family: 'DM Sans', system-ui, sans-serif;
      font-size: 0.95rem; font-weight: 700;
      color: var(--ion-text-color); margin: 0 0 1rem;
      display: flex; align-items: center; gap: 8px;
    }
    .cu-section-title ion-icon {
      font-size: 1.1rem; color: var(--ion-color-primary);
    }
    .cu-chart {
      display: flex; align-items: flex-end; gap: 2px;
      height: 140px; padding: 0 0 20px;
      position: relative;
    }
    .cu-bar-col {
      flex: 1; display: flex; flex-direction: column;
      align-items: center; justify-content: flex-end;
      height: 100%; position: relative; cursor: pointer;
    }
    .cu-bar {
      width: 100%; min-height: 2px; border-radius: 3px 3px 0 0;
      background: var(--ion-color-primary);
      transition: height 0.3s ease;
      opacity: 0.85;
    }
    .cu-bar-col:hover .cu-bar {
      opacity: 1; background: var(--ion-color-primary-shade);
    }
    .cu-bar-label {
      position: absolute; bottom: -18px;
      font-size: 0.55rem; color: var(--ion-color-medium);
      white-space: nowrap;
    }

    /* Two Column */
    .cu-two-col {
      display: grid; grid-template-columns: 1fr 1fr;
      gap: 12px; margin-bottom: 1rem;
    }

    /* Channel Breakdown */
    .cu-channel-bars { display: flex; flex-direction: column; gap: 14px; }
    .cu-channel-row { display: flex; flex-direction: column; gap: 4px; }
    .cu-channel-info {
      display: flex; justify-content: space-between; align-items: center;
    }
    .cu-channel-badge {
      font-size: 0.65rem; text-transform: uppercase;
      letter-spacing: 0.03em; font-weight: 700;
      padding: 3px 8px;
    }
    .cu-channel-credits {
      font-size: 0.8rem; font-weight: 600; color: var(--ion-text-color);
    }
    .cu-channel-bar-wrap {
      height: 8px; background: var(--ion-color-light, rgba(0,0,0,0.06));
      border-radius: 4px; overflow: hidden;
    }
    .cu-channel-bar {
      height: 100%; border-radius: 4px;
      transition: width 0.5s ease;
    }

    /* Cost Grid */
    .cu-cost-grid {
      display: flex; flex-direction: column; gap: 12px;
    }
    .cu-cost-item {
      display: flex; align-items: center; gap: 12px;
    }
    .cu-cost-icon {
      width: 36px; height: 36px; border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1rem;
    }
    .cu-cost-detail {
      display: flex; flex-direction: column;
    }
    .cu-cost-name {
      font-size: 0.8rem; font-weight: 600; color: var(--ion-text-color);
    }
    .cu-cost-amount {
      font-size: 0.7rem; color: var(--ion-color-medium);
    }
    .cu-subsection-title {
      font-size: 0.8rem; font-weight: 700; margin: 1.25rem 0 0.5rem;
      color: var(--ion-text-color);
    }
    .cu-consumer-list {
      display: flex; flex-direction: column; gap: 6px;
    }
    .cu-consumer-item {
      display: flex; justify-content: space-between; align-items: center;
      font-size: 0.75rem; color: var(--ion-text-color);
      padding: 4px 0;
      border-bottom: 1px solid var(--ion-border-color, rgba(0,0,0,0.04));
    }
    .cu-consumer-desc {
      flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
      margin-right: 8px;
    }

    /* Transactions Section */
    .cu-transactions-section { margin-bottom: 1rem; }
    .cu-tab-header { margin-bottom: 1rem; }
    .cu-filters {
      display: flex; flex-wrap: wrap; gap: 8px;
      padding: 0 0 1rem;
    }
    .cu-filter-item {
      --background: transparent;
      --padding-start: 0;
      --inner-padding-end: 0;
      font-size: 0.85rem;
    }

    /* Table */
    .cu-table-wrap { overflow-x: auto; }
    .cu-table {
      width: 100%; border-collapse: collapse;
      font-size: 0.82rem;
    }
    .cu-table th {
      text-align: left; padding: 8px 10px;
      font-size: 0.7rem; font-weight: 700;
      text-transform: uppercase; letter-spacing: 0.04em;
      color: var(--ion-color-medium);
      border-bottom: 2px solid var(--ion-border-color, rgba(0,0,0,0.1));
    }
    .cu-th-right { text-align: right; }
    .cu-table td {
      padding: 10px;
      border-bottom: 1px solid var(--ion-border-color, rgba(0,0,0,0.05));
      color: var(--ion-text-color);
      vertical-align: middle;
    }
    .cu-table tbody tr:hover {
      background: var(--ion-color-light-tint, rgba(0,0,0,0.02));
    }
    .cu-td-date {
      font-size: 0.75rem; color: var(--ion-color-medium);
      white-space: nowrap;
    }
    .cu-td-desc {
      max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .cu-td-phone {
      font-family: monospace; font-size: 0.8rem;
    }
    .cu-td-credits {
      text-align: right; font-weight: 700;
      font-family: 'DM Sans', system-ui, sans-serif;
    }
    .cu-credit-negative { color: #ea4335; }
    .cu-credit-positive { color: #34a853; }
    .cu-channel-badge-sm {
      font-size: 0.6rem; text-transform: uppercase;
      letter-spacing: 0.02em; padding: 2px 6px;
    }
    .cu-status-badge {
      font-size: 0.6rem; text-transform: uppercase;
      padding: 2px 6px;
    }
    .cu-dtmf {
      display: inline-flex; align-items: center; gap: 4px;
      font-size: 0.8rem;
    }
    .cu-empty-row {
      text-align: center; padding: 2rem !important;
      color: var(--ion-color-medium);
    }

    /* Pagination */
    .cu-pagination {
      display: flex; align-items: center; justify-content: center;
      gap: 8px; padding: 12px 0;
    }
    .cu-page-info {
      font-size: 0.8rem; color: var(--ion-color-medium);
    }

    /* Responsive */
    @media (max-width: 960px) {
      .cu-summary-row { grid-template-columns: 1fr 1fr; }
      .cu-two-col { grid-template-columns: 1fr; }
    }
    @media (max-width: 640px) {
      .cu-summary-row { grid-template-columns: 1fr; }
      .cu-page { padding: 1rem 0.75rem 1.5rem; }
    }
  `],
})
export class CreditUsageComponent implements OnInit {
  loading = signal(true);
  data = signal<CreditUsageResponse | null>(null);
  voiceCallLogs = signal<VoiceCallLog[]>([]);
  voiceCallPagination = signal<{ page: number; limit: number; total: number; pages: number } | null>(null);

  activeTab: string = 'transactions';
  channelFilter: string = '';
  currentPage = 1;
  vcPage = 1;

  private maxDailyCredits = 1;

  constructor(
    private service: BillingService,
    private toastCtrl: ToastController,
  ) {}

  ngOnInit(): void {
    this.loadAll();
  }

  loadAll(): void {
    this.loading.set(true);
    this.loadCreditUsage();
    this.loadVoiceCallLogs();
  }

  loadCreditUsage(): void {
    this.service.getCreditUsage({
      channel: this.channelFilter || undefined,
      page: this.currentPage,
      limit: 25,
    }).subscribe({
      next: (data) => {
        this.data.set(data);
        this.maxDailyCredits = Math.max(1, ...data.summary.daily_usage.map(d => d.credits));
        this.loading.set(false);
      },
      error: () => {
        this.loading.set(false);
      },
    });
  }

  loadVoiceCallLogs(): void {
    this.service.getVoiceCallLogs({ page: this.vcPage, limit: 25 }).subscribe({
      next: (data) => {
        this.voiceCallLogs.set(data.voice_call_logs);
        this.voiceCallPagination.set(data.pagination);
      },
      error: () => {},
    });
  }

  onRefresh(event: any): void {
    this.loadAll();
    setTimeout(() => event.target.complete(), 1000);
  }

  onTabChange(): void {
    if (this.activeTab === 'voice_calls' && this.voiceCallLogs().length === 0) {
      this.loadVoiceCallLogs();
    }
  }

  goToPage(page: number): void {
    this.currentPage = page;
    this.loadCreditUsage();
  }

  goToVcPage(page: number): void {
    this.vcPage = page;
    this.loadVoiceCallLogs();
  }

  getBarHeight(credits: number): number {
    if (this.maxDailyCredits === 0) return 2;
    return Math.max(2, (credits / this.maxDailyCredits) * 100);
  }

  channelBreakdown(): { name: string; label: string; credits: number; percent: number; color: string; barColor: string }[] {
    const bc = this.data()?.summary?.by_channel;
    if (!bc) return [];
    const total = Math.max(1, bc.sms + bc.whatsapp + bc.voice_call);
    return [
      { name: 'sms', label: 'SMS', credits: bc.sms, percent: (bc.sms / total) * 100, color: 'primary', barColor: '#2979ff' },
      { name: 'whatsapp', label: 'WhatsApp', credits: bc.whatsapp, percent: (bc.whatsapp / total) * 100, color: 'success', barColor: '#34a853' },
      { name: 'voice_call', label: 'Voice Call', credits: bc.voice_call, percent: (bc.voice_call / total) * 100, color: 'warning', barColor: '#fbbc04' },
    ];
  }

  formatChannel(channel: string): string {
    switch (channel) {
      case 'sms': return 'SMS';
      case 'whatsapp': return 'WhatsApp';
      case 'voice_call': return 'Voice Call';
      default: return channel;
    }
  }

  getChannelColor(channel: string): string {
    switch (channel) {
      case 'sms': return 'primary';
      case 'whatsapp': return 'success';
      case 'voice_call': return 'warning';
      default: return 'medium';
    }
  }

  getStatusColor(status: string): string {
    switch (status) {
      case 'completed': return 'success';
      case 'no-answer': return 'warning';
      case 'busy': return 'tertiary';
      case 'failed': return 'danger';
      default: return 'medium';
    }
  }

  getDtmfClass(result: string): string {
    if (result === 'Acknowledged') return 'ack';
    if (result === 'Escalated') return 'esc';
    return 'none';
  }

  async onBuyCredits(): Promise<void> {
    this.service.buyCredits(100).subscribe({
      next: (res: any) => {
        if (res?.checkout_url) window.location.href = res.checkout_url;
      },
      error: async (err: any) => {
        const msg = err.error?.message || 'Stripe is not configured. Contact support.';
        const toast = await this.toastCtrl.create({
          message: msg, color: 'warning', duration: 3000, position: 'bottom',
        });
        await toast.present();
      },
    });
  }
}
