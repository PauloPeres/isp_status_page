import { Component, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import {
  ReactiveFormsModule,
  FormBuilder,
  FormGroup,
  Validators,
} from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
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
  IonToggle,
  IonNote,
  IonSpinner,
  IonChip,
  IonLabel,
  IonIcon,
  ToastController,
} from '@ionic/angular/standalone';
import { addIcons } from 'ionicons';
import { closeCircle } from 'ionicons/icons';
import { MonitorService } from './monitor.service';
import { ApiService } from '../../core/services/api.service';

addIcons({ closeCircle });
import { MonitorType } from '../../core/models/monitor.model';
import { FieldErrorComponent } from '../../shared/components/field-error.component';
import { showApiError } from '../../core/services/plan-error.helper';

@Component({
  selector: 'app-monitor-form',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
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
    IonToggle,
    IonNote,
    IonSpinner,
    IonChip,
    IonLabel,
    IonIcon,
    FieldErrorComponent,
  ],
  template: `
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button defaultHref="/monitors"></ion-back-button>
        </ion-buttons>
        <ion-title>{{ isEdit() ? 'Edit Monitor' : 'New Monitor' }}</ion-title>
        <ion-buttons slot="end">
          <ion-button
            (click)="onSave()"
            fill="solid"
            color="primary"
            [disabled]="saving()"
          >
            @if (saving()) {
              <ion-spinner name="crescent" style="width: 20px; height: 20px"></ion-spinner>
            } @else {
              Save
            }
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding">
      @if (loading()) {
        <div style="text-align: center; padding: 2rem">
          <ion-spinner name="crescent"></ion-spinner>
        </div>
      } @else {
        <!-- Quick Setup -->
        @if (!isEdit() && !advancedMode()) {
          <div class="quick-setup">
            <h3>Quick Setup</h3>
            <p>Enter a URL to get started quickly, or switch to advanced mode.</p>
            <ion-list>
              <ion-item>
                <ion-input
                  label="URL"
                  labelPlacement="stacked"
                  placeholder="https://example.com"
                  [value]="quickUrl"
                  (ionInput)="quickUrl = $any($event).detail.value || ''"
                  type="url"
                ></ion-input>
              </ion-item>
            </ion-list>
            <div class="quick-actions">
              <ion-button (click)="onQuickCreate()" fill="solid" color="primary" [disabled]="!quickUrl">
                Create HTTP Monitor
              </ion-button>
              <ion-button (click)="advancedMode.set(true)" fill="outline" color="medium">
                Advanced Mode
              </ion-button>
            </div>
          </div>
        }

        <!-- Advanced / Edit Form -->
        @if (isEdit() || advancedMode()) {
          <form [formGroup]="form">
            <!-- Basic Info -->
            <ion-list>
              <ion-item>
                <ion-input
                  label="Name"
                  labelPlacement="stacked"
                  placeholder="My Monitor"
                  formControlName="name"
                  required
                ></ion-input>
              </ion-item>
              <app-field-error [control]="form.get('name')" label="Name"></app-field-error>

              <ion-item>
                <ion-textarea
                  label="Description"
                  labelPlacement="stacked"
                  placeholder="Optional description"
                  formControlName="description"
                  [autoGrow]="true"
                  [rows]="2"
                ></ion-textarea>
              </ion-item>

              <ion-item>
                <ion-select
                  label="Type"
                  labelPlacement="stacked"
                  formControlName="type"
                  interface="popover"
                >
                  @for (t of monitorTypes; track t.value) {
                    <ion-select-option [value]="t.value">{{ t.label }}</ion-select-option>
                  }
                </ion-select>
              </ion-item>
            </ion-list>

            <!-- Type-specific Configuration -->
            <div formGroupName="configuration">
              @switch (form.get('type')?.value) {
                @case ('http') {
                  <ion-list>
                    <ion-item>
                      <ion-input
                        label="URL"
                        labelPlacement="stacked"
                        placeholder="https://example.com"
                        formControlName="url"
                        type="url"
                      ></ion-input>
                    </ion-item>
                    <ion-item>
                      <ion-select
                        label="Method"
                        labelPlacement="stacked"
                        formControlName="method"
                        interface="popover"
                      >
                        <ion-select-option value="GET">GET</ion-select-option>
                        <ion-select-option value="POST">POST</ion-select-option>
                        <ion-select-option value="HEAD">HEAD</ion-select-option>
                        <ion-select-option value="PUT">PUT</ion-select-option>
                      </ion-select>
                    </ion-item>
                    <ion-item>
                      <ion-input
                        label="Expected Status Code"
                        labelPlacement="stacked"
                        formControlName="expected_status_code"
                        type="number"
                        placeholder="200"
                      ></ion-input>
                    </ion-item>
                    <ion-item>
                      <ion-input
                        label="Expected Content"
                        labelPlacement="stacked"
                        formControlName="expected_content"
                        placeholder="Optional string to find in response"
                      ></ion-input>
                    </ion-item>
                    <ion-item>
                      <ion-toggle formControlName="verify_ssl">Verify SSL</ion-toggle>
                    </ion-item>
                    <ion-item>
                      <ion-toggle formControlName="follow_redirects">Follow Redirects</ion-toggle>
                    </ion-item>
                  </ion-list>
                }
                @case ('ping') {
                  <ion-list>
                    <ion-item>
                      <ion-input
                        label="Host"
                        labelPlacement="stacked"
                        placeholder="192.168.1.1 or hostname"
                        formControlName="host"
                      ></ion-input>
                    </ion-item>
                    <ion-item>
                      <ion-input
                        label="Packet Count"
                        labelPlacement="stacked"
                        formControlName="packet_count"
                        type="number"
                        placeholder="4"
                      ></ion-input>
                    </ion-item>
                    <ion-item>
                      <ion-input
                        label="Max Packet Loss (%)"
                        labelPlacement="stacked"
                        formControlName="max_packet_loss"
                        type="number"
                        placeholder="0"
                      ></ion-input>
                    </ion-item>
                    <ion-item>
                      <ion-input
                        label="Max Latency (ms)"
                        labelPlacement="stacked"
                        formControlName="max_latency"
                        type="number"
                        placeholder="1000"
                      ></ion-input>
                    </ion-item>
                  </ion-list>
                }
                @case ('port') {
                  <ion-list>
                    <ion-item>
                      <ion-input
                        label="Host"
                        labelPlacement="stacked"
                        placeholder="192.168.1.1 or hostname"
                        formControlName="host"
                      ></ion-input>
                    </ion-item>
                    <ion-item>
                      <ion-input
                        label="Port"
                        labelPlacement="stacked"
                        formControlName="port"
                        type="number"
                        placeholder="443"
                      ></ion-input>
                    </ion-item>
                    <ion-item>
                      <ion-select
                        label="Protocol"
                        labelPlacement="stacked"
                        formControlName="protocol"
                        interface="popover"
                      >
                        <ion-select-option value="tcp">TCP</ion-select-option>
                        <ion-select-option value="udp">UDP</ion-select-option>
                      </ion-select>
                    </ion-item>
                  </ion-list>
                }
                @case ('api') {
                  <ion-list>
                    <ion-item>
                      <ion-input
                        label="URL"
                        labelPlacement="stacked"
                        placeholder="https://api.example.com/health"
                        formControlName="url"
                        type="url"
                      ></ion-input>
                    </ion-item>
                    <ion-item>
                      <ion-select
                        label="Method"
                        labelPlacement="stacked"
                        formControlName="method"
                        interface="popover"
                      >
                        <ion-select-option value="GET">GET</ion-select-option>
                        <ion-select-option value="POST">POST</ion-select-option>
                      </ion-select>
                    </ion-item>
                    <ion-item>
                      <ion-input
                        label="Expected Status Code"
                        labelPlacement="stacked"
                        formControlName="expected_status_code"
                        type="number"
                        placeholder="200"
                      ></ion-input>
                    </ion-item>
                    <ion-item>
                      <ion-input
                        label="JSON Path"
                        labelPlacement="stacked"
                        formControlName="json_path"
                        placeholder="status.health"
                      ></ion-input>
                    </ion-item>
                    <ion-item>
                      <ion-input
                        label="Expected Value"
                        labelPlacement="stacked"
                        formControlName="expected_value"
                        placeholder="ok"
                      ></ion-input>
                    </ion-item>
                  </ion-list>
                }
                @default {
                  <!-- Generic host-based types (ixc_service, ixc_equipment, zabbix_host, etc.) -->
                  <ion-list>
                    <ion-item>
                      <ion-input
                        label="Host / Resource ID"
                        labelPlacement="stacked"
                        placeholder="Enter the resource identifier"
                        formControlName="host"
                      ></ion-input>
                    </ion-item>
                  </ion-list>
                }
              }
            </div>

            <!-- Monitoring Settings -->
            <ion-list>
              <ion-item>
                <ion-input
                  label="Check Interval (seconds)"
                  labelPlacement="stacked"
                  formControlName="check_interval"
                  type="number"
                  placeholder="300"
                ></ion-input>
              </ion-item>
              <app-field-error [control]="form.get('check_interval')" label="Check interval"></app-field-error>

              <ion-item>
                <ion-input
                  label="Timeout (seconds)"
                  labelPlacement="stacked"
                  formControlName="timeout"
                  type="number"
                  placeholder="30"
                ></ion-input>
              </ion-item>
              <app-field-error [control]="form.get('timeout')" label="Timeout"></app-field-error>

              <ion-item>
                <ion-label position="stacked">Tags</ion-label>
                <div class="tag-chips">
                  @for (tag of getTagsList(); track tag) {
                    <ion-chip (click)="removeTag(tag)">
                      <ion-label>{{ tag }}</ion-label>
                      <ion-icon name="close-circle"></ion-icon>
                    </ion-chip>
                  }
                </div>
                <ion-input
                  placeholder="Type a tag and press Enter"
                  (keyup.enter)="addTag($event)"
                  [value]="''"
                ></ion-input>
              </ion-item>

              <ion-item>
                <ion-select label="Notification Policy" labelPlacement="stacked" formControlName="notification_policy_id" interface="popover" placeholder="None (no notifications)">
                  <ion-select-option [value]="null">None</ion-select-option>
                  @for (policy of notificationPolicies(); track policy.id) {
                    <ion-select-option [value]="policy.id">{{ policy.name }} ({{ policy.trigger_type }})</ion-select-option>
                  }
                </ion-select>
              </ion-item>

              <ion-item>
                <ion-toggle formControlName="active">Active</ion-toggle>
              </ion-item>
            </ion-list>

            <div class="form-actions">
              <ion-button expand="block" (click)="onSave()" [disabled]="saving() || form.invalid">
                @if (saving()) {
                  <ion-spinner name="crescent" style="width: 20px; height: 20px"></ion-spinner>
                } @else {
                  {{ isEdit() ? 'Update Monitor' : 'Create Monitor' }}
                }
              </ion-button>
            </div>
          </form>
        }
      }
    </ion-content>
  `,
  styles: [
    `
      .quick-setup {
        text-align: center;
        padding: 1rem;
      }
      .quick-setup h3 {
        margin-bottom: 0.5rem;
      }
      .quick-setup p {
        color: var(--ion-color-medium);
        margin-bottom: 1rem;
      }
      .quick-actions {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-top: 1rem;
      }
      .field-error {
        display: block;
        padding: 4px 16px;
        font-size: 0.75rem;
      }
      .form-actions {
        padding: 1rem 0 2rem;
      }
      .tag-chips { display: flex; flex-wrap: wrap; gap: 4px; padding: 4px 0; }
      .tag-chips ion-chip { height: 28px; font-size: 0.8rem; }
    `,
  ],
})
export class MonitorFormComponent implements OnInit {
  isEdit = signal(false);
  loading = signal(false);
  saving = signal(false);
  advancedMode = signal(false);
  notificationPolicies = signal<any[]>([]);
  quickUrl = '';

