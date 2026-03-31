import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import {
  IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
  IonList, IonListHeader, IonItem, IonInput, IonSelect, IonSelectOption, IonToggle, IonSpinner, IonNote, IonCheckbox, IonLabel,
  ToastController,
} from '@ionic/angular/standalone';
import { ScheduledReportService } from './scheduled-report.service';
import { ApiService } from '../../core/services/api.service';

@Component({
  selector: 'app-scheduled-report-form',
  standalone: true,
  imports: [
    CommonModule, FormsModule, RouterLink,
    IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton,
    IonList, IonListHeader, IonItem, IonInput, IonSelect, IonSelectOption, IonToggle, IonSpinner, IonNote, IonCheckbox, IonLabel,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button defaultHref="/scheduled-reports"></ion-back-button>
        </ion-buttons>
        <ion-title>{{ isEdit ? 'Edit' : 'New' }} Scheduled Report</ion-title>
      </ion-toolbar>
    </ion-header>
    <ion-content class="ion-padding">
      @if (loadingData) {
        <div style="text-align: center; padding: 2rem">
          <ion-spinner name="crescent"></ion-spinner>
        </div>
      } @else {
      <ion-list>
        <ion-item>
          <ion-input label="Name" labelPlacement="floating" [(ngModel)]="form.name" name="name" required></ion-input>
        </ion-item>
        @if (submitted && !form.name) {
          <ion-note color="danger" class="field-error">Name is required</ion-note>
        }
        <ion-item>
          <ion-select label="Report Type" labelPlacement="floating" [(ngModel)]="form.report_type" name="report_type">
            <ion-select-option value="uptime">Uptime</ion-select-option>
            <ion-select-option value="incidents">Incidents</ion-select-option>
            <ion-select-option value="performance">Performance</ion-select-option>
            <ion-select-option value="sla">SLA</ion-select-option>
          </ion-select>
        </ion-item>
        <ion-item>
          <ion-select label="Frequency" labelPlacement="floating" [(ngModel)]="form.frequency" name="frequency">
            <ion-select-option value="daily">Daily</ion-select-option>
            <ion-select-option value="weekly">Weekly</ion-select-option>
            <ion-select-option value="monthly">Monthly</ion-select-option>
          </ion-select>
        </ion-item>
        <ion-list-header><ion-label>Team Members</ion-label></ion-list-header>
        @for (member of teamMembers(); track member.id) {
          <ion-item>
            <ion-checkbox [(ngModel)]="member.selected" [name]="'member_' + member.id" labelPlacement="end">
              {{ member.email }}
              @if (member.name) { <span style="color: var(--ion-color-medium); margin-left: 4px">({{ member.name }})</span> }
            </ion-checkbox>
          </ion-item>
        }

        <ion-item>
          <ion-input label="Additional Emails" labelPlacement="stacked" [(ngModel)]="form.recipients_text" name="recipients" placeholder="extra@example.com, another@example.com"></ion-input>
        </ion-item>
        @if (submitted && !form.recipients_text && !hasSelectedMembers()) {
          <ion-note color="danger" class="field-error">At least one recipient is required</ion-note>
        }
        <ion-item>
          <ion-toggle [(ngModel)]="form.active" name="active">Active</ion-toggle>
        </ion-item>
      </ion-list>
      <ion-button expand="block" (click)="onSave()" [disabled]="saving">
        @if (saving) { <ion-spinner name="crescent"></ion-spinner> }
        @else { {{ isEdit ? 'Update' : 'Save' }} Report }
      </ion-button>
      }
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
export class ScheduledReportFormComponent implements OnInit {
  form: any = { name: '', report_type: 'uptime', frequency: 'weekly', recipients_text: '', active: true };
  saving = false;
  submitted = false;
  isEdit = false;
  editId: number | null = null;
  loadingData = false;
  teamMembers = signal<any[]>([]);

  constructor(
    private service: ScheduledReportService,
    private router: Router,
    private route: ActivatedRoute,
    private toastCtrl: ToastController,
    private api: ApiService,
  ) {}

  hasSelectedMembers(): boolean {
    return this.teamMembers().some(m => m.selected);
  }

  ngOnInit(): void {
    // Load team members
    this.api.get<any>('/users').subscribe((data) => {
      const members = (data.users || data.items || []).map((u: any) => ({
        ...u,
        selected: false,
      }));
      this.teamMembers.set(members);

      // If editing, pre-select members after they are loaded
      if (this.isEdit && this._savedRecipients.length) {
        this._applyRecipientSelection(this._savedRecipients);
      }
    });

    const idParam = this.route.snapshot.paramMap.get('id');
    if (idParam) {
      this.isEdit = true;
      this.editId = +idParam;
      this.loadingData = true;
      this.service.get(this.editId).subscribe({
        next: (item) => {
          const savedRecipients: string[] = Array.isArray(item.recipients_list) ? item.recipients_list : (Array.isArray(item.recipients) ? item.recipients : []);
          this._savedRecipients = savedRecipients;

          this.form = {
            name: item.name || '',
            report_type: item.report_type || 'uptime',
            frequency: item.frequency || 'weekly',
            recipients_text: '',
            active: item.active ?? true,
          };

          // Pre-select team members and set extra emails
          this._applyRecipientSelection(savedRecipients);

          this.loadingData = false;
        },
        error: () => {
          this.loadingData = false;
          this.router.navigate(['/scheduled-reports']);
        },
      });
    }
  }

  private _savedRecipients: string[] = [];

  private _applyRecipientSelection(savedRecipients: string[]): void {
    const currentMembers = this.teamMembers();
    if (currentMembers.length) {
      this.teamMembers.update(members =>
        members.map(m => ({ ...m, selected: savedRecipients.includes(m.email) }))
      );
      const teamEmails = currentMembers.map(m => m.email);
      this.form.recipients_text = savedRecipients.filter((r: string) => !teamEmails.includes(r)).join(', ');
    }
  }

  onSave(): void {
    this.submitted = true;
    if (!this.form.name || (!this.form.recipients_text && !this.hasSelectedMembers())) return;
    this.saving = true;

    const selectedEmails = this.teamMembers().filter(m => m.selected).map(m => m.email);
    const extraEmails = (this.form.recipients_text || '').split(',').map((e: string) => e.trim()).filter((e: string) => e);
    const allRecipients = [...new Set([...selectedEmails, ...extraEmails])];

    const payload = {
      name: this.form.name,
      report_type: this.form.report_type,
      frequency: this.form.frequency,
      recipients: allRecipients,
      active: this.form.active,
    };

    const request$ = this.isEdit
      ? this.service.update(this.editId!, payload)
      : this.service.create(payload);

    request$.subscribe({
      next: async () => {
        this.saving = false;
        const toast = await this.toastCtrl.create({
          message: this.isEdit ? 'Report updated' : 'Report scheduled',
          color: 'success', duration: 2000, position: 'bottom',
        });
        await toast.present();
        this.router.navigate(['/scheduled-reports']);
      },
      error: async (err: any) => {
        this.saving = false;
        const toast = await this.toastCtrl.create({
          message: err?.message || (this.isEdit ? 'Failed to update report' : 'Failed to create report'),
          color: 'danger', duration: 4000, position: 'bottom',
        });
        await toast.present();
      },
    });
  }
}
