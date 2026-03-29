import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
  IonList, IonItem, IonItemSliding, IonItemOptions, IonItemOption,
  IonLabel, IonBadge, IonNote, IonIcon,
  IonRefresher, IonRefresherContent,
  AlertController,
} from '@ionic/angular/standalone';
import { MaintenanceService, MaintenanceWindow } from './maintenance.service';
import { addIcons } from 'ionicons';
import { constructOutline } from 'ionicons/icons';

addIcons({ constructOutline });

@Component({
  selector: 'app-maintenance-list',
  standalone: true,
  imports: [
    CommonModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
    IonList, IonItem, IonItemSliding, IonItemOptions, IonItemOption,
    IonLabel, IonBadge, IonNote, IonIcon,
    IonRefresher, IonRefresherContent,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>Maintenance Windows</ion-title>
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
                <h2>{{ item.title }}</h2>
                <p>{{ item.start_at | date:'short' }} - {{ item.end_at | date:'short' }}</p>
                @if (item.description) {
                  <p style="font-size: 0.75rem; color: var(--ion-color-medium)">{{ item.description }}</p>
                }
              </ion-label>
              <ion-badge slot="end" [color]="getStatusColor(item.status)">
                {{ item.status | titlecase }}
              </ion-badge>
            </ion-item>

            <ion-item-options side="end">
              <ion-item-option color="danger" (click)="onDelete(item)">Delete</ion-item-option>
            </ion-item-options>
          </ion-item-sliding>
        } @empty {
          <div class="empty-state">
            <ion-icon name="construct-outline" style="font-size: 48px; color: var(--ion-color-medium)"></ion-icon>
            <h3>No maintenance windows</h3>
            <p>Schedule maintenance to inform subscribers in advance.</p>
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
export class MaintenanceListComponent implements OnInit {
  items = signal<MaintenanceWindow[]>([]);

  constructor(private service: MaintenanceService, private alertCtrl: AlertController) {}

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

  async onDelete(item: MaintenanceWindow): Promise<void> {
    const alert = await this.alertCtrl.create({
      header: 'Delete Maintenance Window',
      message: `Delete "${item.title}"?`,
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

  getStatusColor(status: string): string {
    switch (status) {
      case 'scheduled': return 'primary';
      case 'in_progress': return 'warning';
      case 'completed': return 'success';
      case 'cancelled': return 'medium';
      default: return 'medium';
    }
  }
}
