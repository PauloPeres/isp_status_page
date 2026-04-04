import { Component, OnInit, signal } from '@angular/core';
import {
  IonApp,
  IonSplitPane,
  IonRouterOutlet,
  IonMenu,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonList,
  IonItem,
  IonItemDivider,
  IonIcon,
  IonLabel,
  IonBadge,
  IonMenuToggle,
  IonButtons,
  IonButton,
} from '@ionic/angular/standalone';
import { RouterLink, RouterLinkActive } from '@angular/router';
import { AuthService } from '../core/services/auth.service';
import { BRAND } from '../core/config/brand.config';
import { ApiService } from '../core/services/api.service';
import { ThemeService } from '../core/services/theme.service';
import { ChatWidgetComponent } from '../features/chat/chat-widget.component';
import { addIcons } from 'ionicons';
import {
  gridOutline,
  pulseOutline,
  alertCircleOutline,
  notificationsOutline,
  flashOutline,
  linkOutline,
  globeOutline,
  constructOutline,
  barChartOutline,
  calendarOutline,
  shieldCheckmarkOutline,
  peopleOutline,
  mailOutline,
  personOutline,
  keyOutline,
  settingsOutline,
  cardOutline,
  logOutOutline,
  moonOutline,
  sunnyOutline,
  checkmarkDoneOutline,
  timerOutline,
  documentTextOutline,
  timeOutline,
  sendOutline,
  swapHorizontalOutline,
  listOutline,
  personCircleOutline,
  shieldOutline,
  statsChartOutline,
  serverOutline,
  walletOutline,
  megaphoneOutline,
  layersOutline,
} from 'ionicons/icons';

addIcons({
  'grid-outline': gridOutline,
  'pulse-outline': pulseOutline,
  'alert-circle-outline': alertCircleOutline,
  'notifications-outline': notificationsOutline,
  'flash-outline': flashOutline,
  'link-outline': linkOutline,
  'globe-outline': globeOutline,
  'construct-outline': constructOutline,
  'bar-chart-outline': barChartOutline,
  'calendar-outline': calendarOutline,
  'shield-checkmark-outline': shieldCheckmarkOutline,
  'people-outline': peopleOutline,
  'mail-outline': mailOutline,
  'person-outline': personOutline,
  'key-outline': keyOutline,
  'settings-outline': settingsOutline,
  'card-outline': cardOutline,
  'log-out-outline': logOutOutline,
  'moon-outline': moonOutline,
  'sunny-outline': sunnyOutline,
  'checkmark-done-outline': checkmarkDoneOutline,
  'timer-outline': timerOutline,
  'document-text-outline': documentTextOutline,
  'time-outline': timeOutline,
  'send-outline': sendOutline,
  'swap-horizontal-outline': swapHorizontalOutline,
  'list-outline': listOutline,
  'person-circle-outline': personCircleOutline,
  'shield-outline': shieldOutline,
  'stats-chart-outline': statsChartOutline,
  'server-outline': serverOutline,
  'wallet-outline': walletOutline,
  'megaphone-outline': megaphoneOutline,
  'layers-outline': layersOutline,
});

