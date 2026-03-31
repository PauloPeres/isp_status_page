import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
  IonList, IonItem, IonInput, IonSelect, IonSelectOption, IonNote, IonSpinner, IonLabel,
  ToastController,
} from '@ionic/angular/standalone';
import { AuthService } from '../../core/services/auth.service';
import { ApiService } from '../../core/services/api.service';

@Component({
  selector: 'app-profile',
  standalone: true,
  imports: [
    CommonModule, FormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
    IonList, IonItem, IonInput, IonSelect, IonSelectOption, IonNote, IonSpinner, IonLabel,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>My Profile</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      @if (loading()) {
        <div style="text-align: center; padding: 2rem;">
          <ion-spinner name="crescent"></ion-spinner>
        </div>
      } @else {
        <ion-list>
          <ion-item>
            <ion-input label="Username" labelPlacement="stacked" [value]="username" [readonly]="true"></ion-input>
          </ion-item>
          <ion-item>
            <ion-input label="Email" labelPlacement="stacked" [value]="email" [readonly]="true"></ion-input>
          </ion-item>
          <ion-item>
            <ion-input label="Phone Number" labelPlacement="stacked" [(ngModel)]="phoneNumber"
              placeholder="+1 (555) 123-4567" type="tel" inputmode="tel"></ion-input>
          </ion-item>
          <ion-item>
            <ion-select label="Language" labelPlacement="stacked" [(ngModel)]="language" interface="popover">
              <ion-select-option value="en">English</ion-select-option>
              <ion-select-option value="pt">Portugu\u00eas</ion-select-option>
              <ion-select-option value="es">Espa\u00f1ol</ion-select-option>
            </ion-select>
          </ion-item>
          <ion-item>
            <ion-select label="Timezone" labelPlacement="stacked" [(ngModel)]="timezone" interface="popover">
              <ion-select-option value="UTC">UTC</ion-select-option>
              <ion-select-option value="America/New_York">America/New_York (EST)</ion-select-option>
              <ion-select-option value="America/Chicago">America/Chicago (CST)</ion-select-option>
              <ion-select-option value="America/Denver">America/Denver (MST)</ion-select-option>
              <ion-select-option value="America/Los_Angeles">America/Los_Angeles (PST)</ion-select-option>
              <ion-select-option value="America/Sao_Paulo">America/Sao_Paulo (BRT)</ion-select-option>
              <ion-select-option value="Europe/London">Europe/London (GMT)</ion-select-option>
              <ion-select-option value="Europe/Paris">Europe/Paris (CET)</ion-select-option>
              <ion-select-option value="Europe/Berlin">Europe/Berlin (CET)</ion-select-option>
              <ion-select-option value="Asia/Tokyo">Asia/Tokyo (JST)</ion-select-option>
              <ion-select-option value="Asia/Shanghai">Asia/Shanghai (CST)</ion-select-option>
              <ion-select-option value="Asia/Singapore">Asia/Singapore (SGT)</ion-select-option>
              <ion-select-option value="Australia/Sydney">Australia/Sydney (AEST)</ion-select-option>
            </ion-select>
          </ion-item>
        </ion-list>

        <ion-button expand="block" (click)="onSave()" [disabled]="saving()" style="margin-top: 1rem;">
          @if (saving()) {
            <ion-spinner name="crescent" style="width: 16px; height: 16px"></ion-spinner>
          } @else {
            Save Profile
          }
        </ion-button>

        <h3 style="margin-top: 2rem;">Change Password</h3>
        <ion-list>
          <ion-item>
            <ion-input #curPwInput label="Current Password" labelPlacement="stacked" type="password" [(ngModel)]="currentPassword" placeholder="Enter current password" autocomplete="current-password" enterkeyhint="next" (keyup.enter)="newPwInput.setFocus()"></ion-input>
          </ion-item>
          <ion-item>
            <ion-input #newPwInput label="New Password" labelPlacement="stacked" type="password" [(ngModel)]="newPassword" placeholder="Enter new password" autocomplete="new-password" enterkeyhint="next" (keyup.enter)="confirmPwInput.setFocus()"></ion-input>
          </ion-item>
          <ion-item>
            <ion-input #confirmPwInput label="Confirm Password" labelPlacement="stacked" type="password" [(ngModel)]="confirmPassword" placeholder="Confirm new password" autocomplete="new-password" enterkeyhint="done" (keyup.enter)="onChangePassword()"></ion-input>
          </ion-item>
        </ion-list>
        @if (passwordError) {
          <ion-note color="danger" style="display: block; padding: 0.5rem 1rem;">{{ passwordError }}</ion-note>
        }
        <ion-button expand="block" color="secondary" (click)="onChangePassword()" [disabled]="changingPassword()" style="margin-top: 1rem;">
          @if (changingPassword()) {
            <ion-spinner name="crescent" style="width: 16px; height: 16px"></ion-spinner>
          } @else {
            Change Password
          }
        </ion-button>
      }
    </ion-content>
  `,
})
export class ProfileComponent implements OnInit {
  username = '';
  email = '';
  phoneNumber = '';
  language = 'en';
  timezone = 'UTC';

  currentPassword = '';
  newPassword = '';
  confirmPassword = '';
  passwordError = '';

  loading = signal(true);
  saving = signal(false);
  changingPassword = signal(false);

  constructor(
    private auth: AuthService,
    private api: ApiService,
    private toastCtrl: ToastController,
  ) {}

  ngOnInit(): void {
    const user = this.auth.currentUser();
    if (user) {
      this.username = user.username;
      this.email = user.email;
    }
    this.api.get<any>('/auth/me').subscribe({
      next: (data) => {
        const u = data?.user || data;
        if (u) {
          this.username = u.username || this.username;
          this.email = u.email || this.email;
          this.language = u.language || this.language;
          this.timezone = u.timezone || this.timezone;
          this.phoneNumber = u.phone_number || '';
        }
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  onSave(): void {
    this.saving.set(true);
    const payload = { language: this.language, timezone: this.timezone, phone_number: this.phoneNumber || null };
    this.api.put('/auth/me', payload).subscribe({
      next: async () => {
        this.saving.set(false);
        const toast = await this.toastCtrl.create({ message: 'Profile updated', color: 'success', duration: 2000, position: 'bottom' });
        await toast.present();
      },
      error: async (err: any) => {
        this.saving.set(false);
        const toast = await this.toastCtrl.create({ message: err?.message || 'Failed to update profile', color: 'danger', duration: 4000, position: 'bottom' });
        await toast.present();
      },
    });
  }

  onChangePassword(): void {
    this.passwordError = '';
    if (!this.currentPassword || !this.newPassword) {
      this.passwordError = 'Please fill in all password fields.';
      return;
    }
    if (this.newPassword !== this.confirmPassword) {
      this.passwordError = 'New password and confirmation do not match.';
      return;
    }
    if (this.newPassword.length < 8) {
      this.passwordError = 'New password must be at least 8 characters.';
      return;
    }
    this.changingPassword.set(true);
    this.api.post('/auth/change-password', {
      current_password: this.currentPassword,
      new_password: this.newPassword,
    }).subscribe({
      next: async () => {
        this.changingPassword.set(false);
        this.currentPassword = '';
        this.newPassword = '';
        this.confirmPassword = '';
        const toast = await this.toastCtrl.create({ message: 'Password changed successfully', color: 'success', duration: 2000, position: 'bottom' });
        await toast.present();
      },
      error: async (err: any) => {
        this.changingPassword.set(false);
        const toast = await this.toastCtrl.create({ message: err?.message || 'Failed to change password', color: 'danger', duration: 4000, position: 'bottom' });
        await toast.present();
      },
    });
  }
}
