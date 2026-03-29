import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import {
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonButtons,
  IonBackButton,
  IonButton,
  IonList,
  IonItem,
  IonInput,
  IonTextarea,
  IonSelect,
  IonSelectOption,
  ToastController,
} from '@ionic/angular/standalone';
import { IncidentService } from './incident.service';
import { MonitorService } from '../monitors/monitor.service';
import { Monitor } from '../../core/models/monitor.model';
import { signal } from '@angular/core';

@Component({
  selector: 'app-incident-form',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    IonHeader,
    IonToolbar,
    IonTitle,
    IonContent,
    IonButtons,
    IonBackButton,
    IonButton,
    IonList,
    IonItem,
    IonInput,
    IonTextarea,
    IonSelect,
    IonSelectOption,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button defaultHref="/incidents"></ion-back-button>
        </ion-buttons>
        <ion-title>New Incident</ion-title>
        <ion-buttons slot="end">
          <ion-button
            (click)="onSave()"
            fill="solid"
            color="primary"
            [disabled]="!form.title"
          >
            Create
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      <ion-list>
        <ion-item>
          <ion-input
            label="Title"
            labelPlacement="stacked"
            [(ngModel)]="form.title"
            placeholder="Brief incident title"
          ></ion-input>
        </ion-item>
        <ion-item>
          <ion-textarea
            label="Description"
            labelPlacement="stacked"
            [(ngModel)]="form.description"
            placeholder="Describe the incident..."
            [autoGrow]="true"
            [rows]="3"
          ></ion-textarea>
        </ion-item>
        <ion-item>
          <ion-select
            label="Monitor"
            [(ngModel)]="form.monitor_id"
            interface="popover"
            placeholder="Select monitor"
          >
            @for (monitor of monitors(); track monitor.id) {
              <ion-select-option [value]="monitor.id">
                {{ monitor.name }}
              </ion-select-option>
            }
          </ion-select>
        </ion-item>
        <ion-item>
          <ion-select
            label="Severity"
            [(ngModel)]="form.severity"
            interface="popover"
          >
            <ion-select-option value="critical">Critical</ion-select-option>
            <ion-select-option value="major">Major</ion-select-option>
            <ion-select-option value="minor">Minor</ion-select-option>
            <ion-select-option value="info">Info</ion-select-option>
          </ion-select>
        </ion-item>
        <ion-item>
          <ion-select
            label="Status"
            [(ngModel)]="form.status"
            interface="popover"
          >
            <ion-select-option value="investigating"
              >Investigating</ion-select-option
            >
            <ion-select-option value="identified"
              >Identified</ion-select-option
            >
            <ion-select-option value="monitoring"
              >Monitoring</ion-select-option
            >
          </ion-select>
        </ion-item>
      </ion-list>
    </ion-content>
  `,
})
export class IncidentFormComponent {
  monitors = signal<Monitor[]>([]);
  form = {
    title: '',
    description: '',
    monitor_id: null as number | null,
    severity: 'major' as string,
    status: 'investigating' as string,
  };

  constructor(
    private incidentService: IncidentService,
    private monitorService: MonitorService,
    private router: Router,
    private toastCtrl: ToastController,
  ) {
    this.monitorService.getMonitors({ limit: 100 }).subscribe((data) => {
      this.monitors.set(data.items);
    });
  }

  onSave(): void {
    if (!this.form.title) return;

    this.incidentService.createIncident(this.form as any).subscribe({
      next: (incident) => {
        this.router.navigate(['/incidents', incident.id]);
      },
      error: async () => {
        const toast = await this.toastCtrl.create({
          message: 'Failed to create incident',
          duration: 2000,
          color: 'danger',
        });
        await toast.present();
      },
    });
  }
}
