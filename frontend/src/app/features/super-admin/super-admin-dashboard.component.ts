import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons,
  IonCard, IonCardHeader, IonCardTitle, IonCardContent,
  IonGrid, IonRow, IonCol, IonBadge, IonIcon, IonSpinner,
  IonRefresher, IonRefresherContent,
} from '@ionic/angular/standalone';
import { SuperAdminService, AdminDashboard } from './super-admin.service';
import { addIcons } from 'ionicons';
import { businessOutline, peopleOutline, pulseOutline, cashOutline } from 'ionicons/icons';

addIcons({ businessOutline, peopleOutline, pulseOutline, cashOutline });

@Component({
  selector: 'app-super-admin-dashboard',
  standalone: true,
  imports: [
    CommonModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons,
    IonCard, IonCardHeader, IonCardTitle, IonCardContent,
    IonGrid, IonRow, IonCol, IonBadge, IonIcon, IonSpinner,
    IonRefresher, IonRefresherContent,
  ],
  template: `
    <ion-header>
      <ion-toolbar color="dark">
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>Super Admin Dashboard</ion-title>
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
            <ion-col size="6" size-md="3">
              <ion-card class="kpi-card">
                <ion-card-content>
                  <ion-icon name="cash-outline" style="font-size: 24px; color: var(--ion-color-success)"></ion-icon>
                  <div class="kpi-value">\${{ data()!.mrr | number:'1.0-0' }}</div>
                  <div class="kpi-label">MRR</div>
                </ion-card-content>
              </ion-card>
            </ion-col>
            <ion-col size="6" size-md="3">
              <ion-card class="kpi-card">
                <ion-card-content>
                  <ion-icon name="business-outline" style="font-size: 24px; color: var(--ion-color-primary)"></ion-icon>
                  <div class="kpi-value">{{ data()!.total_organizations }}</div>
                  <div class="kpi-label">Organizations</div>
                </ion-card-content>
              </ion-card>
            </ion-col>
            <ion-col size="6" size-md="3">
              <ion-card class="kpi-card">
                <ion-card-content>
                  <ion-icon name="people-outline" style="font-size: 24px; color: var(--ion-color-secondary)"></ion-icon>
                  <div class="kpi-value">{{ data()!.total_users }}</div>
                  <div class="kpi-label">Users</div>
                </ion-card-content>
              </ion-card>
            </ion-col>
            <ion-col size="6" size-md="3">
              <ion-card class="kpi-card">
                <ion-card-content>
                  <ion-icon name="pulse-outline" style="font-size: 24px; color: var(--ion-color-tertiary)"></ion-icon>
                  <div class="kpi-value">{{ data()!.total_monitors }}</div>
                  <div class="kpi-label">Monitors</div>
                </ion-card-content>
              </ion-card>
            </ion-col>
          </ion-row>
        </ion-grid>

        <!-- Plan Distribution -->
        <ion-card>
          <ion-card-header>
            <ion-card-title>Plan Distribution</ion-card-title>
          </ion-card-header>
          <ion-card-content>
            @for (plan of data()!.plan_distribution; track plan.plan) {
              <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--ion-color-light)">
                <ion-badge [color]="getPlanColor(plan.plan)">{{ plan.plan }}</ion-badge>
                <span style="font-weight: 600">{{ plan.count }} orgs</span>
              </div>
            }
          </ion-card-content>
        </ion-card>

        <ion-card>
          <ion-card-content>
            <div style="display: flex; justify-content: space-between">
              <div><strong>Active Incidents:</strong></div>
              <ion-badge [color]="data()!.active_incidents > 0 ? 'danger' : 'success'">
                {{ data()!.active_incidents }}
              </ion-badge>
            </div>
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
export class SuperAdminDashboardComponent implements OnInit {
  data = signal<AdminDashboard | null>(null);
  loading = signal(true);

  constructor(private service: SuperAdminService) {}

  ngOnInit(): void { this.load(); }

  load(): void {
    this.service.getDashboard().subscribe({
      next: (d) => { this.data.set(d); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }

  onRefresh(event: any): void {
    this.service.getDashboard().subscribe({
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
