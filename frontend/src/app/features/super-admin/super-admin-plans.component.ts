import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
  IonList, IonItem, IonNote, IonIcon, IonInput,
  IonToggle, IonCard, IonCardContent,
  IonGrid, IonRow, IonCol, IonCheckbox,
  IonRefresher, IonRefresherContent, IonSkeletonText,
  AlertController, ToastController,
} from '@ionic/angular/standalone';
import { ApiService } from '../../core/services/api.service';
import { addIcons } from 'ionicons';
import {
  addCircleOutline, copyOutline, createOutline, trashOutline,
  checkmarkCircle, closeCircle, starOutline, star,
  closeOutline, eyeOutline,
} from 'ionicons/icons';

addIcons({
  'add-circle-outline': addCircleOutline,
  'copy-outline': copyOutline,
  'create-outline': createOutline,
  'trash-outline': trashOutline,
  'checkmark-circle': checkmarkCircle,
  'close-circle': closeCircle,
  'star-outline': starOutline,
  'star': star,
  'close-outline': closeOutline,
  'eye-outline': eyeOutline,
});

interface Plan {
  id: number;
  name: string;
  slug: string;
  price_monthly: number;
  price_yearly: number;
  monitor_limit: number;
  check_interval_min: number;
  team_member_limit: number;
  status_page_limit: number;
  api_rate_limit: number;
  data_retention_days: number;
  features: string;
  display_order: number;
  active: boolean;
  is_popular?: boolean;
  stripe_price_id_monthly?: string;
  stripe_price_id_yearly?: string;
  organization_count?: number;
}

interface PlanForm {
  name: string;
  slug: string;
  price_monthly: number;
  price_yearly: number;
  monitor_limit: number;
  check_interval_min: number;
  team_member_limit: number;
  status_page_limit: number;
  api_rate_limit: number;
  data_retention_days: number;
  display_order: number;
  active: boolean;
  is_popular: boolean;
  stripe_price_id_monthly: string;
  stripe_price_id_yearly: string;
  features: Record<string, boolean>;
}

const ALL_FEATURES: { key: string; label: string }[] = [
  { key: 'email_alerts', label: 'Email Alerts' },
  { key: 'slack_alerts', label: 'Slack Alerts' },
  { key: 'all_alert_channels', label: 'All Alert Channels' },
  { key: 'api_access', label: 'API Access' },
  { key: 'custom_domains', label: 'Custom Domains' },
  { key: 'multi_region', label: 'Multi-Region' },
  { key: 'custom_branding', label: 'Custom Branding' },
  { key: 'sso_saml', label: 'SSO/SAML' },
  { key: 'ssl_monitoring', label: 'SSL Monitoring' },
  { key: 'priority_support', label: 'Priority Support' },
  { key: 'dedicated_support', label: 'Dedicated Support' },
];

