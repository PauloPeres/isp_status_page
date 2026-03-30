import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons,
  IonCard, IonCardHeader, IonCardTitle, IonCardContent,
  IonList, IonItem, IonLabel, IonBadge, IonNote, IonIcon,
  IonSkeletonText, IonGrid, IonRow, IonCol,
  IonRefresher, IonRefresherContent,
  ToastController,
} from '@ionic/angular/standalone';
import { ApiService } from '../../core/services/api.service';
import { addIcons } from 'ionicons';
import {
  serverOutline, heartOutline, checkmarkCircleOutline, closeCircleOutline,
} from 'ionicons/icons';

addIcons({ serverOutline, heartOutline, checkmarkCircleOutline, closeCircleOutline });

interface HealthCheck {
  healthy: boolean;
  message: string;
  free_gb?: number;
}

interface HealthData {
  healthy: boolean;
  checks: {
    database: HealthCheck;
    cache: HealthCheck;
    disk: HealthCheck;
  };
}

@Component({
  selector: 'app-super-admin-health',
  standalone: true,
  imports: [
    CommonModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons,
    IonCard, IonCardHeader, IonCardTitle, IonCardContent,
    IonList, IonItem, IonLabel, IonBadge, IonNote, IonIcon,
    IonSkeletonText, IonGrid, IonRow, IonCol,
    IonRefresher, IonRefresherContent,
  ],
  template: `
    <ion-header>
      <ion-toolbar color="dark">
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>Platform Health</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content>
      <ion-refresher slot="fixed" (ionRefresh)="onRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

      @if (loading()) {
        <div style="padding: 16px;">
          <ion-card>
            <ion-card-content style="text-align: center; padding: 24px;">
              <ion-skeleton-text [animated]="true" style="width: 120px; height: 2rem; margin: 0 auto 8px;"></ion-skeleton-text>
              <ion-skeleton-text [animated]="true" style="width: 80px; height: 1rem; margin: 0 auto;"></ion-skeleton-text>
            </ion-card-content>
          </ion-card>
          <ion-grid>
            <ion-row>
              @for (i of [1,2,3]; track i) {
                <ion-col size="12" size-md="4">
                  <ion-card>
                    <ion-card-content>
                      <ion-skeleton-text [animated]="true" style="width: 50%; height: 1.2rem; margin-bottom: 12px;"></ion-skeleton-text>
                      <ion-skeleton-text [animated]="true" style="width: 70%; height: 0.85rem; margin-bottom: 8px;"></ion-skeleton-text>
                      <ion-skeleton-text [animated]="true" style="width: 40%; height: 0.85rem;"></ion-skeleton-text>
                    </ion-card-content>
                  </ion-card>
                </ion-col>
              }
            </ion-row>
          </ion-grid>
        </div>
      } @else if (data()) {
        <!-- Overall Health Badge -->
        <ion-card>
          <ion-card-content style="text-align: center; padding: 24px;">
            <ion-icon
              [name]="data()!.healthy ? 'checkmark-circle-outline' : 'close-circle-outline'"
              [style.font-size]="'3rem'"
              [style.color]="data()!.healthy ? 'var(--ion-color-success)' : 'var(--ion-color-danger)'"
            ></ion-icon>
            <h1 style="margin: 8px 0 0; font-weight: 700;">
              <ion-badge [color]="data()!.healthy ? 'success' : 'danger'" style="font-size: 1.1rem; padding: 8px 20px;">
                {{ data()!.healthy ? 'Healthy' : 'Unhealthy' }}
              </ion-badge>
            </h1>
          </ion-card-content>
        </ion-card>

        <!-- Individual Health Checks -->
        <ion-grid>
          <ion-row>
            <!-- Database -->
            <ion-col size="12" size-md="4">
              <ion-card>
                <ion-card-header>
                  <ion-card-title style="font-size: 1rem;">
                    <ion-icon name="server-outline" style="vertical-align: middle; margin-right: 6px;"></ion-icon>
                    Database
                  </ion-card-title>
                </ion-card-header>
                <ion-card-content>
                  <ion-badge [color]="data()!.checks.database.healthy ? 'success' : 'danger'" style="margin-bottom: 8px;">
                    {{ data()!.checks.database.healthy ? 'Healthy' : 'Unhealthy' }}
                  </ion-badge>
                  <p style="color: var(--ion-color-medium); margin: 4px 0 0;">{{ data()!.checks.database.message }}</p>
                </ion-card-content>
              </ion-card>
            </ion-col>

            <!-- Cache (Redis) -->
            <ion-col size="12" size-md="4">
              <ion-card>
                <ion-card-header>
                  <ion-card-title style="font-size: 1rem;">
                    <ion-icon name="server-outline" style="vertical-align: middle; margin-right: 6px;"></ion-icon>
                    Cache (Redis)
                  </ion-card-title>
                </ion-card-header>
                <ion-card-content>
                  <ion-badge [color]="data()!.checks.cache.healthy ? 'success' : 'danger'" style="margin-bottom: 8px;">
                    {{ data()!.checks.cache.healthy ? 'Healthy' : 'Unhealthy' }}
                  </ion-badge>
                  <p style="color: var(--ion-color-medium); margin: 4px 0 0;">{{ data()!.checks.cache.message }}</p>
                </ion-card-content>
              </ion-card>
            </ion-col>

            <!-- Disk -->
            <ion-col size="12" size-md="4">
              <ion-card>
                <ion-card-header>
                  <ion-card-title style="font-size: 1rem;">
                    <ion-icon name="server-outline" style="vertical-align: middle; margin-right: 6px;"></ion-icon>
                    Disk
                  </ion-card-title>
                </ion-card-header>
                <ion-card-content>
                  <ion-badge [color]="data()!.checks.disk.healthy ? 'success' : 'danger'" style="margin-bottom: 8px;">
                    {{ data()!.checks.disk.healthy ? 'Healthy' : 'Unhealthy' }}
                  </ion-badge>
                  <p style="color: var(--ion-color-medium); margin: 4px 0 0;">{{ data()!.checks.disk.message }}</p>
                  @if (data()!.checks.disk.free_gb !== undefined) {
                    <p style="margin: 4px 0 0; font-weight: 600;">
                      Free: {{ data()!.checks.disk.free_gb }} GB
                    </p>
                  }
                </ion-card-content>
              </ion-card>
            </ion-col>
          </ion-row>
        </ion-grid>
      }
    </ion-content>
  `,
})
export class SuperAdminHealthComponent implements OnInit {
  data = signal<HealthData | null>(null);
  loading = signal(true);

  constructor(private api: ApiService, private toastCtrl: ToastController) {}

  ngOnInit(): void { this.load(); }

  load(): void {
    this.loading.set(true);
    this.api.get<HealthData>('/super-admin/health').subscribe({
      next: (d) => { this.data.set(d); this.loading.set(false); },
      error: async (err) => {
        this.loading.set(false);
        const toast = await this.toastCtrl.create({
          message: err?.message || 'Failed to load health data', color: 'danger', duration: 3000, position: 'bottom',
        });
        await toast.present();
      },
    });
  }

  onRefresh(event: any): void {
    this.api.get<HealthData>('/super-admin/health').subscribe({
      next: (d) => { this.data.set(d); event.target.complete(); },
      error: () => event.target.complete(),
    });
  }
}
