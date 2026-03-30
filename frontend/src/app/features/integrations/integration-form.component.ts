import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
  IonList, IonItem, IonInput, IonSelect, IonSelectOption, IonToggle, IonSpinner, IonNote,
  ToastController,
} from '@ionic/angular/standalone';
import { IntegrationService } from './integration.service';

@Component({
  selector: 'app-integration-form',
  standalone: true,
  imports: [
    CommonModule, FormsModule, RouterLink,
    IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
    IonList, IonItem, IonInput, IonSelect, IonSelectOption, IonToggle, IonSpinner, IonNote,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button defaultHref="/integrations"></ion-back-button>
        </ion-buttons>
        <ion-title>New Integration</ion-title>
      </ion-toolbar>
    </ion-header>
    <ion-content class="ion-padding">
      <ion-list>
        <ion-item>
          <ion-input label="Name" labelPlacement="floating" [(ngModel)]="form.name" name="name" required></ion-input>
        </ion-item>
        @if (submitted && !form.name) {
          <ion-note color="danger" class="field-error">Name is required</ion-note>
        }
        <ion-item>
          <ion-select label="Type" labelPlacement="floating" [(ngModel)]="form.type" name="type">
            <ion-select-option value="ixc">IXC</ion-select-option>
            <ion-select-option value="zabbix">Zabbix</ion-select-option>
            <ion-select-option value="rest_api">REST API</ion-select-option>
          </ion-select>
        </ion-item>
        <ion-item>
          <ion-input label="Base URL" labelPlacement="floating" [(ngModel)]="form.base_url" name="base_url" required></ion-input>
        </ion-item>
        @if (submitted && !form.base_url) {
          <ion-note color="danger" class="field-error">Base URL is required</ion-note>
        }
        <ion-item>
          <ion-input label="Username" labelPlacement="floating" [(ngModel)]="form.username" name="username"></ion-input>
        </ion-item>
        <ion-item>
          <ion-input label="Password" labelPlacement="floating" [(ngModel)]="form.password" name="password" type="password"></ion-input>
        </ion-item>
        <ion-item>
          <ion-toggle [(ngModel)]="form.active" name="active">Active</ion-toggle>
        </ion-item>
      </ion-list>
      <ion-button expand="block" (click)="onSave()" [disabled]="saving">
        @if (saving) { <ion-spinner name="crescent"></ion-spinner> }
        @else { Save Integration }
      </ion-button>
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
export class IntegrationFormComponent {
  form: any = { name: '', type: 'rest_api', base_url: '', username: '', password: '', active: true };
  saving = false;
  submitted = false;

  constructor(
    private service: IntegrationService,
    private router: Router,
    private toastCtrl: ToastController,
  ) {}

  onSave(): void {
    this.submitted = true;
    if (!this.form.name || !this.form.base_url) return;
    this.saving = true;
    const payload = {
      name: this.form.name,
      type: this.form.type,
      active: this.form.active,
      configuration: {
        base_url: this.form.base_url,
        username: this.form.username,
        password: this.form.password,
      },
    };
    this.service.create(payload).subscribe({
      next: async () => {
        this.saving = false;
        const toast = await this.toastCtrl.create({ message: 'Integration created', color: 'success', duration: 2000, position: 'bottom' });
        await toast.present();
        this.router.navigate(['/integrations']);
      },
      error: async (err: any) => {
        this.saving = false;
        const toast = await this.toastCtrl.create({ message: err?.message || 'Failed to create integration', color: 'danger', duration: 4000, position: 'bottom' });
        await toast.present();
      },
    });
  }
}
