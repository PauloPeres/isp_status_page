import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
  IonList, IonItem, IonItemSliding, IonItemOptions, IonItemOption,
  IonLabel, IonBadge, IonChip, IonNote, IonIcon,
  IonRefresher, IonRefresherContent, IonSearchbar,
  AlertController,
} from '@ionic/angular/standalone';
import { AlertRuleService, AlertRule } from './alert-rule.service';
import { ListSkeletonComponent } from '../../shared/components/list-skeleton.component';
import { addIcons } from 'ionicons';
import { notificationsOutline } from 'ionicons/icons';

addIcons({ notificationsOutline });

@Component({
  selector: 'app-alert-rule-list',
  standalone: true,
  imports: [
    CommonModule, RouterLink, FormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
    IonList, IonItem, IonItemSliding, IonItemOptions, IonItemOption,
    IonLabel, IonBadge, IonChip, IonNote, IonIcon,
    IonRefresher, IonRefresherContent, IonSearchbar,
    ListSkeletonComponent,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>Alert Rules</ion-title>
        <ion-buttons slot="end">
          <ion-button routerLink="/alert-rules/new" fill="solid" color="primary" size="small">+ New Rule</ion-button>
        </ion-buttons>
      </ion-toolbar>
      <ion-toolbar>
        <ion-searchbar
          [(ngModel)]="searchQuery"
          (ionInput)="onSearch()"
          placeholder="Search..."
          [debounce]="300"
        ></ion-searchbar>
      </ion-toolbar>
    </ion-header>

    <ion-content>
      <ion-refresher slot="fixed" (ionRefresh)="onRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

      @if (loading()) {
        <app-list-skeleton></app-list-skeleton>
      } @else {
      <ion-list>
        @for (item of items(); track item.id) {
          <ion-item-sliding>
            <ion-item [routerLink]="['/alert-rules', item.id, 'edit']">
              <ion-label>
                <h2>{{ item.name }}</h2>
                <p>
                  @if (item.monitor_name) {
                    <span style="margin-right: 8px">{{ item.monitor_name }}</span>
                  }
                  <ion-chip size="small" [color]="getTriggerColor(item.trigger_type)" style="height: 20px; font-size: 0.7rem">
                    {{ item.trigger_type }}
                  </ion-chip>
                  <ion-badge [color]="getChannelColor(item.channel)" style="margin-left: 4px">
                    {{ item.channel }}
                  </ion-badge>
                </p>
              </ion-label>
              <ion-note slot="end" style="font-size: 0.75rem">
                {{ item.cooldown_minutes }}m cooldown
              </ion-note>
            </ion-item>

            <ion-item-options side="end">
              <ion-item-option color="danger" (click)="onDelete(item)">Delete</ion-item-option>
            </ion-item-options>
          </ion-item-sliding>
        } @empty {
          <div class="empty-state">
            <ion-icon name="notifications-outline" style="font-size: 48px; color: var(--ion-color-medium)"></ion-icon>
            <h3>No alert rules</h3>
            <p>Create a rule to get notified when monitors change status.</p>
            <ion-button routerLink="/alert-rules/new" fill="outline">Add Alert Rule</ion-button>
          </div>
        }
      </ion-list>
      }
    </ion-content>
  `,
  styles: [`
    .empty-state { text-align: center; padding: 3rem 1rem; color: var(--ion-color-medium); }
    .empty-state h3 { margin: 1rem 0 0.5rem; color: var(--ion-text-color); }
  `],
})
export class AlertRuleListComponent implements OnInit {
  items = signal<AlertRule[]>([]);
  allItems = signal<AlertRule[]>([]);
  loading = signal(true);
  searchQuery = '';

  constructor(private service: AlertRuleService, private alertCtrl: AlertController) {}

  ngOnInit(): void { this.load(); }

  load(): void {
    this.service.getAll().subscribe((data) => {
      this.allItems.set(data.items);
      this.applyFilter();
      this.loading.set(false);
    });
  }

  onRefresh(event: any): void {
    this.service.getAll().subscribe({
      next: (data) => {
        this.allItems.set(data.items);
        this.applyFilter();
        event.target.complete();
      },
      error: () => event.target.complete(),
    });
  }

  onSearch(): void {
    this.applyFilter();
  }

  applyFilter(): void {
    const query = this.searchQuery.toLowerCase().trim();
    if (!query) {
      this.items.set(this.allItems());
      return;
    }
    this.items.set(
      this.allItems().filter((item) =>
        item.name.toLowerCase().includes(query)
      )
    );
  }

  async onDelete(item: AlertRule): Promise<void> {
    const alert = await this.alertCtrl.create({
      header: 'Delete Alert Rule',
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

  getTriggerColor(type: string): string {
    switch (type) {
      case 'down': return 'danger';
      case 'up': return 'success';
      case 'degraded': return 'warning';
      default: return 'medium';
    }
  }

  getChannelColor(channel: string): string {
    switch (channel) {
      case 'email': return 'primary';
      case 'slack': return 'tertiary';
      case 'discord': return 'tertiary';
      case 'sms': return 'tertiary';
      case 'telegram': return 'secondary';
      case 'pagerduty': return 'success';
      case 'opsgenie': return 'success';
      case 'webhook': return 'warning';
      default: return 'medium';
    }
  }
}
