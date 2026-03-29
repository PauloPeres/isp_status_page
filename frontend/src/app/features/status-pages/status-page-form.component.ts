import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
  IonList, IonItem, IonInput, IonToggle, IonSpinner,
  ToastController,
} from '@ionic/angular/standalone';
import { StatusPageService } from './status-page.service';

@Component({
  selector: 'app-status-page-form',
  standalone: true,
  imports: [
    CommonModule, FormsModule, RouterLink,
    IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
    IonList, IonItem, IonInput, IonToggle, IonSpinner,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button defaultHref="/status-pages"></ion-back-button>
        </ion-buttons>
        <ion-title>New Status Page</ion-title>
      </ion-toolbar>
    </ion-header>
    <ion-content class="ion-padding">
      <ion-list>
        <ion-item>
          <ion-input label="Name" labelPlacement="floating" [(ngModel)]="form.name" name="name" required></ion-input>
        </ion-item>
        <ion-item>
          <ion-input label="Slug" labelPlacement="floating" [(ngModel)]="form.slug" name="slug" required></ion-input>
        </ion-item>
        <ion-item>
          <ion-input label="Custom Domain" labelPlacement="floating" [(ngModel)]="form.custom_domain" name="custom_domain"></ion-input>
        </ion-item>
        <ion-item>
          <ion-toggle [(ngModel)]="form.is_active" name="is_active">Active</ion-toggle>
        </ion-item>
      </ion-list>
      <ion-button expand="block" (click)="onSave()" [disabled]="saving">
        @if (saving) { <ion-spinner name="crescent"></ion-spinner> }
        @else { Save Status Page }
      </ion-button>
    </ion-content>
  `,
})
export class StatusPageFormComponent {
  form: any = { name: '', slug: '', custom_domain: '', is_active: true };
  saving = false;

  constructor(
    private service: StatusPageService,
    private router: Router,
    private toastCtrl: ToastController,
  ) {}

  onSave(): void {
    if (!this.form.name || !this.form.slug) return;
    this.saving = true;
    this.service.create(this.form).subscribe({
      next: async () => {
        this.saving = false;
        const toast = await this.toastCtrl.create({ message: 'Status page created', color: 'success', duration: 2000, position: 'bottom' });
        await toast.present();
        this.router.navigate(['/status-pages']);
      },
      error: async () => {
        this.saving = false;
        const toast = await this.toastCtrl.create({ message: 'Failed to create status page', color: 'danger', duration: 3000, position: 'bottom' });
        await toast.present();
      },
    });
  }
}
