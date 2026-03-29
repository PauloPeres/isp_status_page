import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
  IonList, IonItem, IonInput, IonTextarea, IonSpinner,
  ToastController,
} from '@ionic/angular/standalone';
import { MaintenanceService } from './maintenance.service';

@Component({
  selector: 'app-maintenance-form',
  standalone: true,
  imports: [
    CommonModule, FormsModule, RouterLink,
    IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
    IonList, IonItem, IonInput, IonTextarea, IonSpinner,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button defaultHref="/maintenance"></ion-back-button>
        </ion-buttons>
        <ion-title>New Maintenance Window</ion-title>
      </ion-toolbar>
    </ion-header>
    <ion-content class="ion-padding">
      <ion-list>
        <ion-item>
          <ion-input label="Title" labelPlacement="floating" [(ngModel)]="form.title" name="title" required></ion-input>
        </ion-item>
        <ion-item>
          <ion-textarea label="Description" labelPlacement="floating" [(ngModel)]="form.description" name="description" rows="3"></ion-textarea>
        </ion-item>
        <ion-item>
          <ion-input label="Start At" labelPlacement="floating" [(ngModel)]="form.start_at" name="start_at" type="datetime-local" required></ion-input>
        </ion-item>
        <ion-item>
          <ion-input label="End At" labelPlacement="floating" [(ngModel)]="form.end_at" name="end_at" type="datetime-local" required></ion-input>
        </ion-item>
      </ion-list>
      <ion-button expand="block" (click)="onSave()" [disabled]="saving">
        @if (saving) { <ion-spinner name="crescent"></ion-spinner> }
        @else { Save Maintenance Window }
      </ion-button>
    </ion-content>
  `,
})
export class MaintenanceFormComponent {
  form: any = { title: '', description: '', start_at: '', end_at: '' };
  saving = false;

  constructor(
    private service: MaintenanceService,
    private router: Router,
    private toastCtrl: ToastController,
  ) {}

  onSave(): void {
    if (!this.form.title || !this.form.start_at || !this.form.end_at) return;
    this.saving = true;
    this.service.create(this.form).subscribe({
      next: async () => {
        this.saving = false;
        const toast = await this.toastCtrl.create({ message: 'Maintenance window created', color: 'success', duration: 2000, position: 'bottom' });
        await toast.present();
        this.router.navigate(['/maintenance']);
      },
      error: async () => {
        this.saving = false;
        const toast = await this.toastCtrl.create({ message: 'Failed to create maintenance window', color: 'danger', duration: 3000, position: 'bottom' });
        await toast.present();
      },
    });
  }
}
