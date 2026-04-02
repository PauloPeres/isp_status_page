import { Component, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
  IonCard, IonCardHeader, IonCardTitle, IonCardContent,
  IonItem, IonLabel, IonIcon, IonSpinner, IonChip,
  ToastController,
} from '@ionic/angular/standalone';
import { ReportService } from './report.service';
import { addIcons } from 'ionicons';
import {
  downloadOutline, statsChartOutline, alertCircleOutline, timerOutline, calendarOutline,
} from 'ionicons/icons';

addIcons({ downloadOutline, statsChartOutline, alertCircleOutline, timerOutline, calendarOutline });

interface DatePreset {
  label: string;
  start: string;
  end: string;
}

@Component({
  selector: 'app-report-list',
  standalone: true,
  imports: [
    CommonModule, FormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
    IonCard, IonCardHeader, IonCardTitle, IonCardContent,
    IonItem, IonLabel, IonIcon, IonSpinner, IonChip,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>Reports</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <!-- Shared Date Range -->
      <ion-card>
        <ion-card-header>
          <ion-card-title style="font-size: 1rem">
            <ion-icon name="calendar-outline" style="vertical-align: middle; margin-right: 8px"></ion-icon>
            Date Range
          </ion-card-title>
        </ion-card-header>
        <ion-card-content>
          <div class="presets">
            @for (preset of presets; track preset.label) {
              <ion-chip
                [outline]="activePreset !== preset.label"
                [color]="activePreset === preset.label ? 'primary' : 'medium'"
                (click)="applyPreset(preset)"
              >{{ preset.label }}</ion-chip>
            }
          </div>
          <div class="date-row">
            <div class="date-field">
              <label class="date-label">From</label>
              <input type="date" [(ngModel)]="startDate" (change)="activePreset = 'Custom'" class="date-input" />
            </div>
            <div class="date-field">
              <label class="date-label">To</label>
              <input type="date" [(ngModel)]="endDate" (change)="activePreset = 'Custom'" class="date-input" />
            </div>
          </div>
        </ion-card-content>
      </ion-card>

      <!-- Report Cards -->
      <ion-card>
        <ion-card-header>
          <ion-card-title>
            <ion-icon name="stats-chart-outline" style="vertical-align: middle; margin-right: 8px"></ion-icon>
            Uptime Report
          </ion-card-title>
        </ion-card-header>
        <ion-card-content>
          <p class="report-desc">Uptime percentage, total checks, success/failure counts per monitor.</p>
          <ion-button expand="block" fill="outline" (click)="download('uptime')" [disabled]="downloading() === 'uptime'">
            @if (downloading() === 'uptime') {
              <ion-spinner name="crescent" style="width: 16px; height: 16px; margin-right: 8px"></ion-spinner>
            } @else {
              <ion-icon name="download-outline" slot="start"></ion-icon>
            }
            Download CSV
          </ion-button>
        </ion-card-content>
      </ion-card>

      <ion-card>
        <ion-card-header>
          <ion-card-title>
            <ion-icon name="alert-circle-outline" style="vertical-align: middle; margin-right: 8px"></ion-icon>
            Incidents Report
          </ion-card-title>
        </ion-card-header>
        <ion-card-content>
          <p class="report-desc">All incidents with severity, status, duration, and resolution times.</p>
          <ion-button expand="block" fill="outline" (click)="download('incidents')" [disabled]="downloading() === 'incidents'">
            @if (downloading() === 'incidents') {
              <ion-spinner name="crescent" style="width: 16px; height: 16px; margin-right: 8px"></ion-spinner>
            } @else {
              <ion-icon name="download-outline" slot="start"></ion-icon>
            }
            Download CSV
          </ion-button>
        </ion-card-content>
      </ion-card>

      <ion-card>
        <ion-card-header>
          <ion-card-title>
            <ion-icon name="timer-outline" style="vertical-align: middle; margin-right: 8px"></ion-icon>
            Response Times Report
          </ion-card-title>
        </ion-card-header>
        <ion-card-content>
          <p class="report-desc">Average, minimum, and maximum response times per monitor.</p>
          <ion-button expand="block" fill="outline" (click)="download('response-times')" [disabled]="downloading() === 'response-times'">
            @if (downloading() === 'response-times') {
              <ion-spinner name="crescent" style="width: 16px; height: 16px; margin-right: 8px"></ion-spinner>
            } @else {
              <ion-icon name="download-outline" slot="start"></ion-icon>
            }
            Download CSV
          </ion-button>
        </ion-card-content>
      </ion-card>
    </ion-content>
  `,
  styles: [`
    .presets {
      display: flex;
      flex-wrap: wrap;
      gap: 4px;
      margin-bottom: 12px;
    }
    .presets ion-chip {
      cursor: pointer;
      height: 30px;
      font-size: 0.8rem;
    }
    .date-row {
      display: flex;
      gap: 12px;
    }
    .date-field {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    .date-label {
      font-size: 0.7rem;
      color: var(--ion-color-medium);
      text-transform: uppercase;
      letter-spacing: 0.05em;
      font-weight: 600;
    }
    .date-input {
      padding: 8px 10px;
      border: 1px solid var(--ion-color-light-shade);
      border-radius: 6px;
      font-size: 0.9rem;
      background: var(--ion-item-background);
      color: var(--ion-text-color);
      font-family: inherit;
      width: 100%;
    }
    .date-input:focus {
      outline: none;
      border-color: var(--ion-color-primary);
    }
    .report-desc {
      color: var(--ion-color-medium);
      font-size: 0.85rem;
      margin-bottom: 12px;
    }
    ion-button {
      --border-radius: 8px;
    }
    @media (max-width: 480px) {
      .date-row {
        flex-direction: column;
      }
    }
  `],
})
export class ReportListComponent {
  startDate = '';
  endDate = '';
  activePreset = 'Last 30 days';
  downloading = signal<string | null>(null);

  presets: DatePreset[] = [];

  constructor(private service: ReportService, private toastCtrl: ToastController) {
    this.buildPresets();
    this.applyPreset(this.presets.find(p => p.label === 'Last 30 days')!);
  }

  private buildPresets(): void {
    const now = new Date();
    const fmt = (d: Date) => d.toISOString().slice(0, 10);

    const daysAgo = (n: number) => {
      const d = new Date(now);
      d.setDate(d.getDate() - n);
      return d;
    };

    const thisMonthStart = new Date(now.getFullYear(), now.getMonth(), 1);
    const lastMonthStart = new Date(now.getFullYear(), now.getMonth() - 1, 1);
    const lastMonthEnd = new Date(now.getFullYear(), now.getMonth(), 0);
    const thisQuarterMonth = Math.floor(now.getMonth() / 3) * 3;
    const thisQuarterStart = new Date(now.getFullYear(), thisQuarterMonth, 1);
    const yearStart = new Date(now.getFullYear(), 0, 1);

    this.presets = [
      { label: 'Last 7 days', start: fmt(daysAgo(7)), end: fmt(now) },
      { label: 'Last 30 days', start: fmt(daysAgo(30)), end: fmt(now) },
      { label: 'This month', start: fmt(thisMonthStart), end: fmt(now) },
      { label: 'Last month', start: fmt(lastMonthStart), end: fmt(lastMonthEnd) },
      { label: 'This quarter', start: fmt(thisQuarterStart), end: fmt(now) },
      { label: 'Year to date', start: fmt(yearStart), end: fmt(now) },
    ];
  }

  applyPreset(preset: DatePreset): void {
    this.startDate = preset.start;
    this.endDate = preset.end;
    this.activePreset = preset.label;
  }

  download(type: 'uptime' | 'incidents' | 'response-times'): void {
    if (this.startDate > this.endDate) {
      this.showToast('Start date must be before end date', 'warning');
      return;
    }

    this.downloading.set(type);
    const params = { start: this.startDate, end: this.endDate };
    const method = type === 'uptime' ? this.service.downloadUptime(params)
      : type === 'incidents' ? this.service.downloadIncidents(params)
      : this.service.downloadResponseTimes(params);

    method.subscribe({
      next: (blob) => {
        this.downloading.set(null);
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${type}-report-${this.startDate}-to-${this.endDate}.csv`;
        a.click();
        URL.revokeObjectURL(url);
        this.showToast('Report downloaded successfully', 'success');
      },
      error: (err: any) => {
        this.downloading.set(null);
        this.showToast(err?.message || 'Failed to download report', 'danger');
      },
    });
  }

  private async showToast(message: string, color: string): Promise<void> {
    const toast = await this.toastCtrl.create({
      message, color, duration: 3000, position: 'bottom',
    });
    await toast.present();
  }
}
