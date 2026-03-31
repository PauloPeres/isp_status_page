import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
  IonList, IonItem, IonInput, IonToggle, IonSpinner, IonNote,
  IonCard, IonCardContent, IonIcon,
  ToastController,
} from '@ionic/angular/standalone';
import { StatusPageService } from './status-page.service';
import { showApiError } from '../../core/services/plan-error.helper';
import { addIcons } from 'ionicons';
import { copyOutline } from 'ionicons/icons';

addIcons({ copyOutline });

@Component({
  selector: 'app-status-page-form',
  standalone: true,
  imports: [
    CommonModule, FormsModule, RouterLink,
    IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
    IonList, IonItem, IonInput, IonToggle, IonSpinner, IonNote,
    IonCard, IonCardContent, IonIcon,
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
    `,
  ],
})
export class StatusPageFormComponent implements OnInit {
  form: any = { name: '', slug: '', custom_domain: '', active: true };
  saving = false;
  submitted = false;
  isEdit = false;
  editId: number | null = null;
  loadingData = false;
  private autoSlug = true;

  constructor(
    private service: StatusPageService,
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
      this.service.get(this.editId).subscribe({
        next: (item) => {
          this.form = {
            name: item.name || '',
            slug: item.slug || '',
            custom_domain: item.custom_domain || '',
            active: item.active ?? true,
          };
          this.loadingData = false;
        },
        error: () => {
          this.loadingData = false;
          this.router.navigate(['/status-pages']);
        },
      });
    }
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

    const request$ = this.isEdit
      ? this.service.update(this.editId!, this.form)
      : this.service.create(this.form);

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
