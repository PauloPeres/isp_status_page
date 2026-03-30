import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons,
  IonCard, IonCardHeader, IonCardTitle, IonCardContent,
  IonGrid, IonRow, IonCol, IonIcon,
  IonSkeletonText, IonList, IonItem, IonLabel, IonNote,
  IonRefresher, IonRefresherContent,
  ToastController,
} from '@ionic/angular/standalone';
import { ApiService } from '../../core/services/api.service';
import { addIcons } from 'ionicons';
import { cashOutline, trendingUpOutline, warningOutline } from 'ionicons/icons';

addIcons({ cashOutline, trendingUpOutline, warningOutline });

@Component({
  selector: 'app-super-admin-revenue',
  standalone: true,
  imports: [
    CommonModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons,
    IonCard, IonCardHeader, IonCardTitle, IonCardContent,
    IonGrid, IonRow, IonCol, IonIcon,
    IonSkeletonText, IonList, IonItem, IonLabel, IonNote,
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
        <ion-grid>
          <ion-row>
            @for (i of [1,2]; track i) {
              <ion-col size="6">
                <ion-card>
                  <ion-card-content style="text-align: center; padding: 20px 8px;">
                    <ion-skeleton-text [animated]="true" style="width: 40px; height: 40px; margin: 0 auto; border-radius: 50%;"></ion-skeleton-text>
                    <ion-skeleton-text [animated]="true" style="width: 60%; height: 2rem; margin: 12px auto 4px;"></ion-skeleton-text>
                    <ion-skeleton-text [animated]="true" style="width: 50%; height: 0.75rem; margin: 0 auto;"></ion-skeleton-text>
                  </ion-card-content>
                </ion-card>
              </ion-col>
            }
          </ion-row>
        </ion-grid>
      } @else if (error()) {
        <ion-card>
          <ion-card-content style="text-align: center; padding: 3rem 1rem;">
            <ion-icon name="warning-outline" style="font-size: 48px; color: var(--ion-color-warning)"></ion-icon>
            <h2 style="margin: 1rem 0 0.5rem;">Revenue metrics are being configured</h2>
            <p style="color: var(--ion-color-medium);">The billing service is not yet available. Revenue data will appear here once it is set up.</p>
          </ion-card-content>
        </ion-card>
      } @else if (data()) {
        <ion-grid>
          <ion-row>
            @if (data()?.revenue?.mrr !== undefined) {
              <ion-col size="6">
                <ion-card class="kpi-card">
                  <ion-card-content>
                    <ion-icon name="cash-outline" style="font-size: 2rem; color: var(--ion-color-success)"></ion-icon>
                    <div class="kpi-value">\${{ data()!.revenue.mrr | number:'1.0-0' }}</div>
                    <div class="kpi-label">MRR</div>
                  </ion-card-content>
                </ion-card>
              </ion-col>
            }
            @if (data()?.revenue?.arr !== undefined) {
              <ion-col size="6">
                <ion-card class="kpi-card">
                  <ion-card-content>
                    <ion-icon name="trending-up-outline" style="font-size: 2rem; color: var(--ion-color-primary)"></ion-icon>
                    <div class="kpi-value">\${{ data()!.revenue.arr | number:'1.0-0' }}</div>
                    <div class="kpi-label">ARR</div>
                  </ion-card-content>
                </ion-card>
              </ion-col>
            }
          </ion-row>
        </ion-grid>

        @if (data()?.revenue?.plan_counts) {
          <ion-card>
            <ion-card-header>
              <ion-card-title>Plan Distribution</ion-card-title>
            </ion-card-header>
            <ion-card-content>
              <ion-list>
                @for (entry of revenueEntries(); track entry.key) {
                  <ion-item>
                    <ion-label>{{ entry.key }}</ion-label>
                    <ion-note slot="end">{{ entry.value }}</ion-note>
                  </ion-item>
                }
              </ion-list>
            </ion-card-content>
          </ion-card>
        }

        @if (!data()?.revenue?.mrr && !data()?.revenue?.arr && !data()?.revenue?.plan_counts) {
          <ion-card>
            <ion-card-content style="text-align: center; padding: 2rem;">
              <ion-icon name="cash-outline" style="font-size: 48px; color: var(--ion-color-medium)"></ion-icon>
              <h3 style="color: var(--ion-color-medium);">Revenue data loaded</h3>
              <p style="color: var(--ion-color-medium); font-size: 0.85rem;">
                No specific MRR/ARR metrics found in the response.
              </p>
            </ion-card-content>
          </ion-card>
        }
      }
    </ion-content>
  `,
  styles: [`
    .kpi-card ion-card-content { text-align: center; padding: 20px 8px; }
    .kpi-value { font-size: 2rem; font-weight: 700; margin: 8px 0 2px; }
    .kpi-label { font-size: 0.75rem; color: var(--ion-color-medium); text-transform: uppercase; letter-spacing: 0.5px; }
  `],
})
export class SuperAdminRevenueComponent implements OnInit {
  data = signal<any>(null);
  loading = signal(true);
  error = signal(false);

  constructor(private api: ApiService, private toastCtrl: ToastController) {}

  ngOnInit(): void { this.load(); }

  load(): void {
    this.loading.set(true);
    this.error.set(false);
    this.api.get<any>('/super-admin/revenue').subscribe({
      next: (d) => { this.data.set(d); this.loading.set(false); },
      error: () => {
        this.loading.set(false);
        this.error.set(true);
      },
    });
  }

  onRefresh(event: any): void {
    this.error.set(false);
    this.api.get<any>('/super-admin/revenue').subscribe({
      next: (d) => { this.data.set(d); event.target.complete(); },
      error: () => { this.error.set(true); event.target.complete(); },
    });
  }

  revenueEntries(): { key: string; value: any }[] {
    const planCounts = this.data()?.revenue?.plan_counts;
    if (!planCounts || typeof planCounts !== 'object') return [];
    return Object.entries(planCounts).map(([key, value]) => ({ key, value }));
  }
}
