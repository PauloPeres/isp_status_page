import { Component, OnInit, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
  IonLabel, IonBadge, IonCard, IonCardHeader, IonCardTitle,
  IonCardContent, IonIcon, IonSpinner,
  IonRefresher, IonRefresherContent, IonProgressBar,
  ToastController,
} from '@ionic/angular/standalone';
import { BillingService, Plan, CreditBalance, Usage } from './billing.service';
import { addIcons } from 'ionicons';
import {
  walletOutline, checkmarkCircleOutline, starOutline, rocketOutline,
  speedometerOutline, peopleOutline, layersOutline, pulseOutline,
  serverOutline, shieldCheckmarkOutline, openOutline, diamondOutline,
  closeCircleOutline, analyticsOutline,
} from 'ionicons/icons';

addIcons({
  walletOutline, checkmarkCircleOutline, starOutline, rocketOutline,
  speedometerOutline, peopleOutline, layersOutline, pulseOutline,
  serverOutline, shieldCheckmarkOutline, openOutline, diamondOutline,
  closeCircleOutline, analyticsOutline,
});

@Component({
  selector: 'app-billing',
  standalone: true,
  imports: [
    CommonModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
    IonLabel, IonBadge, IonCard, IonCardHeader, IonCardTitle,
    IonCardContent, IonIcon, IonSpinner,
    IonRefresher, IonRefresherContent, IonProgressBar,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>Billing & Plans</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content>
      <ion-refresher slot="fixed" (ionRefresh)="onRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

      @if (loading()) {
        <div class="billing-loader">
          <ion-spinner name="crescent"></ion-spinner>
        </div>
      } @else {
        <div class="billing-page">

          <!-- Current Plan + Usage Row -->
          <div class="billing-top-row">

            <!-- Current Plan Card -->
            <div class="billing-section current-plan-card" [class.current-plan-card--featured]="currentPlan()?.slug === 'pro'">
              <div class="current-plan-header">
                <div>
                  <div class="current-plan-label">Current Plan</div>
                  <h2 class="current-plan-name">{{ currentPlan()?.name || 'Free' }}</h2>
                </div>
                <div class="current-plan-price-block">
                  <span class="current-plan-price">{{ currentPlan()?.formatted_price || 'Free' }}</span>
                </div>
              </div>

              @if (currentPlan()?.parsed_features && currentPlan()!.parsed_features!.length > 0) {
                <div class="current-plan-features">
                  @for (feature of currentPlan()!.parsed_features!.slice(0, 6); track feature) {
                    <span class="current-plan-feature-chip">
                      <ion-icon name="checkmark-circle-outline" color="success"></ion-icon>
                      {{ feature }}
                    </span>
                  }
                  @if (currentPlan()!.parsed_features!.length > 6) {
                    <span class="current-plan-feature-chip current-plan-feature-chip--more">
                      +{{ currentPlan()!.parsed_features!.length - 6 }} more
                    </span>
                  }
                </div>
              }

              <div class="current-plan-actions">
                <ion-button fill="outline" size="small" (click)="onManageBilling()">
                  <ion-icon name="open-outline" slot="start"></ion-icon>
                  Manage Billing
                </ion-button>
              </div>
            </div>

            <!-- Usage Meters -->
            <div class="billing-section usage-section">
              <h3 class="billing-section-title">
                <ion-icon name="analytics-outline"></ion-icon>
                Resource Usage
              </h3>

              <div class="usage-meters">
                <!-- Monitors -->
                <div class="usage-meter">
                  <div class="usage-meter-header">
                    <div class="usage-meter-label">
                      <ion-icon name="pulse-outline" color="primary"></ion-icon>
                      Monitors
                    </div>
                    <span class="usage-meter-value">
                      {{ usage()?.monitors ?? 0 }}
                      <span class="usage-meter-limit">/ {{ currentPlan()?.monitor_limit === -1 ? 'Unlimited' : currentPlan()?.monitor_limit ?? 0 }}</span>
                    </span>
                  </div>
                  <ion-progress-bar
                    [value]="getUsageRatio('monitors')"
                    [color]="getUsageColor('monitors')">
                  </ion-progress-bar>
                </div>

                <!-- Team Members -->
                <div class="usage-meter">
                  <div class="usage-meter-header">
                    <div class="usage-meter-label">
                      <ion-icon name="people-outline" color="primary"></ion-icon>
                      Team Members
                    </div>
                    <span class="usage-meter-value">
                      {{ usage()?.team_members ?? 0 }}
                      <span class="usage-meter-limit">/ {{ currentPlan()?.team_member_limit === -1 ? 'Unlimited' : currentPlan()?.team_member_limit ?? 0 }}</span>
                    </span>
                  </div>
                  <ion-progress-bar
                    [value]="getUsageRatio('team_members')"
                    [color]="getUsageColor('team_members')">
                  </ion-progress-bar>
                </div>

                <!-- Status Pages -->
                <div class="usage-meter">
                  <div class="usage-meter-header">
                    <div class="usage-meter-label">
                      <ion-icon name="layers-outline" color="primary"></ion-icon>
                      Status Pages
                    </div>
                    <span class="usage-meter-value">
                      --
                      <span class="usage-meter-limit">/ {{ currentPlan()?.status_page_limit === -1 ? 'Unlimited' : currentPlan()?.status_page_limit ?? 0 }}</span>
                    </span>
                  </div>
                  <ion-progress-bar value="0" color="primary"></ion-progress-bar>
                </div>

                <!-- Data Retention -->
                <div class="usage-meter">
                  <div class="usage-meter-header">
                    <div class="usage-meter-label">
                      <ion-icon name="server-outline" color="primary"></ion-icon>
                      Data Retention
                    </div>
                    <span class="usage-meter-value">
                      {{ currentPlan()?.data_retention_days ?? 7 }} days
                    </span>
                  </div>
                  <ion-progress-bar value="1" color="medium"></ion-progress-bar>
                </div>

                <!-- Check Interval -->
                <div class="usage-meter">
                  <div class="usage-meter-header">
                    <div class="usage-meter-label">
                      <ion-icon name="speedometer-outline" color="primary"></ion-icon>
                      Check Interval
                    </div>
                    <span class="usage-meter-value">
                      {{ formatInterval(currentPlan()?.check_interval_min ?? 300) }}
                    </span>
                  </div>
                  <ion-progress-bar value="1" color="medium"></ion-progress-bar>
                </div>

                <!-- API Rate Limit -->
                <div class="usage-meter">
                  <div class="usage-meter-header">
                    <div class="usage-meter-label">
                      <ion-icon name="shield-checkmark-outline" color="primary"></ion-icon>
                      API Rate Limit
                    </div>
                    <span class="usage-meter-value">
                      {{ (currentPlan()?.api_rate_limit ?? 0) > 0 ? (currentPlan()!.api_rate_limit | number) + ' req/hr' : 'N/A' }}
                    </span>
                  </div>
                  <ion-progress-bar value="0" color="primary"></ion-progress-bar>
                </div>
              </div>
            </div>
          </div>

          <!-- Notification Credits -->
          <div class="billing-section credits-section">
            <div class="credits-row">
              <div class="credits-info">
                <h3 class="billing-section-title">
                  <ion-icon name="wallet-outline"></ion-icon>
                  Notification Credits
                </h3>
                <div class="credits-balance-row">
                  <span class="credits-balance">{{ credits()?.balance ?? 0 }}</span>
                  <span class="credits-label">credits remaining</span>
                </div>
                @if (credits()?.monthly_grant) {
                  <p class="credits-grant">{{ credits()!.monthly_grant }} credits/month included in your plan</p>
                }
              </div>
              <div class="credits-actions">
                <ion-button fill="outline" size="small" (click)="onBuyCredits()">
                  Buy Credits
                </ion-button>
              </div>
            </div>
          </div>

          <!-- Plan Comparison Grid -->
          <div class="billing-section plans-section">
            <h3 class="billing-section-title plans-section-title">
              <ion-icon name="diamond-outline"></ion-icon>
              Available Plans
            </h3>
            <p class="plans-subtitle">Compare plans and find the best fit for your team.</p>

            <div class="plans-grid">
              @for (plan of plans(); track plan.id) {
                <div class="plan-card-app"
                     [class.plan-card-app--current]="plan.is_current"
                     [class.plan-card-app--featured]="plan.slug === 'pro'"
                     [class.plan-card-app--enterprise]="plan.slug === 'enterprise'">

                  @if (plan.slug === 'pro') {
                    <div class="plan-card-app__badge">Most Popular</div>
                  }

                  <div class="plan-card-app__header">
                    <h4 class="plan-card-app__name">{{ plan.name }}</h4>

                    <div class="plan-card-app__price">
                      <span class="plan-card-app__price-amount">
                        @if (plan.slug === 'enterprise') { Custom }
                        @else if (plan.price_monthly === 0) { {{ formatPriceLarge(plan) }} }
                        @else { {{ formatPriceLarge(plan) }} }
                      </span>
                      @if (plan.price_monthly > 0 && plan.slug !== 'enterprise') {
                        <span class="plan-card-app__price-period">/mo</span>
                      }
                    </div>

                    @if (plan.slug === 'enterprise') {
                      <p class="plan-card-app__price-sub">Contact sales</p>
                    } @else if (plan.price_monthly === 0) {
                      <p class="plan-card-app__price-sub">Free forever</p>
                    } @else if (plan.yearly_savings_percent && plan.yearly_savings_percent > 0) {
                      <p class="plan-card-app__price-sub">
                        {{ plan.formatted_price_yearly }}
                        <span class="plan-card-app__savings">Save {{ plan.yearly_savings_percent }}%</span>
                      </p>
                    }
                  </div>

                  <!-- Limits summary -->
                  <div class="plan-card-app__limits">
                    <div class="plan-card-app__limit">
                      <span class="plan-card-app__limit-value">{{ plan.monitor_limit === -1 ? 'Unlimited' : plan.monitor_limit }}</span>
                      <span class="plan-card-app__limit-label">monitors</span>
                    </div>
                    <div class="plan-card-app__limit">
                      <span class="plan-card-app__limit-value">{{ plan.team_member_limit === -1 ? 'Unlimited' : plan.team_member_limit }}</span>
                      <span class="plan-card-app__limit-label">team members</span>
                    </div>
                    <div class="plan-card-app__limit">
                      <span class="plan-card-app__limit-value">{{ formatInterval(plan.check_interval_min) }}</span>
                      <span class="plan-card-app__limit-label">checks</span>
                    </div>
                    <div class="plan-card-app__limit">
                      <span class="plan-card-app__limit-value">{{ plan.data_retention_days }}d</span>
                      <span class="plan-card-app__limit-label">retention</span>
                    </div>
                  </div>

                  <!-- Feature list -->
                  @if (plan.parsed_features && plan.parsed_features.length > 0) {
                    <ul class="plan-card-app__features">
                      @for (feature of plan.parsed_features; track feature) {
                        <li class="plan-card-app__feature">
                          <ion-icon name="checkmark-circle-outline" color="success"></ion-icon>
                          {{ feature }}
                        </li>
                      }
                    </ul>
                  }

                  <!-- CTA -->
                  <div class="plan-card-app__cta-area">
                    @if (plan.is_current) {
                      <ion-button expand="block" fill="solid" color="medium" disabled="true">
                        Current Plan
                      </ion-button>
                    } @else if (plan.slug === 'enterprise') {
                      <ion-button expand="block" fill="outline" color="dark"
                        href="/contact" target="_blank">
                        Contact Sales
                      </ion-button>
                    } @else if (plan.is_free) {
                      <ion-button expand="block" fill="outline" color="primary" (click)="onDowngrade(plan)">
                        Downgrade
                      </ion-button>
                    } @else if (isUpgrade(plan)) {
                      <ion-button expand="block" fill="solid" color="primary" (click)="onUpgrade(plan)">
                        Upgrade to {{ plan.name }}
                      </ion-button>
                    } @else {
                      <ion-button expand="block" fill="outline" color="primary" (click)="onUpgrade(plan)">
                        Switch to {{ plan.name }}
                      </ion-button>
                    }
                  </div>
                </div>
              } @empty {
                <div class="plans-empty">
                  <p>No plans available.</p>
                </div>
              }
            </div>
          </div>

        </div>
      }
    </ion-content>
  `,
  styles: [`
    /* === Page layout === */
    .billing-loader {
      display: flex; align-items: center; justify-content: center;
      padding: 3rem; min-height: 200px;
    }
    .billing-page {
      max-width: 1100px; margin: 0 auto;
      padding: 1.25rem 1rem 2rem;
    }

    /* === Top row: plan + usage side by side === */
    .billing-top-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
      margin-bottom: 1rem;
    }

    /* === Shared section card === */
    .billing-section {
      background: var(--ion-card-background, #fff);
      border-radius: 14px;
      padding: 1.25rem 1.5rem;
      border: 1px solid var(--ion-border-color, rgba(0,0,0,0.08));
      box-shadow: 0 1px 4px rgba(0,0,0,0.04);
    }
    .billing-section-title {
      font-family: 'DM Sans', system-ui, sans-serif;
      font-size: 1rem; font-weight: 700;
      color: var(--ion-text-color); margin: 0 0 1rem;
      display: flex; align-items: center; gap: 8px;
    }
    .billing-section-title ion-icon {
      font-size: 1.2rem; color: var(--ion-color-primary);
    }

    /* === Current Plan Card === */
    .current-plan-card { display: flex; flex-direction: column; gap: 1rem; }
    .current-plan-card--featured {
      border-color: var(--ion-color-primary);
      box-shadow: 0 2px 12px rgba(41,121,255,0.1);
    }
    .current-plan-header {
      display: flex; justify-content: space-between; align-items: flex-start;
    }
    .current-plan-label {
      font-size: 0.75rem; font-weight: 600; text-transform: uppercase;
      letter-spacing: 0.05em; color: var(--ion-color-primary);
      margin-bottom: 2px;
    }
    .current-plan-name {
      font-family: 'DM Sans', system-ui, sans-serif;
      font-size: 1.75rem; font-weight: 800; margin: 0;
      color: var(--ion-text-color); letter-spacing: -0.02em;
    }
    .current-plan-price-block { text-align: right; }
    .current-plan-price {
      font-family: 'DM Sans', system-ui, sans-serif;
      font-size: 1.5rem; font-weight: 800;
      color: var(--ion-text-color);
    }
    .current-plan-features {
      display: flex; flex-wrap: wrap; gap: 6px;
    }
    .current-plan-feature-chip {
      display: inline-flex; align-items: center; gap: 4px;
      background: var(--ion-color-light, #f4f5f8);
      padding: 4px 10px; border-radius: 20px;
      font-size: 0.75rem; font-weight: 500;
      color: var(--ion-text-color);
    }
    .current-plan-feature-chip ion-icon { font-size: 14px; }
    .current-plan-feature-chip--more {
      background: var(--ion-color-primary-tint, #e3f2fd);
      color: var(--ion-color-primary);
    }
    .current-plan-actions {
      display: flex; gap: 8px; margin-top: auto;
    }

    /* === Usage Meters === */
    .usage-section { display: flex; flex-direction: column; }
    .usage-meters { display: flex; flex-direction: column; gap: 12px; flex: 1; }
    .usage-meter { }
    .usage-meter-header {
      display: flex; justify-content: space-between; align-items: center;
      margin-bottom: 4px;
    }
    .usage-meter-label {
      display: flex; align-items: center; gap: 6px;
      font-size: 0.8rem; font-weight: 600;
      color: var(--ion-text-color);
    }
    .usage-meter-label ion-icon { font-size: 16px; }
    .usage-meter-value {
      font-size: 0.8rem; font-weight: 700;
      font-family: 'DM Sans', system-ui, sans-serif;
      color: var(--ion-text-color);
    }
    .usage-meter-limit { font-weight: 400; color: var(--ion-color-medium); }
    ion-progress-bar { --background: rgba(0,0,0,0.06); border-radius: 4px; height: 6px; }

    /* === Credits === */
    .credits-section { margin-bottom: 1rem; }
    .credits-row {
      display: flex; justify-content: space-between; align-items: center;
      flex-wrap: wrap; gap: 1rem;
    }
    .credits-info { flex: 1; }
    .credits-balance-row { display: flex; align-items: baseline; gap: 8px; }
    .credits-balance {
      font-family: 'DM Sans', system-ui, sans-serif;
      font-size: 2rem; font-weight: 800;
      color: var(--ion-text-color);
    }
    .credits-label {
      font-size: 0.9rem; color: var(--ion-color-medium);
    }
    .credits-grant {
      font-size: 0.8rem; color: var(--ion-color-medium); margin: 4px 0 0;
    }
    .credits-actions {
      display: flex; align-items: center;
    }

    /* === Plans Grid === */
    .plans-section { margin-bottom: 1rem; }
    .plans-section-title { margin-bottom: 4px; }
    .plans-subtitle {
      font-size: 0.85rem; color: var(--ion-color-medium);
      margin: 0 0 1.25rem;
    }
    .plans-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 14px;
      align-items: stretch;
    }
    .plans-empty {
      text-align: center; padding: 2rem;
      color: var(--ion-color-medium); grid-column: 1 / -1;
    }

    /* === Individual Plan Card (App) === */
    .plan-card-app {
      background: var(--ion-card-background, #fff);
      border: 1px solid var(--ion-border-color, rgba(0,0,0,0.08));
      border-radius: 14px; padding: 20px 16px 16px;
      position: relative; display: flex; flex-direction: column;
      transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }
    .plan-card-app:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    }
    .plan-card-app--current {
      border-color: var(--ion-color-primary);
      box-shadow: 0 2px 12px rgba(41,121,255,0.12);
    }
    .plan-card-app--featured {
      border-color: var(--ion-color-primary);
      box-shadow: 0 4px 20px rgba(41,121,255,0.15);
    }
    .plan-card-app__badge {
      position: absolute; top: -10px; left: 50%; transform: translateX(-50%);
      background: var(--ion-color-primary); color: #fff;
      padding: 3px 14px; border-radius: 20px;
      font-size: 0.65rem; font-weight: 700;
      text-transform: uppercase; letter-spacing: 0.04em;
      white-space: nowrap;
    }

    /* Plan card header */
    .plan-card-app__header {
      text-align: center; padding-bottom: 14px;
      margin-bottom: 14px; border-bottom: 1px solid var(--ion-border-color, rgba(0,0,0,0.06));
    }
    .plan-card-app__name {
      font-family: 'DM Sans', system-ui, sans-serif;
      font-size: 1.05rem; font-weight: 700; margin: 0 0 8px;
      color: var(--ion-text-color);
    }
    .plan-card-app--featured .plan-card-app__name {
      color: var(--ion-color-primary);
    }
    .plan-card-app__price { display: flex; align-items: baseline; justify-content: center; gap: 2px; }
    .plan-card-app__price-amount {
      font-family: 'DM Sans', system-ui, sans-serif;
      font-size: 2rem; font-weight: 800; letter-spacing: -0.02em;
      color: var(--ion-text-color); line-height: 1;
    }
    .plan-card-app__price-period {
      font-size: 0.85rem; font-weight: 500; color: var(--ion-color-medium);
    }
    .plan-card-app__price-sub {
      font-size: 0.7rem; color: var(--ion-color-medium); margin: 4px 0 0;
    }
    .plan-card-app__savings {
      display: inline-block;
      background: rgba(0, 200, 83, 0.12); color: #00c853;
      font-size: 0.6rem; font-weight: 700;
      padding: 1px 6px; border-radius: 10px; margin-left: 4px;
    }

    /* Limits row */
    .plan-card-app__limits {
      display: grid; grid-template-columns: 1fr 1fr; gap: 8px;
      margin-bottom: 14px;
    }
    .plan-card-app__limit {
      text-align: center; padding: 6px 4px;
      background: var(--ion-color-light, #f4f5f8); border-radius: 8px;
    }
    .plan-card-app__limit-value {
      display: block; font-family: 'DM Sans', system-ui, sans-serif;
      font-size: 0.8rem; font-weight: 700; color: var(--ion-text-color);
    }
    .plan-card-app__limit-label {
      font-size: 0.6rem; color: var(--ion-color-medium); text-transform: uppercase;
      letter-spacing: 0.03em;
    }

    /* Features list */
    .plan-card-app__features {
      list-style: none; padding: 0; margin: 0 0 14px; flex: 1;
    }
    .plan-card-app__feature {
      display: flex; align-items: center; gap: 6px;
      font-size: 0.75rem; padding: 3px 0;
      color: var(--ion-text-color);
    }
    .plan-card-app__feature ion-icon { font-size: 14px; min-width: 14px; }

    /* CTA area */
    .plan-card-app__cta-area { margin-top: auto; }
    .plan-card-app__cta-area ion-button {
      --border-radius: 10px;
      font-weight: 600; font-size: 0.8rem;
    }

    /* === Responsive === */
    @media (max-width: 960px) {
      .billing-top-row { grid-template-columns: 1fr; }
      .plans-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 640px) {
      .billing-page { padding: 1rem 0.75rem 1.5rem; }
      .plans-grid { grid-template-columns: 1fr; max-width: 400px; margin: 0 auto; }
      .plan-card-app:hover { transform: none; }
      .current-plan-header { flex-direction: column; gap: 8px; }
      .credits-row { flex-direction: column; align-items: flex-start; }
    }
  `],
})
export class BillingComponent implements OnInit {
  plans = signal<Plan[]>([]);
  credits = signal<CreditBalance | null>(null);
  usage = signal<Usage | null>(null);
  loading = signal(true);

  currentPlan = computed(() => this.plans().find(p => p.is_current) || null);

  constructor(private service: BillingService, private toastCtrl: ToastController) {}

  ngOnInit(): void { this.loadAll(); }

  loadAll(): void {
    this.loading.set(true);
    this.service.getPlans().subscribe({
      next: (plans) => { this.plans.set(plans); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
    this.service.getCredits().subscribe({
      next: (c) => this.credits.set(c),
      error: () => {},
    });
    this.service.getUsage().subscribe({
      next: (u) => this.usage.set(u),
      error: () => {},
    });
  }

  onRefresh(event: any): void {
    this.loadAll();
    setTimeout(() => event.target.complete(), 1000);
  }

  formatInterval(seconds: number): string {
    if (seconds < 60) return seconds + 's';
    return Math.round(seconds / 60) + 'm';
  }

  formatDollars(cents: number): string {
    return Math.round(cents / 100).toString();
  }

  formatPriceLarge(plan: Plan): string {
    if (plan.slug === 'enterprise') return 'Custom';
    if (plan.price_monthly === 0) return '$0';
    return '$' + Math.round(plan.price_monthly / 100);
  }

  getUsageRatio(resource: string): number {
    const plan = this.currentPlan();
    if (!plan || !this.usage()) return 0;

    let limit: number;
    let current: number;

    switch (resource) {
      case 'monitors':
        limit = plan.monitor_limit;
        current = this.usage()!.monitors;
        break;
      case 'team_members':
        limit = plan.team_member_limit;
        current = this.usage()!.team_members;
        break;
      default:
        return 0;
    }

    if (limit === -1) return current > 0 ? 0.1 : 0; // unlimited: show tiny bar
    if (limit === 0) return 1;
    return Math.min(1, current / limit);
  }

  getUsageColor(resource: string): string {
    const ratio = this.getUsageRatio(resource);
    if (ratio >= 0.9) return 'danger';
    if (ratio >= 0.7) return 'warning';
    return 'primary';
  }

  isUpgrade(plan: Plan): boolean {
    const current = this.currentPlan();
    if (!current) return true;
    return plan.price_monthly > current.price_monthly;
  }

  async onUpgrade(plan: Plan): Promise<void> {
    this.service.checkout(plan.slug).subscribe({
      next: (res: any) => {
        if (res?.checkout_url) window.location.href = res.checkout_url;
      },
      error: async () => {
        const toast = await this.toastCtrl.create({
          message: 'Stripe is not configured. Contact support.',
          color: 'warning', duration: 3000, position: 'bottom',
        });
        await toast.present();
      },
    });
  }

  async onDowngrade(plan: Plan): Promise<void> {
    const toast = await this.toastCtrl.create({
      message: 'To downgrade, use the billing portal to manage your subscription.',
      color: 'medium', duration: 4000, position: 'bottom',
    });
    await toast.present();
    this.onManageBilling();
  }

  async onManageBilling(): Promise<void> {
    this.service.openPortal().subscribe({
      next: (res: any) => {
        if (res?.portal_url) window.location.href = res.portal_url;
      },
      error: async () => {
        const toast = await this.toastCtrl.create({
          message: 'Stripe is not configured. Contact support to manage billing.',
          color: 'warning', duration: 3000, position: 'bottom',
        });
        await toast.present();
      },
    });
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