@Component({
  selector: 'app-super-admin-plans',
  standalone: true,
  imports: [
    CommonModule, FormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
    IonList, IonItem, IonNote, IonIcon, IonInput,
    IonToggle, IonCard, IonCardContent,
    IonGrid, IonRow, IonCol, IonCheckbox,
    IonRefresher, IonRefresherContent, IonSkeletonText,
  ],
  template: `
    <ion-header>
      <ion-toolbar color="dark">
        <ion-buttons slot="start">
          <ion-back-button defaultHref="/super-admin"></ion-back-button>
        </ion-buttons>
        <ion-title>Plan Management</ion-title>
        <ion-buttons slot="end">
          <ion-button (click)="onAdd()">
            <ion-icon name="add-circle-outline" slot="start"></ion-icon>
            New Plan
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
              <ion-col size="12" sizeMd="6">
                <ion-card class="plan-card">
                  <ion-card-content style="padding: 24px">
                    <ion-skeleton-text [animated]="true" style="width: 50%; height: 1.5rem; margin-bottom: 12px"></ion-skeleton-text>
                    <ion-skeleton-text [animated]="true" style="width: 30%; height: 2rem; margin-bottom: 16px"></ion-skeleton-text>
                    <ion-skeleton-text [animated]="true" style="width: 100%; height: 0.8rem; margin-bottom: 8px"></ion-skeleton-text>
                    <ion-skeleton-text [animated]="true" style="width: 80%; height: 0.8rem; margin-bottom: 8px"></ion-skeleton-text>
                    <ion-skeleton-text [animated]="true" style="width: 90%; height: 0.8rem; margin-bottom: 8px"></ion-skeleton-text>
                    <ion-skeleton-text [animated]="true" style="width: 60%; height: 0.8rem"></ion-skeleton-text>
                  </ion-card-content>
                </ion-card>
              </ion-col>
            }
          </ion-row>
        </ion-grid>
      } @else {
        <ion-grid>
          <ion-row>
            @for (plan of plans(); track plan.id) {
              <ion-col size="12" sizeMd="6">
                <ion-card class="plan-card" [class.plan-inactive]="!plan.active" [class.plan-popular]="plan.is_popular">
                  <!-- Header -->
                  <div class="plan-header">
                    <div class="plan-header-top">
                      <h2 class="plan-name">{{ plan.name }}</h2>
                      <div class="plan-badges">
                        @if (plan.is_popular) {
                          <span class="badge badge-popular">
                            <ion-icon name="star"></ion-icon> Most Popular
                          </span>
                        }
                        <span class="badge" [class.badge-active]="plan.active" [class.badge-inactive]="!plan.active">
                          {{ plan.active ? 'Active' : 'Inactive' }}
                        </span>
                      </div>
                    </div>
                    <div class="plan-slug-line">{{ plan.slug }} &middot; Order: {{ plan.display_order }}</div>
                  </div>

                  <ion-card-content class="plan-body">
                    <!-- Pricing -->
                    <div class="plan-pricing">
                      @if (plan.price_monthly === 0) {
                        <div class="price-main">Free</div>
                      } @else {
                        <div class="price-main">\${{ (plan.price_monthly / 100) | number:'1.0-2' }}<span class="price-period">/mo</span></div>
                        @if (plan.price_yearly > 0) {
                          <div class="price-yearly">
                            \${{ (plan.price_yearly / 100) | number:'1.0-2' }}/yr
                            @if (getYearlySavings(plan) > 0) {
                              <span class="savings-badge">Save {{ getYearlySavings(plan) }}%</span>
                            }
                          </div>
                        }
                      }
                    </div>

                    <!-- Limits -->
                    <div class="plan-section">
                      <div class="section-title">Limits</div>
                      <div class="limits-grid">
                        <div class="limit-item">
                          <span class="limit-label">Monitors</span>
                          <span class="limit-value">{{ formatLimit(plan.monitor_limit) }}</span>
                        </div>
                        <div class="limit-item">
                          <span class="limit-label">Check Interval</span>
                          <span class="limit-value">{{ formatInterval(plan.check_interval_min) }}</span>
                        </div>
                        <div class="limit-item">
                          <span class="limit-label">Team Members</span>
                          <span class="limit-value">{{ formatLimit(plan.team_member_limit) }}</span>
                        </div>
                        <div class="limit-item">
                          <span class="limit-label">Status Pages</span>
                          <span class="limit-value">{{ formatLimit(plan.status_page_limit) }}</span>
                        </div>
                        <div class="limit-item">
                          <span class="limit-label">API Rate Limit</span>
                          <span class="limit-value">{{ formatApiRate(plan.api_rate_limit) }}</span>
                        </div>
                        <div class="limit-item">
                          <span class="limit-label">Data Retention</span>
                          <span class="limit-value">{{ formatRetention(plan.data_retention_days) }}</span>
                        </div>
                      </div>
                    </div>

                    <!-- Features -->
                    <div class="plan-section">
                      <div class="section-title">Features</div>
                      <div class="features-list">
                        @for (feat of allFeatures; track feat.key) {
                          <div class="feature-row" [class.feature-included]="hasFeature(plan, feat.key)" [class.feature-excluded]="!hasFeature(plan, feat.key)">
                            @if (hasFeature(plan, feat.key)) {
                              <ion-icon name="checkmark-circle" class="feature-icon included"></ion-icon>
                            } @else {
                              <ion-icon name="close-circle" class="feature-icon excluded"></ion-icon>
                            }
                            <span class="feature-name">{{ feat.label }}</span>
                          </div>
                        }
                      </div>
                    </div>

                    <!-- Org count -->
                    <div class="plan-org-count">
                      {{ plan.organization_count || 0 }} organization{{ (plan.organization_count || 0) === 1 ? '' : 's' }} on this plan
                    </div>

                    <!-- Actions -->
                    <div class="plan-actions">
                      <ion-button fill="clear" size="small" (click)="onEdit(plan)">
                        <ion-icon name="create-outline" slot="start"></ion-icon> Edit
                      </ion-button>
                      <ion-button fill="clear" size="small" (click)="onDuplicate(plan)">
                        <ion-icon name="copy-outline" slot="start"></ion-icon> Duplicate
                      </ion-button>
                      <ion-button fill="clear" size="small" color="danger" (click)="onDelete(plan)">
                        <ion-icon name="trash-outline" slot="start"></ion-icon> Delete
                      </ion-button>
                    </div>
                  </ion-card-content>
                </ion-card>
              </ion-col>
            } @empty {
              <ion-col size="12">
                <div class="empty-state">
                  <p>No plans found. Create your first billing plan.</p>
                  <ion-button (click)="onAdd()">
                    <ion-icon name="add-circle-outline" slot="start"></ion-icon> Create Plan
                  </ion-button>
                </div>
              </ion-col>
            }
          </ion-row>
        </ion-grid>
      }

      <!-- Edit/Create Panel Overlay -->
      @if (panelOpen()) {
        <div class="panel-backdrop" (click)="closePanel()"></div>
        <div class="panel-slide" [class.panel-visible]="panelOpen()">
          <div class="panel-header">
            <h2>{{ editingPlan() ? 'Edit Plan' : 'New Plan' }}</h2>
            <ion-button fill="clear" (click)="closePanel()">
              <ion-icon name="close-outline" slot="icon-only"></ion-icon>
            </ion-button>
          </div>

          <div class="panel-body">
            <!-- Preview Card -->
            <div class="panel-section">
              <div class="section-label">Preview</div>
              <div class="preview-card">
                <div class="preview-name">{{ form.name || 'Plan Name' }}</div>
                <div class="preview-badges">
                  @if (form.is_popular) {
                    <span class="badge badge-popular"><ion-icon name="star"></ion-icon> Most Popular</span>
                  }
                  <span class="badge" [class.badge-active]="form.active" [class.badge-inactive]="!form.active">
                    {{ form.active ? 'Active' : 'Inactive' }}
                  </span>
                </div>
                <div class="preview-price">
                  @if (form.price_monthly === 0) {
                    Free
                  } @else {
                    \${{ (form.price_monthly / 100) | number:'1.0-2' }}/mo
                  }
                </div>
                <div class="preview-features">
                  {{ getEnabledFeatureCount() }} features included
                </div>
              </div>
            </div>

            <!-- Basic Info -->
            <div class="panel-section">
              <div class="section-label">Basic Information</div>
              <ion-list lines="full" class="form-list">
                <ion-item>
                  <ion-input
                    label="Plan Name"
                    labelPlacement="stacked"
                    placeholder="e.g. Pro, Business, Enterprise"
                    [(ngModel)]="form.name"
                  ></ion-input>
                </ion-item>
                <ion-item>
                  <ion-input
                    label="Slug"
                    labelPlacement="stacked"
                    placeholder="e.g. pro (lowercase, unique)"
                    [(ngModel)]="form.slug"
                  ></ion-input>
                </ion-item>
                <ion-item>
                  <ion-input
                    label="Display Order"
                    labelPlacement="stacked"
                    type="number"
                    [(ngModel)]="form.display_order"
                  ></ion-input>
                </ion-item>
                <ion-item>
                  <ion-toggle [(ngModel)]="form.active" labelPlacement="start" justify="space-between">Active</ion-toggle>
                </ion-item>
                <ion-item>
                  <ion-toggle [(ngModel)]="form.is_popular" labelPlacement="start" justify="space-between">Most Popular</ion-toggle>
                </ion-item>
              </ion-list>
            </div>

            <!-- Pricing -->
            <div class="panel-section">
              <div class="section-label">Pricing</div>
              <ion-list lines="full" class="form-list">
                <ion-item>
                  <ion-input
                    label="Monthly Price (cents)"
                    labelPlacement="stacked"
                    type="number"
                    placeholder="e.g. 1500 = $15.00"
                    [(ngModel)]="form.price_monthly"
                  ></ion-input>
                </ion-item>
                <ion-item>
                  <ion-input
                    label="Yearly Price (cents)"
                    labelPlacement="stacked"
                    type="number"
                    placeholder="e.g. 15000 = $150.00"
                    [(ngModel)]="form.price_yearly"
                  ></ion-input>
                </ion-item>
              </ion-list>
            </div>

            <!-- Stripe -->
            <div class="panel-section">
              <div class="section-label">Stripe Integration</div>
              <ion-list lines="full" class="form-list">
                <ion-item>
                  <ion-input
                    label="Stripe Price ID (Monthly)"
                    labelPlacement="stacked"
                    placeholder="price_..."
                    [(ngModel)]="form.stripe_price_id_monthly"
                  ></ion-input>
                </ion-item>
                <ion-item>
                  <ion-input
                    label="Stripe Price ID (Yearly)"
                    labelPlacement="stacked"
                    placeholder="price_..."
                    [(ngModel)]="form.stripe_price_id_yearly"
                  ></ion-input>
                </ion-item>
              </ion-list>
            </div>

            <!-- Limits -->
            <div class="panel-section">
              <div class="section-label">Limits</div>
              <ion-note class="help-note">Use -1 for unlimited</ion-note>
              <ion-list lines="full" class="form-list">
                <ion-item>
                  <ion-input
                    label="Monitor Limit"
                    labelPlacement="stacked"
                    type="number"
                    [(ngModel)]="form.monitor_limit"
                  ></ion-input>
                </ion-item>
                <ion-item>
                  <ion-input
                    label="Min Check Interval (seconds)"
                    labelPlacement="stacked"
                    type="number"
                    [(ngModel)]="form.check_interval_min"
                  ></ion-input>
                </ion-item>
                <ion-item>
                  <ion-input
                    label="Team Member Limit"
                    labelPlacement="stacked"
                    type="number"
                    [(ngModel)]="form.team_member_limit"
                  ></ion-input>
                </ion-item>
                <ion-item>
                  <ion-input
                    label="Status Page Limit"
                    labelPlacement="stacked"
                    type="number"
                    [(ngModel)]="form.status_page_limit"
                  ></ion-input>
                </ion-item>
                <ion-item>
                  <ion-input
                    label="API Rate Limit (req/hr)"
                    labelPlacement="stacked"
                    type="number"
                    placeholder="0 = No API access"
                    [(ngModel)]="form.api_rate_limit"
                  ></ion-input>
                </ion-item>
                <ion-item>
                  <ion-input
                    label="Data Retention (days)"
                    labelPlacement="stacked"
                    type="number"
                    [(ngModel)]="form.data_retention_days"
                  ></ion-input>
                </ion-item>
              </ion-list>
            </div>

            <!-- Features -->
            <div class="panel-section">
              <div class="section-label">Features</div>
              <ion-list lines="full" class="form-list">
                @for (feat of allFeatures; track feat.key) {
                  <ion-item>
                    <ion-checkbox
                      [checked]="form.features[feat.key] === true"
                      (ionChange)="onFeatureToggle(feat.key, $event)"
                      labelPlacement="end"
                      justify="start"
                    >{{ feat.label }}</ion-checkbox>
                  </ion-item>
                }
              </ion-list>
            </div>
          </div>

          <div class="panel-footer">
            <ion-button fill="outline" (click)="closePanel()">Cancel</ion-button>
            <ion-button (click)="onSave()" [disabled]="saving()">
              {{ saving() ? 'Saving...' : (editingPlan() ? 'Save Changes' : 'Create Plan') }}
            </ion-button>
          </div>
        </div>
      }
    </ion-content>
  `,
  styles: [`
    /* Plan Card Grid */
    .plan-card {
      border-radius: 12px;
      border: 1px solid var(--ion-color-light-shade);
      transition: box-shadow 0.2s, border-color 0.2s;
      overflow: hidden;
    }
    .plan-card:hover {
      box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
    }
    .plan-popular {
      border-color: var(--ion-color-primary);
      box-shadow: 0 0 0 1px var(--ion-color-primary);
    }
    .plan-inactive {
      opacity: 0.65;
    }

    /* Header */
    .plan-header {
      padding: 20px 20px 12px;
      border-bottom: 1px solid var(--ion-color-light-shade);
    }
    .plan-header-top {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 8px;
    }
    .plan-name {
      font-size: 1.25rem;
      font-weight: 700;
      margin: 0;
      color: var(--ion-text-color);
    }
    .plan-badges {
      display: flex;
      gap: 6px;
      flex-shrink: 0;
      flex-wrap: wrap;
      justify-content: flex-end;
    }
    .plan-slug-line {
      font-size: 0.75rem;
      color: var(--ion-color-medium);
      margin-top: 4px;
    }

    /* Badges */
    .badge {
      display: inline-flex;
      align-items: center;
      gap: 3px;
      padding: 2px 10px;
      border-radius: 12px;
      font-size: 0.7rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.03em;
      white-space: nowrap;
    }
    .badge-active {
      background: rgba(0, 200, 83, 0.12);
      color: var(--ion-color-success-shade);
    }
    .badge-inactive {
      background: rgba(107, 114, 128, 0.12);
      color: var(--ion-color-medium);
    }
    .badge-popular {
      background: rgba(41, 121, 255, 0.12);
      color: var(--ion-color-primary);
    }
    .badge-popular ion-icon {
      font-size: 0.7rem;
    }

    /* Pricing */
    .plan-body { padding: 0 20px 16px !important; }
    .plan-pricing {
      padding: 16px 0 12px;
    }
    .price-main {
      font-family: 'DM Sans', 'Plus Jakarta Sans', system-ui, sans-serif;
      font-size: 2rem;
      font-weight: 800;
      color: var(--ion-text-color);
      line-height: 1.1;
    }
    .price-period {
      font-size: 0.9rem;
      font-weight: 500;
      color: var(--ion-color-medium);
    }
    .price-yearly {
      font-size: 0.8rem;
      color: var(--ion-color-medium);
      margin-top: 4px;
    }
    .savings-badge {
      display: inline-block;
      background: rgba(0, 200, 83, 0.12);
      color: var(--ion-color-success-shade);
      font-size: 0.7rem;
      font-weight: 600;
      padding: 1px 8px;
      border-radius: 8px;
      margin-left: 6px;
    }

    /* Sections */
    .plan-section {
      padding: 12px 0;
      border-top: 1px solid var(--ion-color-light-shade);
    }
    .section-title {
      font-size: 0.7rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      color: var(--ion-color-medium);
      margin-bottom: 10px;
    }

    /* Limits Grid */
    .limits-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px 16px;
    }
    .limit-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .limit-label {
      font-size: 0.8rem;
      color: var(--ion-color-medium-shade);
    }
    .limit-value {
      font-size: 0.8rem;
      font-weight: 600;
      color: var(--ion-text-color);
    }

    /* Features List */
    .features-list {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 4px 12px;
    }
    .feature-row {
      display: flex;
      align-items: center;
      gap: 5px;
      padding: 2px 0;
    }
    .feature-icon {
      font-size: 0.85rem;
      flex-shrink: 0;
    }
    .feature-icon.included {
      color: var(--ion-color-success);
    }
    .feature-icon.excluded {
      color: var(--ion-color-medium-tint);
    }
    .feature-name {
      font-size: 0.75rem;
    }
    .feature-included .feature-name {
      color: var(--ion-text-color);
    }
    .feature-excluded .feature-name {
      color: var(--ion-color-medium-tint);
      text-decoration: line-through;
    }

    /* Org Count */
    .plan-org-count {
      padding: 12px 0 8px;
      border-top: 1px solid var(--ion-color-light-shade);
      font-size: 0.78rem;
      color: var(--ion-color-medium);
      font-weight: 500;
    }

    /* Actions */
    .plan-actions {
      display: flex;
      gap: 0;
      border-top: 1px solid var(--ion-color-light-shade);
      padding-top: 8px;
      margin-top: 4px;
    }
    .plan-actions ion-button {
      --padding-start: 8px;
      --padding-end: 8px;
      font-size: 0.78rem;
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 4rem 1rem;
      color: var(--ion-color-medium);
    }
    .empty-state p {
      margin-bottom: 16px;
      font-size: 1rem;
    }

    /* Slide-over Panel */
    .panel-backdrop {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.4);
      z-index: 999;
      animation: fadeIn 0.2s ease;
    }
    .panel-slide {
      position: fixed;
      top: 0;
      right: 0;
      bottom: 0;
      width: 520px;
      max-width: 100vw;
      background: var(--ion-background-color);
      z-index: 1000;
      display: flex;
      flex-direction: column;
      box-shadow: -8px 0 32px rgba(0, 0, 0, 0.15);
      animation: slideIn 0.25s ease;
    }
    @keyframes slideIn {
      from { transform: translateX(100%); }
      to { transform: translateX(0); }
    }
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    .panel-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 16px 20px;
      border-bottom: 1px solid var(--ion-color-light-shade);
      flex-shrink: 0;
    }
    .panel-header h2 {
      margin: 0;
      font-size: 1.15rem;
      font-weight: 700;
      color: var(--ion-text-color);
    }
    .panel-body {
      flex: 1;
      overflow-y: auto;
      padding: 0 20px 20px;
    }
    .panel-footer {
      display: flex;
      justify-content: flex-end;
      gap: 8px;
      padding: 12px 20px;
      border-top: 1px solid var(--ion-color-light-shade);
      flex-shrink: 0;
    }

    /* Panel Sections */
    .panel-section {
      padding: 16px 0 8px;
    }
    .panel-section:not(:first-child) {
      border-top: 1px solid var(--ion-color-light-shade);
    }
    .section-label {
      font-size: 0.72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      color: var(--ion-color-medium);
      margin-bottom: 8px;
    }
    .help-note {
      display: block;
      font-size: 0.72rem;
      margin-bottom: 6px;
    }
    .form-list {
      border-radius: 8px;
      overflow: hidden;
    }
    .form-list ion-item {
      --padding-start: 0;
    }

    /* Preview Card */
    .preview-card {
      background: var(--ion-color-light);
      border-radius: 10px;
      padding: 16px;
      border: 1px solid var(--ion-color-light-shade);
    }
    .preview-name {
      font-size: 1.05rem;
      font-weight: 700;
      color: var(--ion-text-color);
      margin-bottom: 6px;
    }
    .preview-badges {
      display: flex;
      gap: 6px;
      margin-bottom: 8px;
    }
    .preview-price {
      font-family: 'DM Sans', 'Plus Jakarta Sans', system-ui, sans-serif;
      font-size: 1.5rem;
      font-weight: 800;
      color: var(--ion-text-color);
    }
    .preview-features {
      font-size: 0.75rem;
      color: var(--ion-color-medium);
      margin-top: 4px;
    }

    /* Responsive */
    @media (max-width: 576px) {
      .panel-slide {
        width: 100vw;
      }
      .features-list {
        grid-template-columns: 1fr;
      }
      .limits-grid {
        grid-template-columns: 1fr;
      }
    }
  `],
})
export class SuperAdminPlansComponent implements OnInit {
  plans = signal<Plan[]>([]);
  loading = signal(true);
  panelOpen = signal(false);
  editingPlan = signal<Plan | null>(null);
  saving = signal(false);
  allFeatures = ALL_FEATURES;

