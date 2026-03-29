import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
  IonList, IonItem, IonItemSliding, IonItemOptions, IonItemOption,
  IonLabel, IonBadge, IonChip, IonIcon,
  IonRefresher, IonRefresherContent, IonSpinner,
  AlertController, ToastController,
} from '@ionic/angular/standalone';
import { IntegrationService, Integration } from './integration.service';
import { addIcons } from 'ionicons';
import { extensionPuzzleOutline } from 'ionicons/icons';

addIcons({ extensionPuzzleOutline });

@Component({
  selector: 'app-integration-list',
  standalone: true,
  imports: [
    CommonModule, RouterLink, FormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
    IonList, IonItem, IonItemSliding, IonItemOptions, IonItemOption,
    IonLabel, IonBadge, IonChip, IonIcon,
    IonRefresher, IonRefresherContent, IonSpinner,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>Integrations</ion-title>
        <ion-buttons slot="end">
          <ion-button routerLink="/integrations/new" fill="solid" color="primary" size="small">+ New Integration</ion-button>
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
                  <ion-chip size="small" [color]="getTypeColor(item.type)" style="height: 20px; font-size: 0.7rem">
                    {{ item.type }}
                  </ion-chip>
                  @if (item.last_test_status) {
                    <ion-badge [color]="item.last_test_status === 'ok' ? 'success' : 'danger'" style="margin-left: 4px">
                      {{ item.last_test_status }}
                    </ion-badge>
                  }
                </p>
              </ion-label>
              <ion-button slot="end" fill="clear" size="small" (click)="onTest(item)" [disabled]="testing() === item.id">
                @if (testing() === item.id) {
                  <ion-spinner name="crescent" style="width: 16px; height: 16px"></ion-spinner>
                } @else {
                  Test
                }
              </ion-button>
            </ion-item>

            <ion-item-options side="end">
              <ion-item-option color="danger" (click)="onDelete(item)">Delete</ion-item-option>
            </ion-item-options>
          </ion-item-sliding>
        } @empty {
          <div class="empty-state">
            <ion-icon name="extension-puzzle-outline" style="font-size: 48px; color: var(--ion-color-medium)"></ion-icon>
            <h3>No integrations</h3>
            <p>Connect an external service to get started.</p>
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
export class IntegrationListComponent implements OnInit {
  items = signal<Integration[]>([]);
  testing = signal<number | null>(null);

  constructor(
    private service: IntegrationService,
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

  onTest(item: Integration): void {
    this.testing.set(item.id);
    this.service.testConnection(item.id).subscribe({
      next: async (res) => {
        this.testing.set(null);
        const toast = await this.toastCtrl.create({
          message: res.success ? 'Connection successful' : `Failed: ${res.message}`,
          color: res.success ? 'success' : 'danger',
          duration: 3000, position: 'bottom',
        });
        await toast.present();
        this.load();
      },
      error: async () => {
        this.testing.set(null);
        const toast = await this.toastCtrl.create({
          message: 'Connection test failed', color: 'danger', duration: 3000, position: 'bottom',
        });
        await toast.present();
      },
    });
  }

  async onDelete(item: Integration): Promise<void> {
    const alert = await this.alertCtrl.create({
      header: 'Delete Integration',
      message: `Delete "${item.name}"? This cannot be undone.`,
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

  getTypeColor(type: string): string {
    switch (type) {
      case 'ixc': return 'tertiary';
      case 'zabbix': return 'warning';
      case 'rest_api': return 'primary';
      default: return 'medium';
    }
  }
}
