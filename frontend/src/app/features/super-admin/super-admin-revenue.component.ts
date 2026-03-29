import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons,
  IonCard, IonCardHeader, IonCardTitle, IonCardContent,
  IonGrid, IonRow, IonCol, IonBadge, IonIcon, IonSpinner,
  IonList, IonItem, IonLabel, IonNote,
  IonRefresher, IonRefresherContent,
} from '@ionic/angular/standalone';
import { SuperAdminService, AdminRevenue } from './super-admin.service';
import { addIcons } from 'ionicons';
import { cashOutline, trendingUpOutline } from 'ionicons/icons';

addIcons({ cashOutline, trendingUpOutline });

@Component({
  selector: 'app-super-admin-revenue',
  standalone: true,
  imports: [
    CommonModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons,
    IonCard, IonCardHeader, IonCardTitle, IonCardContent,
    IonGrid, IonRow, IonCol, IonBadge, IonIcon, IonSpinner,
    IonList, IonItem, IonLabel, IonNote,
    IonRefresher, IonRefresherContent,
  ],
  template: `
    <ion-header>
      <ion-toolbar color="dark">
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>Revenue</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content>
      <ion-refresher slot="fixed" (ionRefresh)="onRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

      @if (loading()) {
        <div style="text-align: center; padding: 3rem"><ion-spinner name="crescent"></ion-spinner></div>
      } @else if (data()) {
        <ion-grid>
          <ion-row>
            <ion-col size="6">
              <ion-card class="kpi-card">
                <ion-card-content>
                  <ion-icon name="cash-outline" style="font-size: 24px; color: var(--ion-color-success)"></ion-icon>
                  <div class="kpi-value">\${{ data()!.mrr | number:'1.0-0' }}</div>
                  <div class="kpi-label">MRR</div>
                </ion-card-content>
              </ion-card>
            </ion-col>
            <ion-col size="6">
              <ion-card class="kpi-card">
                <ion-card-content>
                  <ion-icon name="trending-up-outline" style="font-size: 24px; color: var(--ion-color-primary)"></ion-icon>
                  <div class="kpi-value">\${{ data()!.arr | number:'1.0-0' }}</div>
                  <div class="kpi-label">ARR</div>
                </ion-card-content>
              </ion-card>
            </ion-col>
          </ion-row>
        </ion-grid>

        <ion-card>
          <ion-card-header>
            <ion-card-title>Plan Breakdown</ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <ion-list>
              @for (plan of data()!.plan_breakdown; track plan.plan) {
                <ion-item>
                  <ion-badge slot="start" [color]="getPlanColor(plan.plan)">{{ plan.plan }}</ion-badge>
                  <ion-label>{{ plan.count }} orgs</ion-label>
                  <ion-note slot="end">\${{ plan.revenue | number:'1.0-0' }}/mo</ion-note>
                </ion-item>
              }
            </ion-list>
          </ion-card-content>
        </ion-card>

        <ion-card>
          <ion-card-header>
            <ion-card-title>Monthly Trend</ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <ion-list>
              @for (month of data()!.monthly_trend; track month.month) {
                <ion-item>
                  <ion-label>{{ month.month }}</ion-label>
                  <ion-note slot="end">\${{ month.mrr | number:'1.0-0' }}</ion-note>
                </ion-item>
              }
            </ion-list>
          </ion-card-content>
        </ion-card>
      }
    </ion-content>
  `,
  styles: [`
    .kpi-card ion-card-content { text-align: center; padding: 12px 8px; }
    .kpi-value { font-size: 1.5rem; font-weight: 700; margin: 4px 0; }
    .kpi-label { font-size: 0.75rem; color: var(--ion-color-medium); text-transform: uppercase; }
  `],
})
export class SuperAdminRevenueComponent implements OnInit {
  data = signal<AdminRevenue | null>(null);
  loading = signal(true);

  constructor(private service: SuperAdminService) {}

  ngOnInit(): void { this.load(); }

  load(): void {
    this.service.getRevenue().subscribe({
      next: (d) => { this.data.set(d); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }

  onRefresh(event: any): void {
    this.service.getRevenue().subscribe({
      next: (d) => { this.data.set(d); event.target.complete(); },
      error: () => event.target.complete(),
    });
  }

  getPlanColor(plan: string): string {
    switch (plan.toLowerCase()) {
      case 'free': return 'medium';
      case 'pro': return 'primary';
      case 'business': return 'success';
      case 'enterprise': return 'tertiary';
      default: return 'medium';
    }
  }
}
