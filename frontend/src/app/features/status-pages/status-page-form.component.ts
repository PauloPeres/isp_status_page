import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
  IonList, IonItem, IonInput, IonToggle, IonSpinner, IonNote, IonTextarea, IonCheckbox,
  IonCard, IonCardContent, IonCardHeader, IonCardTitle, IonIcon, IonLabel,
  ToastController,
} from '@ionic/angular/standalone';
import { StatusPageService } from './status-page.service';
import { ApiService } from '../../core/services/api.service';
import { showApiError } from '../../core/services/plan-error.helper';
import { addIcons } from 'ionicons';
import { copyOutline } from 'ionicons/icons';

addIcons({ copyOutline });

interface MonitorOption {
  id: number;
  name: string;
  type: string;
  selected: boolean;
}

@Component({
  selector: 'app-status-page-form',
  standalone: true,
  imports: [
    CommonModule, FormsModule, RouterLink,
    IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
    IonList, IonItem, IonInput, IonToggle, IonSpinner, IonNote, IonTextarea, IonCheckbox,
    IonCard, IonCardContent, IonCardHeader, IonCardTitle, IonIcon, IonLabel,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button defaultHref="/status-pages"></ion-back-button>
        </ion-buttons>
        <ion-title>{{ isEdit ? 'Edit' : 'New' }} Status Page</ion-title>
      </ion-toolbar>
    </ion-header>
    <ion-content class="ion-padding">
      @if (loadingData) {
        <div style="text-align: center; padding: 2rem">
          <ion-spinner name="crescent"></ion-spinner>
        </div>
      } @else {

      <!-- Public URL card (edit mode only) -->
      @if (isEdit) {
        <ion-card style="margin-bottom: 16px">
          <ion-card-content>
            <div style="display: flex; justify-content: space-between; align-items: center">
              <div>
                <strong>Public URL</strong>
                <p style="margin: 4px 0 0; font-size: 0.85rem">
                  <a [href]="getPreviewUrl()" target="_blank" style="color: var(--ion-color-primary)">{{ getPreviewUrl() }}</a>
                </p>
              </div>
              <ion-button fill="clear" size="small" (click)="copyUrl()">
                <ion-icon name="copy-outline" slot="icon-only"></ion-icon>
              </ion-button>
            </div>
          </ion-card-content>
        </ion-card>
      }

      <!-- Section 1: Basic Info -->
      <ion-card>
        <ion-card-header>
          <ion-card-title>Basic Info</ion-card-title>
        </ion-card-header>
        <ion-card-content>
          <ion-list>
            <ion-item>
              <ion-input label="Name" labelPlacement="stacked" [(ngModel)]="form.name" name="name" required
                (ionInput)="onNameChange()"></ion-input>
            </ion-item>
            @if (submitted && !form.name) {
              <ion-note color="danger" class="field-error">Name is required</ion-note>
            }
            <ion-item>
              <ion-input label="Slug (URL path)" labelPlacement="stacked" [(ngModel)]="form.slug" name="slug" required
                placeholder="my-status-page" [helperText]="'Public URL: ' + getPreviewUrl()"
                (ionInput)="onSlugChange()"></ion-input>
            </ion-item>
            @if (submitted && !form.slug) {
              <ion-note color="danger" class="field-error">Slug is required</ion-note>
            }
            <ion-item>
              <ion-input label="Custom Domain" labelPlacement="stacked" [(ngModel)]="form.custom_domain" name="custom_domain"
                placeholder="status.example.com" helperText="Point a CNAME record to our server"></ion-input>
            </ion-item>
            <ion-item>
              <ion-toggle [(ngModel)]="form.active" name="active">Active</ion-toggle>
            </ion-item>
          </ion-list>
        </ion-card-content>
      </ion-card>

      <!-- Section 2: Content -->
      <ion-card>
        <ion-card-header>
          <ion-card-title>Content</ion-card-title>
        </ion-card-header>
        <ion-card-content>
          <ion-list>
            <ion-item>
              <ion-textarea label="Header Text" labelPlacement="stacked" [(ngModel)]="form.header_text" name="header_text"
                placeholder="Custom message shown at the top of the status page"
                helperText="Displayed above the monitor list on the public page"
                [autoGrow]="true" [rows]="3"></ion-textarea>
            </ion-item>
            <ion-item>
              <ion-textarea label="Footer Text" labelPlacement="stacked" [(ngModel)]="form.footer_text" name="footer_text"
                placeholder="Custom message shown at the bottom of the status page"
                helperText="Displayed below the monitor list on the public page"
                [autoGrow]="true" [rows]="3"></ion-textarea>
            </ion-item>
          </ion-list>
        </ion-card-content>
      </ion-card>

      <!-- Section 3: Monitors -->
      <ion-card>
        <ion-card-header>
          <ion-card-title>Monitors</ion-card-title>
        </ion-card-header>
        <ion-card-content>
          @if (loadingMonitors) {
            <div style="text-align: center; padding: 1rem">
              <ion-spinner name="crescent"></ion-spinner>
            </div>
          } @else if (monitors.length === 0) {
            <ion-note style="display: block; padding: 8px 0">No monitors available. Create monitors first.</ion-note>
          } @else {
            <ion-note style="display: block; padding: 0 0 12px; font-size: 0.85rem">
              Select which monitors to show on the status page. If none are selected, all monitors will be displayed.
            </ion-note>
            <ion-list>
              @for (monitor of monitors; track monitor.id) {
                <ion-item>
                  <ion-checkbox [checked]="monitor.selected" (ionChange)="onMonitorToggle(monitor, $event)"
                    slot="start"></ion-checkbox>
                  <ion-label>
                    {{ monitor.name }}
                    <p>{{ monitor.type }}</p>
                  </ion-label>
                </ion-item>
              }
            </ion-list>
          }
        </ion-card-content>
      </ion-card>

      <!-- Section 4: Display Options -->
      <ion-card>
        <ion-card-header>
          <ion-card-title>Display</ion-card-title>
        </ion-card-header>
        <ion-card-content>
          <ion-list>
            <ion-item>
              <ion-toggle [(ngModel)]="form.show_uptime_chart" name="show_uptime_chart">Show Uptime Chart</ion-toggle>
            </ion-item>
            <ion-item>
              <ion-toggle [(ngModel)]="form.show_incident_history" name="show_incident_history">Show Incident History</ion-toggle>
            </ion-item>
          </ion-list>
        </ion-card-content>
      </ion-card>

      <!-- Section 5: Security -->
      <ion-card>
        <ion-card-header>
          <ion-card-title>Security</ion-card-title>
        </ion-card-header>
        <ion-card-content>
          <ion-list>
            <ion-item>
              <ion-toggle [(ngModel)]="form.password_protected" name="password_protected">Password Protection</ion-toggle>
            </ion-item>
            @if (form.password_protected) {
              <ion-item>
                <ion-input label="Password" labelPlacement="stacked" type="password" [(ngModel)]="form.password" name="password"
                  placeholder="Enter password"
                  helperText="Visitors will need this password to view the status page"></ion-input>
              </ion-item>
              @if (isEdit) {
                <ion-note style="display: block; padding: 4px 16px; font-size: 0.75rem; color: var(--ion-color-medium)">
                  Leave blank to keep the existing password. Enter a new value to change it.
                </ion-note>
              }
            }
          </ion-list>
        </ion-card-content>
      </ion-card>

      <ion-button expand="block" (click)="onSave()" [disabled]="saving" style="margin-top: 16px">
        @if (saving) { <ion-spinner name="crescent"></ion-spinner> }
        @else { {{ isEdit ? 'Update' : 'Save' }} Status Page }
      </ion-button>
      }
    </ion-content>
  `,
  styles: [
    `
      .field-error {
        display: block;
        padding: 4px 16px;
        font-size: 0.75rem;
      }
      ion-card {
        margin-bottom: 16px;
      }
    `,
  ],
})
export class StatusPageFormComponent implements OnInit {
  form: any = {
    name: '',
    slug: '',
    custom_domain: '',
    active: true,
    header_text: '',
    footer_text: '',
    show_uptime_chart: true,
    show_incident_history: true,
    password_protected: false,
    password: '',
  };
  saving = false;
  submitted = false;
  isEdit = false;
  editId: number | null = null;
  loadingData = false;
  loadingMonitors = false;
  monitors: MonitorOption[] = [];
  private autoSlug = true;

  constructor(
    private service: StatusPageService,
    private apiService: ApiService,
    private router: Router,
    private route: ActivatedRoute,
    private toastCtrl: ToastController,
  ) {}

  ngOnInit(): void {
    this.loadMonitors();

    const idParam = this.route.snapshot.paramMap.get('id');
    if (idParam) {
      this.isEdit = true;
      this.editId = +idParam;
      this.autoSlug = false;
      this.loadingData = true;
      this.service.get(this.editId).subscribe({
        next: (item: any) => {
          this.form = {
            name: item.name || '',
            slug: item.slug || '',
            custom_domain: item.custom_domain || '',
            active: item.active ?? true,
            header_text: item.header_text || '',
            footer_text: item.footer_text || '',
            show_uptime_chart: item.show_uptime_chart ?? true,
            show_incident_history: item.show_incident_history ?? true,
            password_protected: !!item.password,
            password: '', // don't load existing password, only set new one
          };
          this.preselectMonitors(item.monitors);
          this.loadingData = false;
        },
        error: () => {
          this.loadingData = false;
          this.router.navigate(['/status-pages']);
        },
      });
    }
  }

  loadMonitors(): void {
    this.loadingMonitors = true;
    this.apiService.get<any>('/monitors', { limit: 500 }).subscribe({
      next: (data: any) => {
        const items = data.monitors || data.items || [];
        this.monitors = items.map((m: any) => ({
          id: m.id,
          name: m.name,
          type: m.type || 'http',
          selected: false,
        }));
        this.loadingMonitors = false;
      },
      error: () => {
        this.loadingMonitors = false;
      },
    });
  }

  preselectMonitors(monitorsField: any): void {
    if (!monitorsField) return;
    let ids: number[] = [];
    if (typeof monitorsField === 'string') {
      try {
        ids = JSON.parse(monitorsField);
      } catch {
        return;
      }
    } else if (Array.isArray(monitorsField)) {
      ids = monitorsField;
    }
    if (!Array.isArray(ids)) return;
    const idSet = new Set(ids.map(Number));
    this.monitors.forEach((m) => {
      m.selected = idSet.has(m.id);
    });
  }

  onMonitorToggle(monitor: MonitorOption, event: any): void {
    monitor.selected = event.detail.checked;
  }

  getSelectedMonitorIds(): string | null {
    const selected = this.monitors.filter((m) => m.selected).map((m) => m.id);
    if (selected.length === 0) return null;
    return JSON.stringify(selected);
  }

  onNameChange(): void {
    if (this.autoSlug && !this.isEdit) {
      this.form.slug = this.slugify(this.form.name);
    }
  }

  onSlugChange(): void {
    this.autoSlug = false;
  }

  slugify(text: string): string {
    return text.toLowerCase()
      .replace(/[^\w\s-]/g, '')
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-')
      .trim();
  }

  getPreviewUrl(): string {
    if (!this.form.slug) return '';
    if (this.form.custom_domain) return 'https://' + this.form.custom_domain;
    return window.location.origin + '/s/' + this.form.slug;
  }

  async copyUrl(): Promise<void> {
    await navigator.clipboard.writeText(this.getPreviewUrl());
    const toast = await this.toastCtrl.create({ message: 'URL copied!', duration: 1500, position: 'bottom', color: 'success' });
    await toast.present();
  }

  onSave(): void {
    this.submitted = true;
    if (!this.form.name || !this.form.slug) return;
    this.saving = true;

    const payload: any = {
      name: this.form.name,
      slug: this.form.slug,
      custom_domain: this.form.custom_domain || null,
      active: this.form.active,
      header_text: this.form.header_text || null,
      footer_text: this.form.footer_text || null,
      show_uptime_chart: this.form.show_uptime_chart,
      show_incident_history: this.form.show_incident_history,
      monitors: this.getSelectedMonitorIds(),
    };

    if (this.form.password_protected && this.form.password) {
      payload.password = this.form.password;
    } else if (!this.form.password_protected) {
      payload.password = null; // remove password protection
    }
    // If password_protected is true but password is empty (edit mode), don't send password field
    // so the backend keeps the existing one

    const request$ = this.isEdit
      ? this.service.update(this.editId!, payload)
      : this.service.create(payload);

    request$.subscribe({
      next: async () => {
        this.saving = false;
        const toast = await this.toastCtrl.create({
          message: this.isEdit ? 'Status page updated' : 'Status page created',
          color: 'success', duration: 2000, position: 'bottom',
        });
        await toast.present();
        const from = this.route.snapshot.queryParamMap.get('from');
        this.router.navigate([from === 'onboarding' ? '/onboarding' : '/status-pages']);
      },
      error: async (err: any) => {
        this.saving = false;
        await showApiError(err, this.isEdit ? 'Failed to update status page' : 'Failed to create status page', this.toastCtrl, this.router);
      },
    });
  }
}
