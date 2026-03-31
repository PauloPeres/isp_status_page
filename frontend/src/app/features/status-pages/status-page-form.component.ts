import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
  IonList, IonItem, IonInput, IonTextarea, IonToggle, IonCheckbox, IonSpinner, IonNote,
  IonSelect, IonSelectOption,
  IonCard, IonCardHeader, IonCardTitle, IonCardContent, IonIcon, IonLabel,
  ToastController,
} from '@ionic/angular/standalone';
import { StatusPageService } from './status-page.service';
import { ApiService } from '../../core/services/api.service';
import { showApiError } from '../../core/services/plan-error.helper';
import { addIcons } from 'ionicons';
import { copyOutline } from 'ionicons/icons';
import { forkJoin, of, catchError } from 'rxjs';

addIcons({ copyOutline });

interface MonitorOption { id: number; name: string; type: string; selected: boolean; }

@Component({
  selector: 'app-status-page-form',
  standalone: true,
  imports: [
    CommonModule, FormsModule, RouterLink,
    IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
    IonList, IonItem, IonInput, IonTextarea, IonToggle, IonCheckbox, IonSpinner, IonNote,
    IonSelect, IonSelectOption,
    IonCard, IonCardHeader, IonCardTitle, IonCardContent, IonIcon, IonLabel,
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

      <!-- Public URL card (edit mode) -->
      @if (isEdit && form.slug) {
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
        <ion-card-header><ion-card-title>Basic Info</ion-card-title></ion-card-header>
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
            <ion-item>
              <ion-input label="Custom Domain" labelPlacement="stacked" [(ngModel)]="form.custom_domain" name="custom_domain"
                placeholder="status.example.com" helperText="Point a CNAME record to our server"></ion-input>
            </ion-item>
            <ion-item>
              <ion-toggle [(ngModel)]="form.active" name="active">Active</ion-toggle>
            </ion-item>
            <ion-item>
              <ion-select label="Language" labelPlacement="stacked" [(ngModel)]="form.language" name="language" interface="popover"
                helperText="Language for public status page text">
                <ion-select-option value="en">English</ion-select-option>
                <ion-select-option value="pt_BR">Português (Brasil)</ion-select-option>
                <ion-select-option value="es">Español</ion-select-option>
              </ion-select>
            </ion-item>
          </ion-list>
        </ion-card-content>
      </ion-card>

      <!-- Section 2: Content -->
      <ion-card>
        <ion-card-header><ion-card-title>Content</ion-card-title></ion-card-header>
        <ion-card-content>
          <ion-list>
            <ion-item>
              <ion-textarea label="Header Text" labelPlacement="stacked" [(ngModel)]="form.header_text" name="header_text"
                placeholder="Custom message shown at the top of the status page"
                [autoGrow]="true" [rows]="2"></ion-textarea>
            </ion-item>
            <ion-item>
              <ion-textarea label="Footer Text" labelPlacement="stacked" [(ngModel)]="form.footer_text" name="footer_text"
                placeholder="Custom message shown at the bottom"
                [autoGrow]="true" [rows]="2"></ion-textarea>
            </ion-item>
          </ion-list>
        </ion-card-content>
      </ion-card>

      <!-- Section 3: Branding -->
      <ion-card>
        <ion-card-header><ion-card-title>Branding</ion-card-title></ion-card-header>
        <ion-card-content>
          <ion-list>
            <ion-item>
              <ion-label position="stacked">Primary Color</ion-label>
              <div style="display: flex; align-items: center; gap: 8px; padding: 8px 0; width: 100%">
                <input type="color" [value]="form.primary_color || '#6366F1'" (input)="form.primary_color = $any($event).target.value"
                  style="width: 44px; height: 44px; border: 1px solid var(--ion-color-light-shade); border-radius: 8px; cursor: pointer; padding: 2px;">
                <ion-input [(ngModel)]="form.primary_color" name="primary_color" placeholder="#6366F1"
                  style="flex: 1" helperText="Header background color"></ion-input>
              </div>
            </ion-item>
            <ion-item>
              <ion-input label="Logo URL" labelPlacement="stacked" [(ngModel)]="form.logo_url" name="logo_url"
                placeholder="https://example.com/logo.png" helperText="Logo displayed in the status page header"></ion-input>
            </ion-item>
            <ion-item>
              <ion-textarea label="Custom CSS" labelPlacement="stacked" [(ngModel)]="form.custom_css" name="custom_css"
                placeholder=".sp-header { ... }" helperText="Advanced: inject custom CSS into the public page"
                [autoGrow]="true" [rows]="3"></ion-textarea>
            </ion-item>
          </ion-list>
        </ion-card-content>
      </ion-card>

      <!-- Section 4: Monitors -->
      <ion-card>
        <ion-card-header>
          <div style="display: flex; justify-content: space-between; align-items: center">
            <ion-card-title>Monitors</ion-card-title>
            @if (monitors.length > 0) {
              <ion-button fill="clear" size="small" (click)="toggleAllMonitors()">
                {{ allMonitorsSelected() ? 'Deselect All' : 'Select All' }}
              </ion-button>
            }
          </div>
        </ion-card-header>
        <ion-card-content>
          @if (loadingMonitors) {
            <div style="text-align: center; padding: 1rem"><ion-spinner name="crescent"></ion-spinner></div>
          } @else if (monitors.length === 0) {
            <ion-note style="display: block; padding: 8px 0">No monitors available. Create monitors first.</ion-note>
          } @else {
            <ion-note style="display: block; padding: 0 0 12px; font-size: 0.85rem">
              Select which monitors to display. If none selected, all monitors will be shown.
            </ion-note>
            <ion-list>
              @for (monitor of monitors; track monitor.id) {
                <ion-item>
                  <ion-checkbox [checked]="monitor.selected" (ionChange)="onMonitorToggle(monitor, $event)" slot="start"></ion-checkbox>
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

      <!-- Section 5: Display Options -->
      <ion-card>
        <ion-card-header><ion-card-title>Display</ion-card-title></ion-card-header>
        <ion-card-content>
          <ion-list>
            <ion-item>
              <ion-toggle [(ngModel)]="form.show_uptime_chart" name="show_uptime_chart">Show 90-Day Uptime Chart</ion-toggle>
            </ion-item>
            <ion-item>
              <ion-toggle [(ngModel)]="form.show_incident_history" name="show_incident_history">Show Incident History & Timeline</ion-toggle>
            </ion-item>
          </ion-list>
        </ion-card-content>
      </ion-card>

      <!-- Section 6: Security -->
      <ion-card>
        <ion-card-header><ion-card-title>Security</ion-card-title></ion-card-header>
        <ion-card-content>
          <ion-list>
            <ion-item>
              <ion-toggle [(ngModel)]="form.password_protected" name="password_protected">Password Protection</ion-toggle>
            </ion-item>
            @if (form.password_protected) {
              <ion-item>
                <ion-input label="Password" labelPlacement="stacked" [(ngModel)]="form.password" name="password"
                  type="password" placeholder="Enter password for visitors"
                  [helperText]="isEdit ? 'Leave blank to keep existing password' : ''"></ion-input>
              </ion-item>
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
  styles: [`
    .field-error { display: block; padding: 4px 16px; font-size: 0.75rem; }
    ion-card { margin-bottom: 16px; }
  `],
})
export class StatusPageFormComponent implements OnInit {
  form: any = {
    name: '', slug: '', custom_domain: '', active: true,
    header_text: '', footer_text: '',
    show_uptime_chart: true, show_incident_history: true,
    password_protected: false, password: '',
    primary_color: '', logo_url: '', custom_css: '', language: 'en',
  };
  saving = false;
  submitted = false;
  isEdit = false;
  editId: number | null = null;
  loadingData = false;
  loadingMonitors = false;
  monitors: MonitorOption[] = [];
  private autoSlug = true;
  private pendingMonitorIds: any = null;

  constructor(
    private service: StatusPageService,
    private apiService: ApiService,
    private router: Router,
    private route: ActivatedRoute,
    private toastCtrl: ToastController,
  ) {}

  ngOnInit(): void {
    const idParam = this.route.snapshot.paramMap.get('id');

    if (idParam) {
      this.isEdit = true;
      this.editId = +idParam;
      this.autoSlug = false;
      this.loadingData = true;

      // Load monitors and status page data in parallel, then preselect
      forkJoin({
        monitors: this.apiService.get<any>('/monitors', { limit: 500 }).pipe(catchError(() => of({ items: [] }))),
        statusPage: this.service.get(this.editId),
      }).subscribe({
        next: ({ monitors, statusPage }: any) => {
          const items = monitors.monitors || monitors.items || [];
          this.monitors = items.map((m: any) => ({ id: m.id, name: m.name, type: m.type || 'http', selected: false }));

          const item = statusPage;
          const theme = this.parseTheme(item.theme);

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
            password: '',
            primary_color: theme.primary_color || '',
            logo_url: theme.logo_url || '',
            custom_css: theme.custom_css || '',
            language: item.language || 'en',
          };

          this.preselectMonitors(item.monitors);
          this.loadingData = false;
          this.loadingMonitors = false;
        },
        error: () => {
          this.loadingData = false;
          this.router.navigate(['/status-pages']);
        },
      });
    } else {
      this.loadMonitors();
    }
  }

  loadMonitors(): void {
    this.loadingMonitors = true;
    this.apiService.get<any>('/monitors', { limit: 500 }).subscribe({
      next: (data: any) => {
        const items = data.monitors || data.items || [];
        this.monitors = items.map((m: any) => ({ id: m.id, name: m.name, type: m.type || 'http', selected: false }));
        this.loadingMonitors = false;
        if (this.pendingMonitorIds) {
          this.preselectMonitors(this.pendingMonitorIds);
          this.pendingMonitorIds = null;
        }
      },
      error: () => { this.loadingMonitors = false; },
    });
  }

  preselectMonitors(monitorsField: any): void {
    if (!monitorsField) return;
    if (this.monitors.length === 0) {
      this.pendingMonitorIds = monitorsField;
      return;
    }
    let ids: number[] = [];
    if (typeof monitorsField === 'string') {
      try { ids = JSON.parse(monitorsField); } catch { return; }
    } else if (Array.isArray(monitorsField)) {
      ids = monitorsField;
    }
    if (!Array.isArray(ids)) return;
    const idSet = new Set(ids.map(Number));
    this.monitors.forEach((m) => { m.selected = idSet.has(m.id); });
  }

  parseTheme(theme: any): any {
    if (!theme) return {};
    if (typeof theme === 'string') {
      try { return JSON.parse(theme); } catch { return {}; }
    }
    if (typeof theme === 'object') return theme;
    return {};
  }

  onMonitorToggle(monitor: MonitorOption, event: any): void {
    monitor.selected = event.detail.checked;
  }

  toggleAllMonitors(): void {
    const allSelected = this.allMonitorsSelected();
    this.monitors.forEach(m => m.selected = !allSelected);
  }

  allMonitorsSelected(): boolean {
    return this.monitors.length > 0 && this.monitors.every(m => m.selected);
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

  onSlugChange(): void { this.autoSlug = false; }

  slugify(text: string): string {
    return text.toLowerCase().replace(/[^\w\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-').trim();
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

    // Build theme JSON
    const theme: any = {};
    if (this.form.primary_color) theme.primary_color = this.form.primary_color;
    if (this.form.logo_url) theme.logo_url = this.form.logo_url;
    if (this.form.custom_css) theme.custom_css = this.form.custom_css;

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
      language: this.form.language || 'en',
      theme: Object.keys(theme).length > 0 ? JSON.stringify(theme) : null,
    };
    if (this.form.password_protected && this.form.password) {
      payload.password = this.form.password;
    } else if (!this.form.password_protected) {
      payload.password = null;
    }

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
        await showApiError(err, this.isEdit ? 'Failed to update' : 'Failed to create', this.toastCtrl, this.router);
      },
    });
  }
}
