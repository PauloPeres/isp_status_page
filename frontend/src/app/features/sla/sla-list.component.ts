import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
  IonList, IonItem, IonItemSliding, IonItemOptions, IonItemOption,
  IonLabel, IonBadge, IonNote, IonIcon, IonProgressBar,
  IonRefresher, IonRefresherContent,
  AlertController,
} from '@ionic/angular/standalone';
import { SlaService, Sla } from './sla.service';
import { addIcons } from 'ionicons';
import { ribbonOutline } from 'ionicons/icons';

addIcons({ ribbonOutline });

@Component({
  selector: 'app-sla-list',
  standalone: true,
  imports: [
    CommonModule, RouterLink, FormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
    IonList, IonItem, IonItemSliding, IonItemOptions, IonItemOption,
    IonLabel, IonBadge, IonNote, IonIcon, IonProgressBar,
    IonRefresher, IonRefresherContent,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>SLA</ion-title>
        <ion-buttons slot="end">
          <ion-button routerLink="/sla/new" fill="solid" color="primary" size="small">+ New SLA</ion-button>
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
            <ion-item [routerLink]="['/sla', item.id]">
              <ion-label>
                <h2>{{ item.name }}</h2>
                <p>
                  Target: {{ item.target_uptime }}% | Actual: {{ item.actual_uptime }}%
                  @if (item.monitor_name) {
                    <span style="margin-left: 8px; color: var(--ion-color-medium)">{{ item.monitor_name }}</span>
                  }
                </p>
                <ion-progress-bar
                  [value]="getBudgetUsed(item)"
                  [color]="getStatusColor(item.status)"
                  style="margin-top: 4px"
                ></ion-progress-bar>
                <p style="font-size: 0.7rem; margin-top: 2px">
                  Downtime budget: {{ item.downtime_used_minutes }}/{{ item.downtime_budget_minutes }} min used
                </p>
              </ion-label>
              <ion-badge slot="end" [color]="getStatusColor(item.status)">
                {{ item.status }}
              </ion-badge>
            </ion-item>

            <ion-item-options side="end">
              <ion-item-option color="danger" (click)="onDelete(item)">Delete</ion-item-option>
            </ion-item-options>
          </ion-item-sliding>
        } @empty {
          <div class="empty-state">
            <ion-icon name="ribbon-outline" style="font-size: 48px; color: var(--ion-color-medium)"></ion-icon>
            <h3>No SLA definitions</h3>
            <p>Define SLA targets to track uptime commitments.</p>
            <ion-button routerLink="/sla/new" fill="outline">Add SLA</ion-button>
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
export class SlaListComponent implements OnInit {
  items = signal<Sla[]>([]);

  constructor(private service: SlaService, private alertCtrl: AlertController) {}

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

  async onDelete(item: Sla): Promise<void> {
    const alert = await this.alertCtrl.create({
      header: 'Delete SLA',
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

  getStatusColor(status: string): string {
    switch (status) {
      case 'met': return 'success';
      case 'at_risk': return 'warning';
      case 'breached': return 'danger';
      default: return 'medium';
    }
  }

  getBudgetUsed(item: Sla): number {
    if (!item.downtime_budget_minutes || item.downtime_budget_minutes === 0) return 0;
    return Math.min(item.downtime_used_minutes / item.downtime_budget_minutes, 1);
  }
}
