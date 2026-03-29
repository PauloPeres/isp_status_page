import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
  IonList, IonItem, IonLabel, IonBadge, IonCard, IonCardHeader, IonCardTitle,
  IonCardContent, IonIcon, IonNote, IonSpinner,
  IonRefresher, IonRefresherContent,
  ToastController,
} from '@ionic/angular/standalone';
import { BillingService, Plan, CreditBalance } from './billing.service';
import { addIcons } from 'ionicons';
import { walletOutline, checkmarkCircle } from 'ionicons/icons';

addIcons({ walletOutline, checkmarkCircle });

@Component({
  selector: 'app-billing',
  standalone: true,
  imports: [
    CommonModule, FormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
    IonList, IonItem, IonLabel, IonBadge, IonCard, IonCardHeader, IonCardTitle,
    IonCardContent, IonIcon, IonNote, IonSpinner,
    IonRefresher, IonRefresherContent,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>Billing</ion-title>
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
        <!-- Credit Balance -->
        <ion-card>
          <ion-card-header>
            <ion-card-title>
              <ion-icon name="wallet-outline" style="margin-right: 8px; vertical-align: middle"></ion-icon>
              Credit Balance
            </ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <h1 style="font-size: 2rem; margin: 0.5rem 0">{{ credits()?.credits || 0 }} credits</h1>
            @if (credits()?.last_purchase_at) {
              <ion-note>Last purchase: {{ credits()!.last_purchase_at }}</ion-note>
            }
            <div style="margin-top: 1rem">
              <ion-button fill="outline" (click)="onBuyCredits()" [disabled]="purchasing()">
                @if (purchasing()) {
                  <ion-spinner name="crescent" style="width: 16px; height: 16px"></ion-spinner>
                } @else {
                  Buy Credits
                }
              </ion-button>
            </div>
          </ion-card-content>
        </ion-card>

        <!-- Plans -->
        <h2 style="padding: 0 16px; margin-top: 1.5rem">Plans</h2>
        @for (plan of plans(); track plan.id) {
          <ion-card [class.current-plan]="plan.is_current">
            <ion-card-header>
              <ion-card-title>
                {{ plan.name }}
                @if (plan.is_current) {
                  <ion-badge color="primary" style="margin-left: 8px">Current</ion-badge>
                }
              </ion-card-title>
            </ion-card-header>
            <ion-card-content>
              <h2 style="margin: 0">
                @if (plan.price === 0) {
                  Free
                } @else {
                  \${{ plan.price }}/{{ plan.interval }}
                }
              </h2>
              <p style="color: var(--ion-color-medium)">Up to {{ plan.monitor_limit }} monitors</p>
              <ion-list lines="none" style="padding: 0">
                @for (feature of plan.features; track feature) {
                  <ion-item style="--min-height: 28px; --padding-start: 0">
                    <ion-icon name="checkmark-circle" color="success" slot="start" style="font-size: 16px; margin-right: 8px"></ion-icon>
                    <ion-label style="font-size: 0.85rem">{{ feature }}</ion-label>
                  </ion-item>
                }
              </ion-list>
              @if (!plan.is_current) {
                <ion-button expand="block" fill="outline" style="margin-top: 12px" (click)="onUpgrade(plan)">
                  {{ plan.price === 0 ? 'Downgrade' : 'Upgrade' }}
                </ion-button>
              }
            </ion-card-content>
          </ion-card>
        }
      }
    </ion-content>
  `,
  styles: [`
    .current-plan { border: 2px solid var(--ion-color-primary); }
  `],
})
export class BillingComponent implements OnInit {
  plans = signal<Plan[]>([]);
  credits = signal<CreditBalance | null>(null);
  loading = signal(false);
  purchasing = signal(false);

  constructor(private service: BillingService, private toastCtrl: ToastController) {}

  ngOnInit(): void { this.loadAll(); }

  loadAll(): void {
    this.loading.set(true);
    this.service.getPlans().subscribe({
      next: (plans) => { this.plans.set(plans); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
    this.service.getCredits().subscribe((c) => this.credits.set(c));
  }

  onRefresh(event: any): void {
    this.service.getPlans().subscribe({
      next: (plans) => {
        this.plans.set(plans);
        this.service.getCredits().subscribe((c) => this.credits.set(c));
        event.target.complete();
      },
      error: () => event.target.complete(),
    });
  }

  onUpgrade(plan: Plan): void {
    this.service.checkout(plan.id).subscribe({
      next: (res) => { window.location.href = res.checkout_url; },
      error: async () => {
        const toast = await this.toastCtrl.create({ message: 'Checkout failed', color: 'danger', duration: 3000, position: 'bottom' });
        await toast.present();
      },
    });
  }

  onBuyCredits(): void {
    this.purchasing.set(true);
    this.service.buyCredits(100).subscribe({
      next: (res) => { this.purchasing.set(false); window.location.href = res.checkout_url; },
      error: async () => {
        this.purchasing.set(false);
        const toast = await this.toastCtrl.create({ message: 'Purchase failed', color: 'danger', duration: 3000, position: 'bottom' });
        await toast.present();
      },
    });
  }
}