  monitorTypes: { value: MonitorType; label: string }[] = [
    { value: 'http', label: 'HTTP(S)' },
    { value: 'ping', label: 'Ping (ICMP)' },
    { value: 'port', label: 'Port (TCP/UDP)' },
    { value: 'api', label: 'REST API' },
    { value: 'keyword', label: 'Keyword' },
    { value: 'ssl', label: 'SSL Certificate' },
    { value: 'heartbeat', label: 'Heartbeat' },
    { value: 'ixc_service', label: 'IXC Service' },
    { value: 'ixc_equipment', label: 'IXC Equipment' },
    { value: 'zabbix_host', label: 'Zabbix Host' },
    { value: 'zabbix_trigger', label: 'Zabbix Trigger' },
  ];

  form: FormGroup;

  private monitorId: number | null = null;

  constructor(
    private fb: FormBuilder,
    private route: ActivatedRoute,
    private router: Router,
    private monitorService: MonitorService,
    private toastCtrl: ToastController,
    private api: ApiService,
  ) {
    this.form = this.fb.group({
      name: ['', Validators.required],
      description: [''],
      type: ['http', Validators.required],
      check_interval: [300, [Validators.required, Validators.min(30)]],
      timeout: [30, [Validators.required, Validators.min(1)]],
      tags: [''],
      active: [true],
      notification_policy_id: [null],
      configuration: this.fb.group({
        // HTTP / API fields
        url: [''],
        method: ['GET'],
        expected_status_code: [200],
        expected_content: [''],
        verify_ssl: [true],
        follow_redirects: [true],
        // Ping fields
        host: [''],
        packet_count: [4],
        max_packet_loss: [0],
        max_latency: [1000],
        // Port fields
        port: [null as number | null],
        protocol: ['tcp'],
        // API fields
        json_path: [''],
        expected_value: [''],
      }),
    });
  }