  form: PlanForm = this.getEmptyForm();

  constructor(
    private api: ApiService,
    private alertCtrl: AlertController,
    private toastCtrl: ToastController,
  ) {}

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.api.get<any>('/super-admin/plans').subscribe({
      next: (data) => {
        this.plans.set(data.items);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  onRefresh(event: any): void {
    this.api.get<any>('/super-admin/plans').subscribe({
      next: (data) => {
        this.plans.set(data.items);
        event.target.complete();
      },
      error: () => event.target.complete(),
    });
  }

  // --- Formatting helpers ---

  formatLimit(value: number): string {
    return value === -1 ? 'Unlimited' : value.toLocaleString();
  }

  formatInterval(seconds: number): string {
    if (seconds < 60) {
      return `${seconds} second${seconds !== 1 ? 's' : ''}`;
    }
    const minutes = Math.floor(seconds / 60);
    return `${minutes} minute${minutes !== 1 ? 's' : ''}`;
  }

  formatApiRate(rate: number): string {
    if (rate <= 0) {
      return 'No API access';
    }
    return `${rate.toLocaleString()} req/hr`;
  }

  formatRetention(days: number): string {
    if (days === -1) {
      return 'Unlimited';
    }
    if (days >= 365) {
      const years = Math.floor(days / 365);
      return `${years} year${years !== 1 ? 's' : ''}`;
    }
    return `${days} day${days !== 1 ? 's' : ''}`;
  }

  getYearlySavings(plan: Plan): number {
    if (plan.price_monthly === 0 || plan.price_yearly === 0) {
      return 0;
    }
    const monthlyTotal = plan.price_monthly * 12;
    const savings = ((monthlyTotal - plan.price_yearly) / monthlyTotal) * 100;
    return Math.round(Math.max(savings, 0));
  }

  hasFeature(plan: Plan, key: string): boolean {
    try {
      const features = typeof plan.features === 'string' ? JSON.parse(plan.features) : plan.features;
      if (Array.isArray(features)) {
        return features.includes(key);
      }
      if (typeof features === 'object' && features !== null) {
        return features[key] === true;
      }
      return false;
    } catch {
      return false;
    }
  }

  parseFeatures(plan: Plan): Record<string, boolean> {
    const result: Record<string, boolean> = {};
    ALL_FEATURES.forEach(f => result[f.key] = false);
    try {
      const features = typeof plan.features === 'string' ? JSON.parse(plan.features) : plan.features;
      if (Array.isArray(features)) {
        features.forEach((k: string) => { if (k in result) result[k] = true; });
      } else if (typeof features === 'object' && features !== null) {
        Object.entries(features).forEach(([k, v]) => { if (k in result) result[k] = v as boolean; });
      }
    } catch { /* ignore parse errors */ }
    return result;
  }

  getEnabledFeatureCount(): number {
    return Object.values(this.form.features).filter(v => v).length;
  }

  // --- Panel operations ---

  getEmptyForm(): PlanForm {
    const features: Record<string, boolean> = {};
    ALL_FEATURES.forEach(f => features[f.key] = false);
    return {
      name: '',
      slug: '',
      price_monthly: 0,
      price_yearly: 0,
      monitor_limit: 50,
      check_interval_min: 60,
      team_member_limit: 5,
      status_page_limit: 3,
      api_rate_limit: 100,
      data_retention_days: 90,
      display_order: 10,
      active: true,
      is_popular: false,
      stripe_price_id_monthly: '',
      stripe_price_id_yearly: '',
      features,
    };
  }

  openPanel(plan?: Plan): void {
    if (plan) {
      this.editingPlan.set(plan);
      this.form = {
        name: plan.name,
        slug: plan.slug,
        price_monthly: plan.price_monthly,
        price_yearly: plan.price_yearly,
        monitor_limit: plan.monitor_limit,
        check_interval_min: plan.check_interval_min,
        team_member_limit: plan.team_member_limit,
        status_page_limit: plan.status_page_limit,
        api_rate_limit: plan.api_rate_limit,
        data_retention_days: plan.data_retention_days,
        display_order: plan.display_order,
        active: plan.active,
        is_popular: plan.is_popular || false,
        stripe_price_id_monthly: plan.stripe_price_id_monthly || '',
        stripe_price_id_yearly: plan.stripe_price_id_yearly || '',
        features: this.parseFeatures(plan),
      };
    } else {
      this.editingPlan.set(null);
      this.form = this.getEmptyForm();
    }
    this.panelOpen.set(true);
  }

  closePanel(): void {
    this.panelOpen.set(false);
    this.editingPlan.set(null);
  }

  onFeatureToggle(key: string, event: any): void {
    this.form.features[key] = event.detail.checked;
  }

  onAdd(): void {
    this.openPanel();
  }

  onEdit(plan: Plan): void {
    this.openPanel(plan);
  }

  buildPayload(): Record<string, any> {
    // Build features as object { key: true/false }
    const featuresObj: Record<string, boolean> = {};
    ALL_FEATURES.forEach(f => {
      featuresObj[f.key] = this.form.features[f.key] || false;
    });

    return {
      name: this.form.name,
      slug: this.form.slug,
      price_monthly: Number(this.form.price_monthly) || 0,
      price_yearly: Number(this.form.price_yearly) || 0,
      monitor_limit: Number(this.form.monitor_limit),
      check_interval_min: Number(this.form.check_interval_min),
      team_member_limit: Number(this.form.team_member_limit),
      status_page_limit: Number(this.form.status_page_limit),
      api_rate_limit: Number(this.form.api_rate_limit),
      data_retention_days: Number(this.form.data_retention_days),
      display_order: Number(this.form.display_order),
      active: this.form.active,
      is_popular: this.form.is_popular,
      stripe_price_id_monthly: this.form.stripe_price_id_monthly || null,
      stripe_price_id_yearly: this.form.stripe_price_id_yearly || null,
      features: featuresObj,
    };
  }

  async onSave(): Promise<void> {
    if (!this.form.name || !this.form.slug) {
      const toast = await this.toastCtrl.create({
        message: 'Name and slug are required',
        color: 'warning',
        duration: 3000,
        position: 'bottom',
      });
      await toast.present();
      return;
    }

    this.saving.set(true);
    const payload = this.buildPayload();
    const editing = this.editingPlan();

    const request$ = editing
      ? this.api.put<any>(`/super-admin/plans/${editing.id}`, payload)
      : this.api.post<any>('/super-admin/plans', payload);

    request$.subscribe({
      next: async () => {
        this.saving.set(false);
        this.closePanel();
        this.load();
        const toast = await this.toastCtrl.create({
          message: editing ? 'Plan updated' : 'Plan created',
          color: 'success',
          duration: 2000,
          position: 'bottom',
        });
        await toast.present();
      },
      error: async (err: any) => {
        this.saving.set(false);
        const toast = await this.toastCtrl.create({
          message: err?.message || (editing ? 'Failed to update plan' : 'Failed to create plan'),
          color: 'danger',
          duration: 4000,
          position: 'bottom',
        });
        await toast.present();
      },
    });
  }

  async onDuplicate(plan: Plan): Promise<void> {
    this.api.post<any>(`/super-admin/plans/${plan.id}/duplicate`).subscribe({
      next: async () => {
        this.load();
        const toast = await this.toastCtrl.create({
          message: `Duplicated "${plan.name}"`,
          color: 'success',
          duration: 2000,
          position: 'bottom',
        });
        await toast.present();
      },
      error: async (err: any) => {
        const toast = await this.toastCtrl.create({
          message: err?.message || 'Failed to duplicate',
          color: 'danger',
          duration: 4000,
          position: 'bottom',
        });
        await toast.present();
      },
    });
  }

  async onDelete(plan: Plan): Promise<void> {
    const alert = await this.alertCtrl.create({
      header: 'Delete Plan',
      message: `Delete "${plan.name}"? This cannot be undone.${
        (plan.organization_count || 0) > 0
          ? ` Warning: ${plan.organization_count} organization(s) are currently on this plan.`
          : ''
      }`,
      buttons: [
        { text: 'Cancel', role: 'cancel' },
        {
          text: 'Delete',
          role: 'destructive',
          handler: () => {
            this.api.delete<any>(`/super-admin/plans/${plan.id}`).subscribe({
              next: async () => {
                this.plans.update((list) => list.filter((p) => p.id !== plan.id));
                const toast = await this.toastCtrl.create({
                  message: 'Plan deleted',
                  color: 'success',
                  duration: 2000,
                  position: 'bottom',
                });
                await toast.present();
              },
              error: async (err: any) => {
                const toast = await this.toastCtrl.create({
                  message: err?.message || 'Failed to delete',
                  color: 'danger',
                  duration: 4000,
                  position: 'bottom',
                });
                await toast.present();
              },
            });
          },
        },
      ],
    });
    await alert.present();
  }
}