@Component({
  selector: 'app-layout',
  standalone: true,
  imports: [
    IonApp,
    IonSplitPane,
    IonRouterOutlet,
    IonMenu,
    IonHeader,
    IonToolbar,
    IonTitle,
    IonContent,
    IonList,
    IonItem,
    IonItemDivider,
    IonIcon,
    IonLabel,
    IonBadge,
    IonMenuToggle,
    IonButtons,
    IonButton,
    RouterLink,
    RouterLinkActive,
    ChatWidgetComponent,
  ],
  template: `
    <ion-app>
      <ion-split-pane contentId="main-content">
        <ion-menu contentId="main-content" type="overlay">
          <ion-header>
            <ion-toolbar color="secondary">
              <ion-title>{{ brand.name }}</ion-title>
              <ion-buttons slot="end">
                <ion-button (click)="theme.toggle()" [title]="'Theme: ' + theme.mode()">
                  <ion-icon
                    [name]="theme.isDark() ? 'sunny-outline' : 'moon-outline'"
                    slot="icon-only"
                  ></ion-icon>
                </ion-button>
                <ion-button (click)="auth.logout()">
                  <ion-icon name="log-out-outline" slot="icon-only"></ion-icon>
                </ion-button>
              </ion-buttons>
            </ion-toolbar>
          </ion-header>
          <ion-content>
            <ion-list lines="none">
              <!-- Dashboard -->
              <ion-menu-toggle auto-hide="false">
                <ion-item routerLink="/dashboard" routerLinkActive="selected" [routerLinkActiveOptions]="{exact: true}">
                  <ion-icon name="grid-outline" slot="start"></ion-icon>
                  <ion-label>Dashboard</ion-label>
                </ion-item>
              </ion-menu-toggle>

              <!-- Monitoring -->
              <ion-item-divider>
                <ion-label>Monitoring</ion-label>
              </ion-item-divider>

              <ion-menu-toggle auto-hide="false">
                <ion-item routerLink="/monitors" routerLinkActive="selected">
                  <ion-icon name="pulse-outline" slot="start"></ion-icon>
                  <ion-label>Monitors</ion-label>
                </ion-item>
              </ion-menu-toggle>

              <ion-menu-toggle auto-hide="false">
                <ion-item routerLink="/checks" routerLinkActive="selected">
                  <ion-icon name="checkmark-done-outline" slot="start"></ion-icon>
                  <ion-label>Checks</ion-label>
                </ion-item>
              </ion-menu-toggle>

              <ion-menu-toggle auto-hide="false">
                <ion-item routerLink="/incidents" routerLinkActive="selected">
                  <ion-icon name="alert-circle-outline" slot="start"></ion-icon>
                  <ion-label>Incidents</ion-label>
                  @if (activeIncidentCount() > 0) {
                    <ion-badge color="danger" slot="end">{{ activeIncidentCount() }}</ion-badge>
                  }
                </ion-item>
              </ion-menu-toggle>

              <ion-menu-toggle auto-hide="false">
                <ion-item routerLink="/integrations" routerLinkActive="selected">
                  <ion-icon name="link-outline" slot="start"></ion-icon>
                  <ion-label>Integrations</ion-label>
                </ion-item>
              </ion-menu-toggle>

              <ion-menu-toggle auto-hide="false">
                <ion-item routerLink="/notifications" routerLinkActive="selected">
                  <ion-icon name="notifications-outline" slot="start"></ion-icon>
                  <ion-label>Notifications</ion-label>
                </ion-item>
              </ion-menu-toggle>

              <ion-menu-toggle auto-hide="false">
                <ion-item routerLink="/status-pages" routerLinkActive="selected">
                  <ion-icon name="globe-outline" slot="start"></ion-icon>
                  <ion-label>Status Pages</ion-label>
                </ion-item>
              </ion-menu-toggle>

              <ion-menu-toggle auto-hide="false">
                <ion-item routerLink="/maintenance" routerLinkActive="selected">
                  <ion-icon name="construct-outline" slot="start"></ion-icon>
                  <ion-label>Maintenance</ion-label>
                </ion-item>
              </ion-menu-toggle>

              <!-- Reports -->
              <ion-item-divider (click)="reportsExpanded = !reportsExpanded" style="cursor: pointer">
                <ion-label>Reports {{ reportsExpanded ? '&#9662;' : '&#9656;' }}</ion-label>
              </ion-item-divider>

              @if (reportsExpanded) {
                <ion-menu-toggle auto-hide="false">
                  <ion-item routerLink="/reports" routerLinkActive="selected">
                    <ion-icon name="bar-chart-outline" slot="start"></ion-icon>
                    <ion-label>Reports</ion-label>
                  </ion-item>
                </ion-menu-toggle>

                <ion-menu-toggle auto-hide="false">
                  <ion-item routerLink="/scheduled-reports" routerLinkActive="selected">
                    <ion-icon name="calendar-outline" slot="start"></ion-icon>
                    <ion-label>Scheduled Reports</ion-label>
                  </ion-item>
                </ion-menu-toggle>

                <ion-menu-toggle auto-hide="false">
                  <ion-item routerLink="/sla" routerLinkActive="selected">
                    <ion-icon name="shield-checkmark-outline" slot="start"></ion-icon>
                    <ion-label>SLA</ion-label>
                  </ion-item>
                </ion-menu-toggle>
              }

              <!-- Subscribers -->
              <ion-item-divider (click)="subscribersExpanded = !subscribersExpanded" style="cursor: pointer">
                <ion-label>Subscribers {{ subscribersExpanded ? '&#9662;' : '&#9656;' }}</ion-label>
              </ion-item-divider>

              @if (subscribersExpanded) {
                <ion-menu-toggle auto-hide="false">
                  <ion-item routerLink="/subscribers" routerLinkActive="selected">
                    <ion-icon name="people-outline" slot="start"></ion-icon>
                    <ion-label>Subscribers</ion-label>
                  </ion-item>
                </ion-menu-toggle>

                <ion-menu-toggle auto-hide="false">
                  <ion-item routerLink="/email-logs" routerLinkActive="selected">
                    <ion-icon name="mail-outline" slot="start"></ion-icon>
                    <ion-label>Email Logs</ion-label>
                  </ion-item>
                </ion-menu-toggle>
              }

              <!-- Administration -->
              <ion-item-divider>
                <ion-label>Administration</ion-label>
              </ion-item-divider>

              <ion-menu-toggle auto-hide="false">
                <ion-item routerLink="/profile" routerLinkActive="selected">
                  <ion-icon name="person-circle-outline" slot="start"></ion-icon>
                  <ion-label>My Profile</ion-label>
                </ion-item>
              </ion-menu-toggle>

              <ion-menu-toggle auto-hide="false">
                <ion-item routerLink="/users" routerLinkActive="selected">
                  <ion-icon name="person-outline" slot="start"></ion-icon>
                  <ion-label>Users</ion-label>
                </ion-item>
              </ion-menu-toggle>

              <ion-menu-toggle auto-hide="false">
                <ion-item routerLink="/invitations" routerLinkActive="selected">
                  <ion-icon name="send-outline" slot="start"></ion-icon>
                  <ion-label>Invitations</ion-label>
                </ion-item>
              </ion-menu-toggle>

              <ion-menu-toggle auto-hide="false">
                <ion-item routerLink="/channels" routerLinkActive="selected">
                  <ion-icon name="megaphone-outline" slot="start"></ion-icon>
                  <ion-label>Channels</ion-label>
                </ion-item>
              </ion-menu-toggle>

              <ion-menu-toggle auto-hide="false">
                <ion-item routerLink="/api-keys" routerLinkActive="selected">
                  <ion-icon name="key-outline" slot="start"></ion-icon>
                  <ion-label>API Keys</ion-label>
                </ion-item>
              </ion-menu-toggle>

              <ion-menu-toggle auto-hide="false">
                <ion-item routerLink="/settings" routerLinkActive="selected">
                  <ion-icon name="settings-outline" slot="start"></ion-icon>
                  <ion-label>Settings</ion-label>
                </ion-item>
              </ion-menu-toggle>

              <ion-menu-toggle auto-hide="false">
                <ion-item routerLink="/billing" routerLinkActive="selected" [routerLinkActiveOptions]="{exact: true}">
                  <ion-icon name="card-outline" slot="start"></ion-icon>
                  <ion-label>Billing</ion-label>
                </ion-item>
              </ion-menu-toggle>

              <ion-menu-toggle auto-hide="false">
                <ion-item routerLink="/billing/credits" routerLinkActive="selected">
                  <ion-icon name="wallet-outline" slot="start"></ion-icon>
                  <ion-label>Credit Usage</ion-label>
                </ion-item>
              </ion-menu-toggle>

              <ion-menu-toggle auto-hide="false">
                <ion-item routerLink="/activity-log" routerLinkActive="selected">
                  <ion-icon name="list-outline" slot="start"></ion-icon>
                  <ion-label>Activity Log</ion-label>
                </ion-item>
              </ion-menu-toggle>

              <!-- Super Admin (only visible to super admins) -->
              @if (auth.isSuperAdmin()) {
                <ion-item-divider>
                  <ion-label>Super Admin</ion-label>
                </ion-item-divider>

                <ion-menu-toggle auto-hide="false">
                  <ion-item routerLink="/super-admin" routerLinkActive="selected" [routerLinkActiveOptions]="{exact: true}">
                    <ion-icon name="shield-outline" slot="start"></ion-icon>
                    <ion-label>Dashboard</ion-label>
                  </ion-item>
                </ion-menu-toggle>

                <ion-menu-toggle auto-hide="false">
                  <ion-item routerLink="/super-admin/organizations" routerLinkActive="selected">
                    <ion-icon name="globe-outline" slot="start"></ion-icon>
                    <ion-label>Organizations</ion-label>
                  </ion-item>
                </ion-menu-toggle>

                <ion-menu-toggle auto-hide="false">
                  <ion-item routerLink="/super-admin/users" routerLinkActive="selected">
                    <ion-icon name="people-outline" slot="start"></ion-icon>
                    <ion-label>All Users</ion-label>
                  </ion-item>
                </ion-menu-toggle>

                <ion-menu-toggle auto-hide="false">
                  <ion-item routerLink="/super-admin/plans" routerLinkActive="selected">
                    <ion-icon name="wallet-outline" slot="start"></ion-icon>
                    <ion-label>Plans</ion-label>
                  </ion-item>
                </ion-menu-toggle>

                <ion-menu-toggle auto-hide="false">
                  <ion-item routerLink="/super-admin/revenue" routerLinkActive="selected">
                    <ion-icon name="stats-chart-outline" slot="start"></ion-icon>
                    <ion-label>Revenue</ion-label>
                  </ion-item>
                </ion-menu-toggle>

                <ion-menu-toggle auto-hide="false">
                  <ion-item routerLink="/super-admin/health" routerLinkActive="selected">
                    <ion-icon name="server-outline" slot="start"></ion-icon>
                    <ion-label>Health</ion-label>
                  </ion-item>
                </ion-menu-toggle>

                <ion-menu-toggle auto-hide="false">
                  <ion-item routerLink="/super-admin/queue" routerLinkActive="selected">
                    <ion-icon name="layers-outline" slot="start"></ion-icon>
                    <ion-label>Queue</ion-label>
                  </ion-item>
                </ion-menu-toggle>

                <ion-menu-toggle auto-hide="false">
                  <ion-item routerLink="/super-admin/blog-posts" routerLinkActive="selected">
                    <ion-icon name="document-text-outline" slot="start"></ion-icon>
                    <ion-label>Blog Posts</ion-label>
                  </ion-item>
                </ion-menu-toggle>
              }
            </ion-list>
          </ion-content>
        </ion-menu>

        <ion-router-outlet id="main-content"></ion-router-outlet>
      </ion-split-pane>

      <app-chat-widget></app-chat-widget>
    </ion-app>
  `,
  styles: [
    `
      .selected {
        --background: rgba(41, 121, 255, 0.12);
        --color: var(--ion-color-primary);
        font-weight: 600;
      }
      ion-item-divider {
        --background: transparent;
        --color: var(--ion-color-medium);
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-top: 0.5rem;
        min-height: 32px;
      }
      ion-item {
        --min-height: 44px;
        cursor: pointer;
      }
      ion-icon {
        font-size: 1.2rem;
      }
    `,
  ],
})
export class AppLayoutComponent implements OnInit {
  brand = BRAND;
  activeIncidentCount = signal(0);
  reportsExpanded = false;
  subscribersExpanded = false;

  constructor(
    public auth: AuthService,
    public theme: ThemeService,
    private api: ApiService,
  ) {}

  ngOnInit(): void {
    this.loadIncidentCount();
    // Refresh every 60 seconds
    setInterval(() => this.loadIncidentCount(), 60000);
  }

  private loadIncidentCount(): void {
    if (!this.auth.isAuthenticated()) return;
    this.api.get<any>('/dashboard/summary').subscribe({
      next: (data) => {
        this.activeIncidentCount.set(data?.active_incidents?.total ?? 0);
      },
      error: () => {},
    });
  }
}
