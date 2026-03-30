import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
  IonList, IonItem, IonLabel, IonBadge, IonNote, IonIcon, IonSelect, IonSelectOption,
  IonRefresher, IonRefresherContent,
  ActionSheetController,
} from '@ionic/angular/standalone';
import { ActivityLogService, ActivityLogEntry } from './activity-log.service';
import { environment } from '../../../environments/environment';
import { addIcons } from 'ionicons';
import { listOutline, downloadOutline } from 'ionicons/icons';

addIcons({ listOutline, 'download-outline': downloadOutline });

@Component({
  selector: 'app-activity-log',
  standalone: true,
  imports: [
    CommonModule, FormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
    IonList, IonItem, IonLabel, IonBadge, IonNote, IonIcon, IonSelect, IonSelectOption,
    IonRefresher, IonRefresherContent,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>Activity Log</ion-title>
        <ion-buttons slot="end">
          <ion-button (click)="onExport()">
            <ion-icon name="download-outline" slot="icon-only"></ion-icon>
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content>
      <ion-refresher slot="fixed" (ionRefresh)="onRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

      <ion-item>
        <ion-label>Filter by event</ion-label>
        <ion-select [(ngModel)]="eventFilter" (ionChange)="load()" interface="popover" placeholder="All events">
          <ion-select-option value="">All</ion-select-option>
          <ion-select-option value="login">Login</ion-select-option>
          <ion-select-option value="logout">Logout</ion-select-option>
          <ion-select-option value="create">Create</ion-select-option>
          <ion-select-option value="update">Update</ion-select-option>
          <ion-select-option value="delete">Delete</ion-select-option>
          <ion-select-option value="settings_change">Settings Change</ion-select-option>
          <ion-select-option value="2fa">2FA</ion-select-option>
        </ion-select>
      </ion-item>

      <ion-list>
        @for (entry of items(); track entry.id) {
          <ion-item>
            <ion-label>
              <h2>{{ entry.description }}</h2>
              <p>
                <ion-badge [color]="getEventColor(entry.event_type)" style="margin-right: 6px; font-size: 0.65rem">
                  {{ entry.event_type }}
                </ion-badge>
                @if (entry.user_name) {
                  <span>{{ entry.user_name }}</span>
                }
                <span style="margin-left: 8px; color: var(--ion-color-medium); font-size: 0.75rem">{{ entry.ip_address }}</span>
              </p>
            </ion-label>
            <ion-note slot="end" style="font-size: 0.7rem">{{ entry.created_at | date:'short' }}</ion-note>
          </ion-item>
        } @empty {
          <div class="empty-state">
            <ion-icon name="list-outline" style="font-size: 48px; color: var(--ion-color-medium)"></ion-icon>
            <h3>No activity logged</h3>
            <p>Activity will appear here as actions are performed.</p>
          </div>
        }
      </ion-list>
    </ion-content>
  `,
  styles: [`
    .empty-state { text-align: center; padding: 3rem 1rem; color: var(--ion-color-medium); }
    .empty-state h3 { margin: 1rem 0 0.5rem; color: var(--ion-text-color); }
  `],
})
export class ActivityLogComponent implements OnInit {
  items = signal<ActivityLogEntry[]>([]);
  eventFilter = '';

  constructor(
    private service: ActivityLogService,
    private actionSheetCtrl: ActionSheetController,
  ) {}

  ngOnInit(): void { this.load(); }

  load(): void {
    const params: any = {};
    if (this.eventFilter) params.event_type = this.eventFilter;
    this.service.getAll(params).subscribe((data) => this.items.set(data.items));
  }

  onRefresh(event: any): void {
    const params: any = {};
    if (this.eventFilter) params.event_type = this.eventFilter;
    this.service.getAll(params).subscribe({
      next: (data) => { this.items.set(data.items); event.target.complete(); },
      error: () => event.target.complete(),
    });
  }

  async onExport(): Promise<void> {
    const sheet = await this.actionSheetCtrl.create({
      header: 'Export Audit Log',
      buttons: [
        {
          text: 'Export as CSV',
          handler: () => {
            this.downloadExport('csv');
          },
        },
        {
          text: 'Export as JSON',
          handler: () => {
            this.downloadExport('json');
          },
        },
        { text: 'Cancel', role: 'cancel' },
      ],
    });
    await sheet.present();
  }

  private downloadExport(format: string): void {
    const token = localStorage.getItem('access_token');
    let url = `${environment.apiUrl}/activity-log/export?format=${format}`;
    if (this.eventFilter) {
      url += `&event_type=${this.eventFilter}`;
    }
    // Open in new tab with auth header via fetch + blob download
    fetch(url, { headers: { Authorization: `Bearer ${token}` } })
      .then((res) => res.blob())
      .then((blob) => {
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = `audit_log_export.${format}`;
        a.click();
        URL.revokeObjectURL(a.href);
      });
  }

  getEventColor(type: string): string {
    switch (type) {
      case 'login': return 'success';
      case 'logout': return 'medium';
      case 'create': return 'primary';
      case 'update': return 'secondary';
      case 'delete': return 'danger';
      case 'settings_change': return 'warning';
      case '2fa': return 'tertiary';
      default: return 'medium';
    }
  }
}
