import { Component, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
  IonCard, IonCardHeader, IonCardTitle, IonCardContent,
  IonItem, IonLabel, IonInput, IonIcon, IonSpinner,
  ToastController,
} from '@ionic/angular/standalone';
import { ReportService } from './report.service';
import { addIcons } from 'ionicons';
import { downloadOutline, statsChartOutline, alertCircleOutline, timerOutline } from 'ionicons/icons';

addIcons({ downloadOutline, statsChartOutline, alertCircleOutline, timerOutline });

@Component({
  selector: 'app-report-list',
  standalone: true,
  imports: [
    CommonModule, FormsModule,
    IonHeader, IonToolbar, IonTitle, IonContent, IonMenuButton, IonButtons, IonButton,
    IonCard, IonCardHeader, IonCardTitle, IonCardContent,
    IonItem, IonLabel, IonInput, IonIcon, IonSpinner,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start"><ion-menu-button></ion-menu-button></ion-buttons>
        <ion-title>Reports</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <ion-card>
        <ion-card-header>
          <ion-card-title>
            <ion-icon name="stats-chart-outline" style="vertical-align: middle; margin-right: 8px"></ion-icon>
            Uptime Report
          </ion-card-title>
        </ion-card-header>
        <ion-card-content>
          <ion-item>
            <ion-label position="stacked">Start Date</ion-label>
            <ion-input type="date" [(ngModel)]="uptimeStart"></ion-input>
          </ion-item>
          <ion-item>
            <ion-label position="stacked">End Date</ion-label>
            <ion-input type="date" [(ngModel)]="uptimeEnd"></ion-input>
          </ion-item>
          <ion-button expand="block" fill="outline" style="margin-top: 12px" (click)="download('uptime')" [disabled]="downloading() === 'uptime'">
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
          <ion-item>
            <ion-label position="stacked">Start Date</ion-label>
            <ion-input type="date" [(ngModel)]="incidentsStart"></ion-input>
          </ion-item>
          <ion-item>
            <ion-label position="stacked">End Date</ion-label>
            <ion-input type="date" [(ngModel)]="incidentsEnd"></ion-input>
          </ion-item>
          <ion-button expand="block" fill="outline" style="margin-top: 12px" (click)="download('incidents')" [disabled]="downloading() === 'incidents'">
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
          <ion-item>
            <ion-label position="stacked">Start Date</ion-label>
            <ion-input type="date" [(ngModel)]="responseStart"></ion-input>
          </ion-item>
          <ion-item>
            <ion-label position="stacked">End Date</ion-label>
            <ion-input type="date" [(ngModel)]="responseEnd"></ion-input>
          </ion-item>
          <ion-button expand="block" fill="outline" style="margin-top: 12px" (click)="download('response-times')" [disabled]="downloading() === 'response-times'">
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
})
export class ReportListComponent {
  uptimeStart = '';
  uptimeEnd = '';
  incidentsStart = '';
  incidentsEnd = '';
  responseStart = '';
  responseEnd = '';
  downloading = signal<string | null>(null);

  constructor(private service: ReportService, private toastCtrl: ToastController) {
    const now = new Date();
    const thirtyDaysAgo = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
    const end = now.toISOString().slice(0, 10);
    const start = thirtyDaysAgo.toISOString().slice(0, 10);
    this.uptimeStart = this.incidentsStart = this.responseStart = start;
    this.uptimeEnd = this.incidentsEnd = this.responseEnd = end;
  }

  download(type: 'uptime' | 'incidents' | 'response-times'): void {
    this.downloading.set(type);
    const params = this.getParams(type);
    const method = type === 'uptime' ? this.service.downloadUptime(params)
      : type === 'incidents' ? this.service.downloadIncidents(params)
      : this.service.downloadResponseTimes(params);

    method.subscribe({
      next: async (blob) => {
        this.downloading.set(null);
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${type}-report.csv`;
        a.click();
        URL.revokeObjectURL(url);
      },
      error: async (err: any) => {
        this.downloading.set(null);
        const toast = await this.toastCtrl.create({
          message: err?.message || 'Failed to download report', color: 'danger', duration: 4000, position: 'bottom',
        });
        await toast.present();
      },
    });
  }

  private getParams(type: string): { start: string; end: string } {
    switch (type) {
      case 'uptime': return { start: this.uptimeStart, end: this.uptimeEnd };
      case 'incidents': return { start: this.incidentsStart, end: this.incidentsEnd };
      default: return { start: this.responseStart, end: this.responseEnd };
    }
  }
}
