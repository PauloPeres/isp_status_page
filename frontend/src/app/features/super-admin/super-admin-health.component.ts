import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons,
  IonCard, IonCardHeader, IonCardTitle, IonCardContent,
  IonList, IonItem, IonLabel, IonBadge, IonNote, IonIcon, IonSpinner, IonProgressBar,
  IonRefresher, IonRefresherContent,
} from '@ionic/angular/standalone';
import { SuperAdminService, AdminHealth } from './super-admin.service';
import { addIcons } from 'ionicons';
import { serverOutline, heartOutline } from 'ionicons/icons';

addIcons({ serverOutline, heartOutline });

@Component({
  selector: 'app-super-admin-health',
  standalone: true,
  imports: [
    CommonModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons,
    IonCard, IonCardHeader, IonCardTitle, IonCardContent,
    IonList, IonItem, IonLabel, IonBadge, IonNote, IonIcon, IonSpinner, IonProgressBar,
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
        <div style="text-align: center; padding: 3rem"><ion-spinner name="crescent"></ion-spinner></div>
      } @else if (data()) {
        <ion-card>
          <ion-card-header>
            <ion-card-title>
              <ion-icon name="server-outline" style="vertical-align: middle; margin-right: 8px"></ion-icon>
              System Resources
            </ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <ion-list>
              <ion-item>
                <ion-label>
                  <h3>CPU Usage</h3>
                  <ion-progress-bar [value]="data()!.cpu_usage / 100" [color]="getUsageColor(data()!.cpu_usage)"></ion-progress-bar>
                </ion-label>
                <ion-note slot="end">{{ data()!.cpu_usage }}%</ion-note>
              </ion-item>
              <ion-item>
                <ion-label>
                  <h3>Memory Usage</h3>
                  <ion-progress-bar [value]="data()!.memory_usage / 100" [color]="getUsageColor(data()!.memory_usage)"></ion-progress-bar>
                </ion-label>
                <ion-note slot="end">{{ data()!.memory_usage }}%</ion-note>
              </ion-item>
              <ion-item>
                <ion-label>
                  <h3>Disk Usage</h3>
                  <ion-progress-bar [value]="data()!.disk_usage / 100" [color]="getUsageColor(data()!.disk_usage)"></ion-progress-bar>
                </ion-label>
                <ion-note slot="end">{{ data()!.disk_usage }}%</ion-note>
              </ion-item>
            </ion-list>
          </ion-card-content>
        </ion-card>

        <ion-card>
          <ion-card-header>
            <ion-card-title>
              <ion-icon name="heart-outline" style="vertical-align: middle; margin-right: 8px"></ion-icon>
              Services
            </ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <ion-list>
              <ion-item>
                <ion-label>Database Connections</ion-label>
                <ion-note slot="end">{{ data()!.db_connections }}</ion-note>
              </ion-item>
              <ion-item>
                <ion-label>Queue Size</ion-label>
                <ion-badge slot="end" [color]="data()!.queue_size > 100 ? 'warning' : 'success'">
                  {{ data()!.queue_size }}
                </ion-badge>
              </ion-item>
              <ion-item>
                <ion-label>Redis</ion-label>
                <ion-badge slot="end" [color]="data()!.redis_connected ? 'success' : 'danger'">
                  {{ data()!.redis_connected ? 'Connected' : 'Disconnected' }}
                </ion-badge>
              </ion-item>
              <ion-item>
                <ion-label>Uptime</ion-label>
                <ion-note slot="end">{{ formatUptime(data()!.uptime_seconds) }}</ion-note>
              </ion-item>
              <ion-item>
                <ion-label>Last Health Check</ion-label>
                <ion-note slot="end">{{ data()!.last_check_at | date:'medium' }}</ion-note>
              </ion-item>
            </ion-list>
          </ion-card-content>
        </ion-card>
      }
    </ion-content>
  `,
})
export class SuperAdminHealthComponent implements OnInit {
  data = signal<AdminHealth | null>(null);
  loading = signal(true);

  constructor(private service: SuperAdminService) {}

  ngOnInit(): void { this.load(); }

  load(): void {
    this.service.getHealth().subscribe({
      next: (d) => { this.data.set(d); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }

  onRefresh(event: any): void {
    this.service.getHealth().subscribe({
      next: (d) => { this.data.set(d); event.target.complete(); },
      error: () => event.target.complete(),
    });
  }

  getUsageColor(pct: number): string {
    if (pct > 90) return 'danger';
    if (pct > 70) return 'warning';
    return 'success';
  }

  formatUptime(seconds: number): string {
    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    const mins = Math.floor((seconds % 3600) / 60);
    return `${days}d ${hours}h ${mins}m`;
  }
}
