import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
  IonList, IonItem, IonLabel, IonBadge, IonCard, IonCardHeader, IonCardTitle,
  IonCardContent, IonIcon, IonNote, IonSpinner, IonChip, IonGrid, IonRow, IonCol,
  IonRefresher, IonRefresherContent,
  ToastController,
} from '@ionic/angular/standalone';
import { BillingService, Plan, CreditBalance } from './billing.service';
import { addIcons } from 'ionicons';
import { walletOutline, checkmarkCircleOutline, starOutline, rocketOutline } from 'ionicons/icons';

addIcons({ walletOutline, checkmarkCircleOutline, starOutline, rocketOutline });

@Component({
  selector: 'app-billing',
  standalone: true,
  imports: [
    CommonModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
    IonList, IonItem, IonLabel, IonBadge, IonCard, IonCardHeader, IonCardTitle,
    IonCardContent, IonIcon, IonNote, IonSpinner, IonChip, IonGrid, IonRow, IonCol,
    IonRefresher, IonRefresherContent,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>Billing & Plans</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <ion-refresher slot="fixed" (ionRefresh)="onRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

      @if (loading()) {
        <div style="text-align: center; padding: 2rem">
          <ion-spinner name="crescent"></ion-spinner>
        </div>
      } @else {
        <!-- Credit Balance Card -->
        <ion-card>
          <ion-card-header>
            <ion-card-title>
              <ion-icon name="wallet-outline" style="margin-right: 8px; vertical-align: middle"></ion-icon>
              Notification Credits
            </ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <div style="display: flex; align-items: baseline; gap: 8px">
              <span style="font-size: 2.5rem; font-weight: 700; font-family: 'DM Sans', sans-serif">{{ credits()?.balance ?? 0 }}</span>
              <span style="color: var(--ion-color-medium)">credits remaining</span>
            </div>
            @if (credits()?.monthly_grant) {
              <p style="color: var(--ion-color-medium); margin-top: 4px">
                {{ credits()!.monthly_grant }} credits/month included in your plan
              </p>
            }
          </ion-card-content>
        </ion-card>

        <!-- Plans -->
        <h2 style="padding: 0 4px; margin: 1.5rem 0 0.75rem; font-family: 'DM Sans', sans-serif">Plans</h2>

        @for (plan of plans(); track plan.id) {
          <ion-card [style.border]="plan.is_current ? '2px solid var(--ion-color-primary)' : 'none'">
            <ion-card-header>
              <div style="display: flex; justify-content: space-between; align-items: center">
                <ion-card-title>
                  {{ plan.name }}
                </ion-card-title>
                @if (plan.is_current) {
                  <ion-badge color="primary">Current Plan</ion-badge>
                }
              </div>
            </ion-card-header>
            <ion-card-content>
              <div style="margin-bottom: 12px">
                <span style="font-size: 1.75rem; font-weight: 700; font-family: 'DM Sans', sans-serif">
                  {{ plan.formatted_price }}
                </span>
              </div>

              <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px">
                <ion-chip color="medium" style="height: 24px; font-size: 0.75rem">
                  {{ plan.monitor_limit === -1 ? 'Unlimited' : plan.monitor_limit }} monitors
                </ion-chip>
                <ion-chip color="medium" style="height: 24px; font-size: 0.75rem">
                  {{ plan.team_member_limit === -1 ? 'Unlimited' : plan.team_member_limit }} team members
                </ion-chip>
                <ion-chip color="medium" style="height: 24px; font-size: 0.75rem">
                  {{ plan.data_retention_days }} days retention
                </ion-chip>
              </div>

              @if (plan.parsed_features && plan.parsed_features.length > 0) {
                <ion-list lines="none" style="padding: 0">
                  @for (feature of plan.parsed_features; track feature) {
                    <ion-item style="--min-height: 28px; --padding-start: 0; font-size: 0.85rem">
                      <ion-icon name="checkmark-circle-outline" color="success" slot="start" style="font-size: 16px; margin-right: 8px; margin-inline-end: 8px"></ion-icon>
                      <ion-label>{{ feature }}</ion-label>
                    </ion-item>
                  }
                </ion-list>
              }

              @if (!plan.is_current && !plan.is_free) {
                <ion-button expand="block" fill="outline" style="margin-top: 12px" (click)="onUpgrade(plan)">
                  Upgrade to {{ plan.name }}
                </ion-button>
              }
            </ion-card-content>
          </ion-card>
        } @empty {
          <ion-card>
            <ion-card-content style="text-align: center; padding: 2rem">
              <p style="color: var(--ion-color-medium)">No plans available</p>
            </ion-card-content>
          </ion-card>
        }
      }
    </ion-content>
  `,
})
export class BillingComponent implements OnInit {
  plans = signal<Plan[]>([]);
  credits = signal<CreditBalance | null>(null);
  loading = signal(true);

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
  }

  onRefresh(event: any): void {
    this.loadAll();
    setTimeout(() => event.target.complete(), 1000);
  }

  async onUpgrade(plan: Plan): Promise<void> {
    this.service.checkout(plan.slug).subscribe({
      next: (res: any) => {
        if (res?.checkout_url) window.location.href = res.checkout_url;
      },
      error: async () => {
        const toast = await this.toastCtrl.create({ message: 'Stripe is not configured. Contact support.', color: 'warning', duration: 3000, position: 'bottom' });
        await toast.present();
      },
    });
  }
}
