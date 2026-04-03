import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { ViewWillEnter } from '@ionic/angular';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
  IonList, IonItem, IonItemSliding, IonItemOptions, IonItemOption,
  IonLabel, IonBadge, IonChip, IonNote, IonIcon, IonInput,
  IonRefresher, IonRefresherContent, IonSpinner, IonSearchbar,
  AlertController, ToastController,
} from '@ionic/angular/standalone';
import { ApiKeyService, ApiKey, ApiKeyCreateResponse } from './api-key.service';
import { ListSkeletonComponent } from '../../shared/components/list-skeleton.component';
import { showApiError } from '../../core/services/plan-error.helper';
import { addIcons } from 'ionicons';
import { keyOutline, copyOutline } from 'ionicons/icons';

addIcons({ keyOutline, copyOutline });

@Component({
  selector: 'app-api-key-list',
  standalone: true,
  imports: [
    CommonModule, FormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
    IonList, IonItem, IonItemSliding, IonItemOptions, IonItemOption,
    IonLabel, IonBadge, IonChip, IonNote, IonIcon, IonInput,
    IonRefresher, IonRefresherContent, IonSpinner, IonSearchbar,
    ListSkeletonComponent,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>API Keys</ion-title>
        <ion-buttons slot="end">
          <ion-button (click)="onCreate()" fill="solid" color="primary" size="small">+ New Key</ion-button>
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

      <!-- Show newly created key -->
      @if (newKey()) {
        <div class="new-key-banner">
          <h3>Key Created - Copy Now!</h3>
          <p class="new-key-hint">This key will not be shown again.</p>
          <div class="new-key-value">
            <code>{{ newKey() }}</code>
            <ion-button fill="clear" size="small" (click)="copyKey()">
              <ion-icon name="copy-outline"></ion-icon>
            </ion-button>
          </div>
        </div>
      }

      @if (loading()) {
        <app-list-skeleton></app-list-skeleton>
      } @else {
      <ion-list>
        @for (item of items(); track item.id) {
          <ion-item-sliding>
            <ion-item class="key-item">
              <ion-icon name="key-outline" slot="start" color="medium"></ion-icon>
              <ion-label>
                <div class="key-header">
                  <h2>{{ item.name }}</h2>
                  <div class="key-permissions">
                    @for (perm of item.permissions; track perm) {
                      <ion-badge [color]="getPermissionColor(perm)" class="perm-badge">{{ perm }}</ion-badge>
                    }
                  </div>
                </div>
                <div class="key-meta">
                  <code class="key-prefix">{{ maskPrefix(item.prefix) }}</code>
                  <span class="meta-separator">|</span>
                  <span class="meta-date">
                    @if (item.last_used_at) {
                      Last used {{ item.last_used_at | date:'MMM d, y' }}
                    } @else {
                      Never used
                    }
                  </span>
                  <span class="meta-separator">|</span>
                  <span class="meta-date">Created {{ item.created_at | date:'MMM d, y' }}</span>
                </div>
              </ion-label>
            </ion-item>

            <ion-item-options side="end">
              <ion-item-option color="danger" (click)="onDelete(item)">Delete</ion-item-option>
            </ion-item-options>
          </ion-item-sliding>
        } @empty {
          <div class="empty-state">
            <ion-icon name="key-outline" style="font-size: 48px; color: var(--ion-color-medium)"></ion-icon>
            <h3>No API keys</h3>
            <p>Create an API key to access the REST API programmatically.</p>
            <ion-button (click)="onCreate()" fill="outline">Create API Key</ion-button>
          </div>
        }
      </ion-list>
      }
    </ion-content>
  `,
  styles: [`
    .empty-state {
      text-align: center;
      padding: 3rem 1rem;
      color: var(--ion-color-medium);
    }
    .empty-state h3 {
      margin: 1rem 0 0.5rem;
      color: var(--ion-text-color);
    }
    .new-key-banner {
      padding: 16px;
      background: var(--ion-color-success-tint);
      border-radius: 8px;
      margin: 16px;
    }
    .new-key-banner h3 {
      margin: 0 0 4px;
      color: var(--ion-color-success-shade);
    }
    .new-key-hint {
      font-size: 0.8rem;
      color: var(--ion-color-medium);
      margin: 0 0 8px;
    }
    .new-key-value {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .new-key-value code {
      flex: 1;
      padding: 8px;
      background: white;
      border-radius: 4px;
      font-size: 0.8rem;
      word-break: break-all;
      font-family: 'SF Mono', 'Fira Code', 'Cascadia Code', monospace;
    }
    .key-header {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 8px;
    }
    .key-header h2 {
      margin: 0;
      font-size: 1rem;
      font-weight: 600;
    }
    .key-permissions {
      display: flex;
      align-items: center;
      gap: 4px;
    }
    .perm-badge {
      font-size: 0.6rem;
      letter-spacing: 0.5px;
      font-weight: 700;
      padding: 2px 8px;
      text-transform: uppercase;
    }
    .key-meta {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-top: 4px;
      flex-wrap: wrap;
    }
    .key-prefix {
      font-family: 'SF Mono', 'Fira Code', 'Cascadia Code', monospace;
      font-size: 0.78rem;
      color: var(--ion-color-medium-shade);
      background: var(--ion-color-light);
      padding: 1px 6px;
      border-radius: 4px;
      letter-spacing: 0.3px;
    }
    .meta-separator {
      color: var(--ion-color-light-shade);
      font-size: 0.75rem;
    }
    .meta-date {
      font-size: 0.78rem;
      color: var(--ion-color-medium);
    }
  `],
})
export class ApiKeyListComponent implements OnInit, ViewWillEnter {
  items = signal<ApiKey[]>([]);
  allItems = signal<ApiKey[]>([]);
  loading = signal(true);
  searchQuery = '';
  newKey = signal<string | null>(null);

  constructor(
    private service: ApiKeyService,
    private alertCtrl: AlertController,
    private toastCtrl: ToastController,
    private router: Router,
  ) {}

  ngOnInit(): void {}

  ionViewWillEnter(): void { this.load(); }

  load(): void {
    this.service.getAll().subscribe({
      next: (data) => {
        this.allItems.set(data.items);
        this.applyFilter();
        this.loading.set(false);
      },
      error: () => { this.loading.set(false); },
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
        item.name.toLowerCase().includes(query) ||
        item.prefix.toLowerCase().includes(query)
      )
    );
  }

  async onCreate(): Promise<void> {
    const alert = await this.alertCtrl.create({
      header: 'Create API Key',
      inputs: [
        { name: 'name', type: 'text', placeholder: 'Key name (e.g. CI/CD)' },
      ],
      buttons: [
        { text: 'Cancel', role: 'cancel' },
        { text: 'Create', handler: (data) => {
          if (!data.name) return false;
          this.service.create({ name: data.name, permissions: ['read', 'write'] }).subscribe({
            next: (res: ApiKeyCreateResponse) => {
              this.newKey.set(res.key);
              this.load();
            },
            error: async (err: any) => {
              await showApiError(err, 'Failed to create API key', this.toastCtrl, this.router);
            },
          });
          return true;
        }},
      ],
    });
    await alert.present();
  }

  async copyKey(): Promise<void> {
    const key = this.newKey();
    if (key) {
      await navigator.clipboard.writeText(key);
      const toast = await this.toastCtrl.create({ message: 'Copied to clipboard', color: 'success', duration: 2000, position: 'bottom' });
      await toast.present();
    }
  }

  maskPrefix(prefix: string): string {
    if (!prefix) return '••••••••';
    const suffix = prefix.length > 4 ? prefix.slice(-4) : prefix;
    const prefixPart = prefix.length > 4 ? prefix.slice(0, prefix.indexOf('_') + 1) || '' : '';
    return `${prefixPart}${'••••••••'}${suffix}`;
  }

  getPermissionColor(perm: string): string {
    switch (perm) {
      case 'admin': return 'danger';
      case 'write': return 'warning';
      case 'read': return 'tertiary';
      default: return 'medium';
    }
  }

  async onDelete(item: ApiKey): Promise<void> {
    const alert = await this.alertCtrl.create({
      header: 'Delete API Key',
      message: `Delete "${item.name}" (${item.prefix}...)? This cannot be undone.`,
      buttons: [
        { text: 'Cancel', role: 'cancel' },
        { text: 'Delete', role: 'destructive', handler: () => {
          this.service.delete(item.public_id).subscribe(() => {
            this.allItems.update((list) => list.filter((k) => k.id !== item.id));
            this.items.update((list) => list.filter((k) => k.id !== item.id));
          });
        }},
      ],
    });
    await alert.present();
  }
}
