import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
  IonList, IonItem, IonInput, IonToggle, IonLabel, IonNote, IonIcon,
  IonSegment, IonSegmentButton, IonSpinner, IonSelect, IonSelectOption,
  IonRefresher, IonRefresherContent, IonSkeletonText,
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
    IonSegment, IonSegmentButton, IonSpinner, IonSelect, IonSelectOption,
    IonRefresher, IonRefresherContent, IonSkeletonText,
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
        </ion-segment>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <ion-refresher slot="fixed" (ionRefresh)="onRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

      @if (loading()) {
        <ion-list>
          @for (i of [1,2,3,4]; track i) {
            <ion-item>
              <ion-label>
                <ion-skeleton-text [animated]="true" style="width: 30%; height: 0.75rem; margin-bottom: 6px;"></ion-skeleton-text>
                <ion-skeleton-text [animated]="true" style="width: 70%; height: 1rem;"></ion-skeleton-text>
              </ion-label>
            </ion-item>
          }
        </ion-list>
      } @else {
        @switch (activeTab) {
          @case ('general') {
            <ion-list>
              <ion-item>
                <ion-input label="Organization Name" labelPlacement="stacked" [(ngModel)]="settings['org_name']" placeholder="My Organization"></ion-input>
              </ion-item>
              <ion-item>
                <ion-select label="Timezone" labelPlacement="stacked" [(ngModel)]="settings['timezone']" interface="popover">
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

  onRefresh(event: any): void {
    this.service.get().subscribe({
      next: (data) => { this.settings = data || {}; event.target.complete(); },
      error: () => event.target.complete(),
    });
  }

  onSave(): void {
    this.saving.set(true);
    this.service.save(this.settings).subscribe({
      next: async () => {
        this.saving.set(false);
        const toast = await this.toastCtrl.create({ message: 'Settings saved', color: 'success', duration: 2000, position: 'bottom' });
        await toast.present();
      },
      error: async (err: any) => {
        this.saving.set(false);
        const toast = await this.toastCtrl.create({ message: err?.message || 'Failed to save settings', color: 'danger', duration: 4000, position: 'bottom' });
        await toast.present();
      },
    });
  }
}
