import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
  IonCard, IonCardHeader, IonCardTitle, IonCardContent,
  IonItem, IonLabel, IonInput, IonIcon, IonSpinner, IonList, IonNote,
  AlertController, ToastController,
} from '@ionic/angular/standalone';
import { ApiService } from '../../core/services/api.service';
import { addIcons } from 'ionicons';
import { shieldCheckmarkOutline, keyOutline } from 'ionicons/icons';

addIcons({ shieldCheckmarkOutline, keyOutline });

interface TwoFactorSetup {
  secret: string;
  qr_url: string;
  enabled: boolean;
}

@Component({
  selector: 'app-two-factor',
  standalone: true,
  imports: [
    CommonModule, FormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
    IonCard, IonCardHeader, IonCardTitle, IonCardContent,
    IonItem, IonLabel, IonInput, IonIcon, IonSpinner, IonList, IonNote,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>Two-Factor Authentication</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      @if (loading()) {
        <div style="text-align: center; padding: 3rem">
          <ion-spinner name="crescent"></ion-spinner>
        </div>
      } @else if (enabled()) {
        <!-- 2FA Enabled State -->
        <ion-card>
          <ion-card-header>
            <ion-card-title>
              <ion-icon name="shield-checkmark-outline" style="vertical-align: middle; margin-right: 8px; color: var(--ion-color-success)"></ion-icon>
              2FA Enabled
            </ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <p>Two-factor authentication is currently enabled on your account.</p>

            <ion-item style="margin-top: 16px">
              <ion-label position="stacked">Password to disable</ion-label>
              <ion-input type="password" [(ngModel)]="disablePassword" placeholder="Enter your password"></ion-input>
            </ion-item>
            <ion-button expand="block" color="danger" fill="outline" style="margin-top: 12px"
              (click)="disable()" [disabled]="disabling()">
              @if (disabling()) {
                <ion-spinner name="crescent" style="width: 16px; height: 16px; margin-right: 8px"></ion-spinner>
              }
              Disable 2FA
            </ion-button>
          </ion-card-content>
        </ion-card>

        <!-- Recovery Codes -->
        <ion-card>
          <ion-card-header>
            <ion-card-title>
              <ion-icon name="key-outline" style="vertical-align: middle; margin-right: 8px"></ion-icon>
              Recovery Codes
            </ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <ion-button expand="block" fill="outline" (click)="loadRecoveryCodes()" [disabled]="loadingCodes()">
              @if (loadingCodes()) {
                <ion-spinner name="crescent" style="width: 16px; height: 16px; margin-right: 8px"></ion-spinner>
              }
              Show Recovery Codes
            </ion-button>
            @if (recoveryCodes().length > 0) {
              <ion-list style="margin-top: 12px">
                @for (code of recoveryCodes(); track code) {
                  <ion-item>
                    <ion-label><code>{{ code }}</code></ion-label>
                  </ion-item>
                }
              </ion-list>
              <ion-note color="warning" style="display: block; margin-top: 8px">
                Store these codes in a safe place. Each code can only be used once.
              </ion-note>
            }
          </ion-card-content>
        </ion-card>
      } @else {
        <!-- 2FA Setup State -->
        <ion-card>
          <ion-card-header>
            <ion-card-title>
              <ion-icon name="shield-checkmark-outline" style="vertical-align: middle; margin-right: 8px"></ion-icon>
              Setup Two-Factor Authentication
            </ion-card-title>
          </ion-card-header>
          <ion-card-content>
            @if (setup()) {
              <p>Scan this QR code with your authenticator app:</p>
              <div style="text-align: center; margin: 16px 0">
                <img [src]="setup()!.qr_url" alt="2FA QR Code" style="max-width: 200px" />
              </div>
              <p style="font-size: 0.8rem; color: var(--ion-color-medium)">
                Or enter this secret manually: <code>{{ setup()!.secret }}</code>
              </p>

              <ion-item style="margin-top: 16px">
                <ion-label position="stacked">Verification Code</ion-label>
                <ion-input type="text" [(ngModel)]="verifyCode" placeholder="Enter 6-digit code" maxlength="6"></ion-input>
              </ion-item>
              <ion-button expand="block" color="primary" style="margin-top: 12px"
                (click)="verify()" [disabled]="verifying() || verifyCode.length < 6">
                @if (verifying()) {
                  <ion-spinner name="crescent" style="width: 16px; height: 16px; margin-right: 8px"></ion-spinner>
                }
                Enable 2FA
              </ion-button>
            } @else {
              <p>Add an extra layer of security to your account.</p>
              <ion-button expand="block" fill="outline" (click)="beginSetup()" [disabled]="settingUp()">
                @if (settingUp()) {
                  <ion-spinner name="crescent" style="width: 16px; height: 16px; margin-right: 8px"></ion-spinner>
                }
                Begin Setup
              </ion-button>
            }
          </ion-card-content>
        </ion-card>
      }
    </ion-content>
  `,
})
export class TwoFactorComponent implements OnInit {
  loading = signal(true);
  enabled = signal(false);
  setup = signal<TwoFactorSetup | null>(null);
  settingUp = signal(false);
  verifyCode = '';
  verifying = signal(false);
  disablePassword = '';
  disabling = signal(false);
  recoveryCodes = signal<string[]>([]);
  loadingCodes = signal(false);

  constructor(
    private api: ApiService,
    private toastCtrl: ToastController,
    private alertCtrl: AlertController,
  ) {}

  ngOnInit(): void {
    this.api.get<{ enabled: boolean }>('/2fa/setup').subscribe({
      next: (data) => { this.enabled.set(data.enabled); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }

  beginSetup(): void {
    this.settingUp.set(true);
    this.api.get<TwoFactorSetup>('/2fa/setup').subscribe({
      next: (data) => { this.setup.set(data); this.settingUp.set(false); },
      error: () => this.settingUp.set(false),
    });
  }

  verify(): void {
    this.verifying.set(true);
    this.api.post<{ recovery_codes: string[] }>('/2fa/setup', { code: this.verifyCode }).subscribe({
      next: async (data) => {
        this.verifying.set(false);
        this.enabled.set(true);
        this.setup.set(null);
        this.recoveryCodes.set(data.recovery_codes || []);
        const toast = await this.toastCtrl.create({
          message: '2FA enabled successfully', color: 'success', duration: 3000, position: 'bottom',
        });
        await toast.present();
      },
      error: async () => {
        this.verifying.set(false);
        const toast = await this.toastCtrl.create({
          message: 'Invalid code. Please try again.', color: 'danger', duration: 3000, position: 'bottom',
        });
        await toast.present();
      },
    });
  }

  async disable(): Promise<void> {
    if (!this.disablePassword) return;
    const alert = await this.alertCtrl.create({
      header: 'Disable 2FA',
      message: 'Are you sure you want to disable two-factor authentication?',
      buttons: [
        { text: 'Cancel', role: 'cancel' },
        { text: 'Disable', role: 'destructive', handler: () => {
          this.disabling.set(true);
          this.api.post<void>('/2fa/disable', { password: this.disablePassword }).subscribe({
            next: async () => {
              this.disabling.set(false);
              this.enabled.set(false);
              this.recoveryCodes.set([]);
              this.disablePassword = '';
              const toast = await this.toastCtrl.create({
                message: '2FA disabled', color: 'success', duration: 3000, position: 'bottom',
              });
              await toast.present();
            },
            error: async () => {
              this.disabling.set(false);
              const toast = await this.toastCtrl.create({
                message: 'Invalid password', color: 'danger', duration: 3000, position: 'bottom',
              });
              await toast.present();
            },
          });
        }},
      ],
    });
    await alert.present();
  }

  loadRecoveryCodes(): void {
    this.loadingCodes.set(true);
    this.api.get<{ codes: string[] }>('/2fa/recovery-codes').subscribe({
      next: (data) => { this.recoveryCodes.set(data.codes); this.loadingCodes.set(false); },
      error: () => this.loadingCodes.set(false),
    });
  }
}
