import { Component } from '@angular/core';
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
  IonMenuToggle,
  IonButtons,
  IonButton,
} from '@ionic/angular/standalone';
import { RouterLink, RouterLinkActive } from '@angular/router';
import { AuthService } from '../core/services/auth.service';
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
    IonMenuToggle,
    IonButtons,
    IonButton,
    RouterLink,
    RouterLinkActive,
  ],
  template: `
    <ion-app>
      <ion-split-pane contentId="main-content">
        <ion-menu contentId="main-content" type="overlay">
          <ion-header>
            <ion-toolbar color="primary">
              <ion-title>ISP Status</ion-title>
              <ion-buttons slot="end">
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
                </ion-item>
              </ion-menu-toggle>

              <ion-menu-toggle auto-hide="false">
                <ion-item routerLink="/alert-rules" routerLinkActive="selected">
                  <ion-icon name="notifications-outline" slot="start"></ion-icon>
                  <ion-label>Alert Rules</ion-label>
                </ion-item>
              </ion-menu-toggle>

              <ion-menu-toggle auto-hide="false">
                <ion-item routerLink="/escalation" routerLinkActive="selected">
                  <ion-icon name="flash-outline" slot="start"></ion-icon>
                  <ion-label>Escalation</ion-label>
                </ion-item>
              </ion-menu-toggle>

              <ion-menu-toggle auto-hide="false">
                <ion-item routerLink="/integrations" routerLinkActive="selected">
                  <ion-icon name="link-outline" slot="start"></ion-icon>
                  <ion-label>Integrations</ion-label>
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
              <ion-item-divider>
                <ion-label>Reports</ion-label>
              </ion-item-divider>

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

              <!-- Subscribers -->
              <ion-item-divider>
                <ion-label>Subscribers</ion-label>
              </ion-item-divider>

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

              <!-- Administration -->
              <ion-item-divider>
                <ion-label>Administration</ion-label>
              </ion-item-divider>

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
                <ion-item routerLink="/billing" routerLinkActive="selected">
                  <ion-icon name="card-outline" slot="start"></ion-icon>
                  <ion-label>Billing</ion-label>
                </ion-item>
              </ion-menu-toggle>

              <ion-menu-toggle auto-hide="false">
                <ion-item routerLink="/activity-log" routerLinkActive="selected">
                  <ion-icon name="list-outline" slot="start"></ion-icon>
                  <ion-label>Activity Log</ion-label>
                </ion-item>
              </ion-menu-toggle>
            </ion-list>
          </ion-content>
        </ion-menu>

        <ion-router-outlet id="main-content"></ion-router-outlet>
      </ion-split-pane>
    </ion-app>
  `,
  styles: [
    `
      .selected {
        --background: var(--ion-color-primary-tint);
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
export class AppLayoutComponent {
  constructor(public auth: AuthService) {}
}
