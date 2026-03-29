import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
  IonList, IonItem, IonItemSliding, IonItemOptions, IonItemOption,
  IonLabel, IonBadge, IonNote, IonIcon, IonSpinner,
  IonRefresher, IonRefresherContent,
  AlertController, ToastController,
} from '@ionic/angular/standalone';
import { ScheduledReportService, ScheduledReport } from './scheduled-report.service';
import { addIcons } from 'ionicons';
import { calendarOutline, sendOutline } from 'ionicons/icons';

addIcons({ calendarOutline, sendOutline });

@Component({
  selector: 'app-scheduled-report-list',
  standalone: true,
  imports: [
    CommonModule, RouterLink,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
    IonList, IonItem, IonItemSliding, IonItemOptions, IonItemOption,
    IonLabel, IonBadge, IonNote, IonIcon, IonSpinner,
    IonRefresher, IonRefresherContent,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>Scheduled Reports</ion-title>
        <ion-buttons slot="end">
          <ion-button routerLink="/scheduled-reports/new" fill="solid" color="primary" size="small">+ New Report</ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content>
      <ion-refresher slot="fixed" (ionRefresh)="onRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

      <ion-list>
        @for (item of items(); track item.id) {
          <ion-item-sliding>
            <ion-item>
              <ion-label>
                <h2>{{ item.name }}</h2>
                <p>
                  <ion-badge [color]="getFrequencyColor(item.frequency)" style="margin-right: 6px">
                    {{ item.frequency }}
                  </ion-badge>
                  <span>{{ item.report_type }}</span>
                </p>
                <p style="font-size: 0.75rem; color: var(--ion-color-medium)">
                  Next: {{ item.next_send_at | date:'medium' }}
                  @if (item.last_sent_at) {
                    &middot; Last: {{ item.last_sent_at | date:'medium' }}
                  }
                </p>
              </ion-label>
              <ion-button slot="end" fill="clear" size="small" (click)="onSendNow(item)" [disabled]="sending() === item.id">
                @if (sending() === item.id) {
                  <ion-spinner name="crescent" style="width: 16px; height: 16px"></ion-spinner>
                } @else {
                  <ion-icon name="send-outline"></ion-icon>
                }
              </ion-button>
            </ion-item>

            <ion-item-options side="end">
              <ion-item-option color="danger" (click)="onDelete(item)">Delete</ion-item-option>
            </ion-item-options>
          </ion-item-sliding>
        } @empty {
          <div class="empty-state">
            <ion-icon name="calendar-outline" style="font-size: 48px; color: var(--ion-color-medium)"></ion-icon>
            <h3>No scheduled reports</h3>
            <p>Configure automated report delivery.</p>
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
export class ScheduledReportListComponent implements OnInit {
  items = signal<ScheduledReport[]>([]);
  sending = signal<number | null>(null);

  constructor(
    private service: ScheduledReportService,
    private alertCtrl: AlertController,
    private toastCtrl: ToastController,
  ) {}

  ngOnInit(): void { this.load(); }

  load(): void {
    this.service.getAll().subscribe((data) => this.items.set(data.items));
  }

  onRefresh(event: any): void {
    this.service.getAll().subscribe({
      next: (data) => { this.items.set(data.items); event.target.complete(); },
      error: () => event.target.complete(),
    });
  }

  onSendNow(item: ScheduledReport): void {
    this.sending.set(item.id);
    this.service.sendNow(item.id).subscribe({
      next: async (res) => {
        this.sending.set(null);
        const toast = await this.toastCtrl.create({
          message: res.success ? 'Report sent' : res.message, color: res.success ? 'success' : 'danger',
          duration: 3000, position: 'bottom',
        });
        await toast.present();
        this.load();
      },
      error: async () => {
        this.sending.set(null);
        const toast = await this.toastCtrl.create({
          message: 'Failed to send report', color: 'danger', duration: 3000, position: 'bottom',
        });
        await toast.present();
      },
    });
  }

  async onDelete(item: ScheduledReport): Promise<void> {
    const alert = await this.alertCtrl.create({
      header: 'Delete Scheduled Report',
      message: `Delete "${item.name}"?`,
      buttons: [
        { text: 'Cancel', role: 'cancel' },
        { text: 'Delete', role: 'destructive', handler: () => {
          this.service.delete(item.id).subscribe(() => {
            this.items.update((list) => list.filter((i) => i.id !== item.id));
          });
        }},
      ],
    });
    await alert.present();
  }

  getFrequencyColor(freq: string): string {
    switch (freq) {
      case 'daily': return 'primary';
      case 'weekly': return 'secondary';
      case 'monthly': return 'tertiary';
      default: return 'medium';
    }
  }
}