  ngOnInit(): void {
    // Load notification policies for the selector
    this.api.get<any>('/notification-policies').subscribe({
      next: (data) => this.notificationPolicies.set(data.items || data.notification_policies || []),
      error: () => this.notificationPolicies.set([]),
    });

    const idParam = this.route.snapshot.paramMap.get('id');
    if (idParam) {
      this.monitorId = Number(idParam);
      this.isEdit.set(true);
      this.advancedMode.set(true);
      this.loadMonitor();
    }
  }

  private loadMonitor(): void {
    if (!this.monitorId) return;
    this.loading.set(true);
    this.monitorService.getMonitor(this.monitorId).subscribe({
      next: (data: any) => {
        const monitor = data.monitor;
        this.form.patchValue({
          name: monitor.name,
          description: monitor.description || '',
          type: monitor.type,
          check_interval: monitor.check_interval,
          timeout: monitor.timeout,
          tags: this.parseTags(monitor.tags),
          active: monitor.active,
          notification_policy_id: monitor.notification_policy_id ?? null,
        });

        // Patch configuration
        if (monitor.configuration) {
          const config =
            typeof monitor.configuration === 'string'
              ? JSON.parse(monitor.configuration)
              : monitor.configuration;
          this.form.get('configuration')?.patchValue(config);
        }

        this.loading.set(false);
      },
      error: (err: any) => {
        this.loading.set(false);
        this.showToast(err?.message || 'Failed to load monitor', 'danger');
      },
    });
  }

