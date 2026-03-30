import { Component, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
  IonList, IonItem, IonLabel, IonSelect, IonSelectOption, IonTextarea, IonNote,
  IonCard, IonCardHeader, IonCardTitle, IonCardContent, IonIcon, IonSpinner, IonBadge,
  ToastController,
} from '@ionic/angular/standalone';
import { addIcons } from 'ionicons';
import { cloudUploadOutline, checkmarkCircleOutline, alertCircleOutline } from 'ionicons/icons';
import { ApiService } from '../../core/services/api.service';

addIcons({
  'cloud-upload-outline': cloudUploadOutline,
  'checkmark-circle-outline': checkmarkCircleOutline,
  'alert-circle-outline': alertCircleOutline,
});

interface ImportResult {
  created: number;
  total_parsed: number;
  format_detected: string;
  errors: string[];
}

@Component({
  selector: 'app-monitor-import',
  standalone: true,
  imports: [
    CommonModule, FormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
    IonList, IonItem, IonLabel, IonSelect, IonSelectOption, IonTextarea, IonNote,
    IonCard, IonCardHeader, IonCardTitle, IonCardContent, IonIcon, IonSpinner, IonBadge,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button defaultHref="/monitors"></ion-back-button>
        </ion-buttons>
        <ion-title>Import Monitors</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <div class="import-container">
        <ion-card>
          <ion-card-header>
            <ion-card-title>
              <ion-icon name="cloud-upload-outline"></ion-icon>
              Import from another platform
            </ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <p class="help-text">
              Paste your exported monitor data below. We auto-detect the format, or you can select it manually.
            </p>

            <ion-list>
              <ion-item>
                <ion-select
                  label="Source Platform"
                  labelPlacement="stacked"
                  [(ngModel)]="format"
                  interface="popover"
                  placeholder="Auto-detect"
                >
                  <ion-select-option value="">Auto-detect</ion-select-option>
                  <ion-select-option value="uptimerobot">UptimeRobot</ion-select-option>
                  <ion-select-option value="pingdom">Pingdom</ion-select-option>
                  <ion-select-option value="betteruptime">Better Uptime</ion-select-option>
                  <ion-select-option value="csv">Generic CSV</ion-select-option>
                </ion-select>
              </ion-item>

              <ion-item>
                <ion-textarea
                  label="Export Data (CSV or JSON)"
                  labelPlacement="stacked"
                  [(ngModel)]="content"
                  placeholder="Paste your monitor export data here..."
                  [rows]="10"
                  [autoGrow]="true"
                ></ion-textarea>
              </ion-item>
            </ion-list>

            @if (!content) {
              <ion-note color="medium" class="format-help">
                <strong>How to export:</strong><br>
                UptimeRobot: My Settings > Export > JSON or CSV<br>
                Pingdom: Integrations > Monitoring > Export CSV<br>
                Better Uptime: API > GET /monitors (JSON)
              </ion-note>
            }

            <ion-button
              expand="block"
              (click)="onImport()"
              [disabled]="importing() || !content"
              class="import-btn"
            >
              @if (importing()) {
                <ion-spinner name="crescent"></ion-spinner>
              } @else {
                Import Monitors
              }
            </ion-button>
          </ion-card-content>
        </ion-card>

        <!-- Results -->
        @if (result()) {
          <ion-card>
            <ion-card-header>
              <ion-card-title>
                @if (result()!.created > 0) {
                  <ion-icon name="checkmark-circle-outline" color="success"></ion-icon>
                  Import Complete
                } @else {
                  <ion-icon name="alert-circle-outline" color="warning"></ion-icon>
                  Import Result
                }
              </ion-card-title>
            </ion-card-header>
            <ion-card-content>
              <div class="result-stats">
                <ion-badge color="success">{{ result()!.created }} created</ion-badge>
                <ion-badge color="medium">{{ result()!.total_parsed }} parsed</ion-badge>
                <ion-badge color="tertiary">{{ result()!.format_detected }}</ion-badge>
              </div>

              @if (result()!.errors.length > 0) {
                <div class="errors-section">
                  <h4>Issues ({{ result()!.errors.length }})</h4>
                  <ion-list>
                    @for (error of result()!.errors; track error) {
                      <ion-item>
                        <ion-label class="ion-text-wrap">
                          <p style="color: var(--ion-color-danger)">{{ error }}</p>
                        </ion-label>
                      </ion-item>
                    }
                  </ion-list>
                </div>
              }

              @if (result()!.created > 0) {
                <ion-button expand="block" fill="outline" routerLink="/monitors">
                  View Monitors
                </ion-button>
              }
            </ion-card-content>
          </ion-card>
        }
      </div>
    </ion-content>
  `,
  styles: [`
    .import-container { max-width: 700px; margin: 0 auto; }
    .help-text { color: var(--ion-color-medium); margin-bottom: 16px; line-height: 1.5; }
    .format-help { display: block; padding: 12px 16px; font-size: 0.8rem; line-height: 1.6; }
    .import-btn { margin-top: 16px; }
    .result-stats { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
    .errors-section h4 { margin: 16px 0 8px; color: var(--ion-color-danger); }
    ion-card-title ion-icon { vertical-align: middle; margin-right: 8px; }
  `],
})
export class MonitorImportComponent {
  content = '';
  format = '';
  importing = signal(false);
  result = signal<ImportResult | null>(null);

  constructor(
    private api: ApiService,
    private router: Router,
    private toastCtrl: ToastController,
  ) {}

  onImport(): void {
    if (!this.content) return;
    this.importing.set(true);
    this.result.set(null);

    const payload: any = { content: this.content };
    if (this.format) {
      payload.format = this.format;
    }

    this.api.post<ImportResult>('/monitors/import-competitor', payload).subscribe({
      next: async (data) => {
        this.importing.set(false);
        this.result.set(data);
        if (data.created > 0) {
          const toast = await this.toastCtrl.create({
            message: `${data.created} monitor(s) imported successfully`,
            color: 'success',
            duration: 3000,
            position: 'bottom',
          });
          await toast.present();
        }
      },
      error: async (err: any) => {
        this.importing.set(false);
        const toast = await this.toastCtrl.create({
          message: err?.message || 'Import failed',
          color: 'danger',
          duration: 4000,
          position: 'bottom',
        });
        await toast.present();
      },
    });
  }
}
