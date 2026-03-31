import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
  IonList, IonItem, IonInput, IonToggle, IonSpinner, IonNote,
  ToastController,
} from '@ionic/angular/standalone';
import { StatusPageService } from './status-page.service';
import { showApiError } from '../../core/services/plan-error.helper';

@Component({
  selector: 'app-status-page-form',
  standalone: true,
  imports: [
    CommonModule, FormsModule, RouterLink,
    IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
    IonList, IonItem, IonInput, IonToggle, IonSpinner, IonNote,
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
      <ion-list>
        <ion-item>
          <ion-input label="Name" labelPlacement="floating" [(ngModel)]="form.name" name="name" required></ion-input>
        </ion-item>
        @if (submitted && !form.name) {
          <ion-note color="danger" class="field-error">Name is required</ion-note>
        }
        <ion-item>
          <ion-input label="Slug" labelPlacement="floating" [(ngModel)]="form.slug" name="slug" required placeholder="my-status-page"></ion-input>
        </ion-item>
        @if (submitted && !form.slug) {
          <ion-note color="danger" class="field-error">Slug is required</ion-note>
        }
        <ion-item>
          <ion-input label="Custom Domain" labelPlacement="floating" [(ngModel)]="form.custom_domain" name="custom_domain" placeholder="status.example.com"></ion-input>
        </ion-item>
        <ion-item>
          <ion-toggle [(ngModel)]="form.is_active" name="is_active">Active</ion-toggle>
        </ion-item>
      </ion-list>
      <ion-button expand="block" (click)="onSave()" [disabled]="saving">
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
  form: any = { name: '', slug: '', custom_domain: '', is_active: true };
  saving = false;
  submitted = false;
  isEdit = false;
  editId: number | null = null;
  loadingData = false;

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
      this.loadingData = true;
      this.service.get(this.editId).subscribe({
        next: (item) => {
          this.form = {
            name: item.name || '',
            slug: item.slug || '',
            custom_domain: item.custom_domain || '',
            is_active: item.is_active ?? true,
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
        this.router.navigate(['/status-pages']);
      },
      error: async (err: any) => {
        this.saving = false;
        await showApiError(err, this.isEdit ? 'Failed to update status page' : 'Failed to create status page', this.toastCtrl, this.router);
      },
    });
  }
}