  onQuickCreate(): void {
    if (!this.quickUrl) return;

    // Try to extract a name from the URL
    let name = '';
    try {
      const url = new URL(this.quickUrl);
      name = url.hostname;
    } catch {
      name = this.quickUrl;
    }

    this.saving.set(true);
    this.monitorService
      .createMonitor({
        name,
        type: 'http',
        configuration: {
          url: this.quickUrl,
          method: 'GET',
          expected_status_code: 200,
          verify_ssl: true,
          follow_redirects: true,
        },
        check_interval: 300,
        timeout: 30,
        active: true,
      })
      .subscribe({
        next: () => {
          this.saving.set(false);
          this.showToast('Monitor created successfully', 'success');
          const from = this.route.snapshot.queryParamMap.get('from');
          this.router.navigate([from === 'onboarding' ? '/onboarding' : '/monitors']);
        },
        error: (err: any) => {
          this.saving.set(false);
          showApiError(err, 'Failed to create monitor', this.toastCtrl, this.router);
        },
      });
  }

  onSave(): void {
    this.form.markAllAsTouched();
    if (this.form.invalid) return;

    const formValue = this.form.getRawValue();

    // Process tags from comma-separated string to array
    const tagsStr = formValue.tags as string;
    const tags = tagsStr
      ? tagsStr
          .split(',')
          .map((t: string) => t.trim())
          .filter((t: string) => t.length > 0)
      : [];

    // Filter configuration to only include relevant fields for the type
    const config = this.filterConfigByType(
      formValue.type,
      formValue.configuration,
    );

    const payload: any = {
      name: formValue.name,
      description: formValue.description || null,
      type: formValue.type,
      check_interval: formValue.check_interval,
      timeout: formValue.timeout,
      active: formValue.active,
      notification_policy_id: formValue.notification_policy_id || null,
      configuration: config,
      tags,
    };

    this.saving.set(true);

    const request$ = this.isEdit()
      ? this.monitorService.updateMonitor(this.monitorId!, payload)
      : this.monitorService.createMonitor(payload);

    request$.subscribe({
      next: () => {
        this.saving.set(false);
        this.showToast(
          this.isEdit()
            ? 'Monitor updated successfully'
            : 'Monitor created successfully',
          'success',
        );
        const from = this.route.snapshot.queryParamMap.get('from');
        this.router.navigate([from === 'onboarding' ? '/onboarding' : '/monitors']);
      },
      error: (err: any) => {
        this.saving.set(false);
        showApiError(err, this.isEdit() ? 'Failed to update monitor' : 'Failed to create monitor', this.toastCtrl, this.router);
      },
    });
  }

