import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
  IonList, IonItem, IonInput, IonToggle, IonLabel, IonNote, IonIcon,
  IonSegment, IonSegmentButton, IonSpinner,
  ToastController,
} from '@ionic/angular/standalone';
import { SettingsService, Settings } from './settings.service';
import { addIcons } from 'ionicons';
import { settingsOutline } from 'ionicons/icons';

addIcons({ settingsOutline });

@Component({
  selector: 'app-settings',
  standalone: true,
  imports: [
    CommonModule, FormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
    IonList, IonItem, IonInput, IonToggle, IonLabel, IonNote, IonIcon,
    IonSegment, IonSegmentButton, IonSpinner,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>Settings</ion-title>
        <ion-buttons slot="end">
          <ion-button (click)="onSave()" fill="solid" color="primary" size="small" [disabled]="saving()">
            @if (saving()) {
              <ion-spinner name="crescent" style="width: 16px; height: 16px"></ion-spinner>
            } @else {
              Save
            }
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
      <ion-toolbar>
        <ion-segment [(ngModel)]="activeTab" (ionChange)="onTabChange()">
          <ion-segment-button value="general"><ion-label>General</ion-label></ion-segment-button>
          <ion-segment-button value="notifications"><ion-label>Notifications</ion-label></ion-segment-button>
          <ion-segment-button value="channels"><ion-label>Channels</ion-label></ion-segment-button>
        </ion-segment>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      @if (loading()) {
        <div style="text-align: center; padding: 2rem">
          <ion-spinner name="crescent"></ion-spinner>
        </div>
      } @else {
        @switch (activeTab) {
          @case ('general') {
            <ion-list>
              <ion-item>
                <ion-input label="Organization Name" labelPlacement="stacked" [(ngModel)]="settings['org_name']" placeholder="My Organization"></ion-input>
              </ion-item>
              <ion-item>
                <ion-input label="Timezone" labelPlacement="stacked" [(ngModel)]="settings['timezone']" placeholder="UTC"></ion-input>
              </ion-item>
              <ion-item>
                <ion-input label="Date Format" labelPlacement="stacked" [(ngModel)]="settings['date_format']" placeholder="YYYY-MM-DD"></ion-input>
              </ion-item>
              <ion-item>
                <ion-input label="Default Check Interval (s)" labelPlacement="stacked" [(ngModel)]="settings['default_check_interval']" type="number" placeholder="300"></ion-input>
              </ion-item>
            </ion-list>
          }
          @case ('notifications') {
            <ion-list>
              <ion-item>
                <ion-toggle [(ngModel)]="settings['email_notifications_enabled']">Email Notifications</ion-toggle>
              </ion-item>
              <ion-item>
                <ion-input label="From Email" labelPlacement="stacked" [(ngModel)]="settings['notification_from_email']" placeholder="noreply&#64;example.com"></ion-input>
              </ion-item>
              <ion-item>
                <ion-input label="From Name" labelPlacement="stacked" [(ngModel)]="settings['notification_from_name']" placeholder="Status Page"></ion-input>
              </ion-item>
              <ion-item>
                <ion-toggle [(ngModel)]="settings['digest_enabled']">Daily Digest</ion-toggle>
              </ion-item>
              <ion-item>
                <ion-input label="Digest Time" labelPlacement="stacked" [(ngModel)]="settings['digest_time']" placeholder="09:00"></ion-input>
              </ion-item>
            </ion-list>
          }
          @case ('channels') {
            <ion-list>
              <ion-item>
                <ion-input label="SMTP Host" labelPlacement="stacked" [(ngModel)]="settings['smtp_host']" placeholder="smtp.example.com"></ion-input>
              </ion-item>
              <ion-item>
                <ion-input label="SMTP Port" labelPlacement="stacked" [(ngModel)]="settings['smtp_port']" type="number" placeholder="587"></ion-input>
              </ion-item>
              <ion-item>
                <ion-input label="SMTP Username" labelPlacement="stacked" [(ngModel)]="settings['smtp_username']" placeholder="user"></ion-input>
              </ion-item>
              <ion-item>
                <ion-input label="SMTP Password" labelPlacement="stacked" [(ngModel)]="settings['smtp_password']" type="password" placeholder="password"></ion-input>
              </ion-item>
              <ion-item>
                <ion-input label="Telegram Bot Token" labelPlacement="stacked" [(ngModel)]="settings['telegram_bot_token']" placeholder="Bot token"></ion-input>
              </ion-item>
              <ion-item>
                <ion-input label="Webhook URL" labelPlacement="stacked" [(ngModel)]="settings['webhook_url']" placeholder="https://hooks.example.com/..."></ion-input>
              </ion-item>
            </ion-list>
          }
        }
      }
    </ion-content>
  `,
})
export class SettingsComponent implements OnInit {
  settings: Settings = {};
  activeTab = 'general';
  loading = signal(false);
  saving = signal(false);

  constructor(private service: SettingsService, private toastCtrl: ToastController) {}

  ngOnInit(): void { this.loadSettings(); }

  loadSettings(): void {
    this.loading.set(true);
    this.service.get().subscribe({
      next: (data) => { this.settings = data || {}; this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }

  onTabChange(): void {}

  onSave(): void {
    this.saving.set(true);
    this.service.save(this.settings).subscribe({
      next: async () => {
        this.saving.set(false);
        const toast = await this.toastCtrl.create({ message: 'Settings saved', color: 'success', duration: 2000, position: 'bottom' });
        await toast.present();
      },
      error: async () => {
        this.saving.set(false);
        const toast = await this.toastCtrl.create({ message: 'Failed to save settings', color: 'danger', duration: 3000, position: 'bottom' });
        await toast.present();
      },
    });
  }
}