  private parseTags(tags: any): string {
    if (!tags) return '';
    if (Array.isArray(tags)) return tags.join(', ');
    if (typeof tags === 'string') {
      try {
        const parsed = JSON.parse(tags);
        if (Array.isArray(parsed)) return parsed.join(', ');
      } catch {
        // Not JSON, treat as comma-separated string
      }
      return tags;
    }
    return '';
  }

  getTagsList(): string[] {
    const tags = this.form.get('tags')?.value || '';
    return tags.split(',').map((t: string) => t.trim()).filter((t: string) => t.length > 0);
  }

  addTag(event: any): void {
    const value = (event.target?.value || '').trim();
    if (!value) return;
    const current = this.getTagsList();
    if (!current.includes(value)) {
      current.push(value);
      this.form.get('tags')?.setValue(current.join(', '));
    }
    event.target.value = '';
  }

  removeTag(tag: string): void {
    const current = this.getTagsList().filter(t => t !== tag);
    this.form.get('tags')?.setValue(current.join(', '));
  }

  private filterConfigByType(type: string, config: any): any {
    const filtered: any = {};

    switch (type) {
      case 'http':
        if (config.url) filtered.url = config.url;
        if (config.method) filtered.method = config.method;
        if (config.expected_status_code != null)
          filtered.expected_status_code = config.expected_status_code;
        if (config.expected_content)
          filtered.expected_content = config.expected_content;
        filtered.verify_ssl = config.verify_ssl ?? true;
        filtered.follow_redirects = config.follow_redirects ?? true;
        break;

      case 'ping':
        if (config.host) filtered.host = config.host;
        if (config.packet_count != null) filtered.packet_count = config.packet_count;
        if (config.max_packet_loss != null)
          filtered.max_packet_loss = config.max_packet_loss;
        if (config.max_latency != null) filtered.max_latency = config.max_latency;
        break;

      case 'port':
        if (config.host) filtered.host = config.host;
        if (config.port != null) filtered.port = config.port;
        if (config.protocol) filtered.protocol = config.protocol;
        break;

      case 'api':
        if (config.url) filtered.url = config.url;
        if (config.method) filtered.method = config.method;
        if (config.expected_status_code != null)
          filtered.expected_status_code = config.expected_status_code;
        if (config.json_path) filtered.json_path = config.json_path;
        if (config.expected_value)
          filtered.expected_value = config.expected_value;
        break;

      default:
        // For integration-based types, just pass host/resource
        if (config.host) filtered.host = config.host;
        break;
    }

    return filtered;
  }

  private async showToast(
    message: string,
    color: 'success' | 'danger',
  ): Promise<void> {
    const toast = await this.toastCtrl.create({
      message,
      duration: color === 'danger' ? 4000 : 3000,
      color,
      position: 'bottom',
    });
    await toast.present();
  }
}
