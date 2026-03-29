import { Routes } from '@angular/router';
import { authGuard, superAdminGuard } from './core/guards/auth.guard';

export const routes: Routes = [
  {
    path: 'login',
    loadComponent: () =>
      import('./auth/login/login.component').then((m) => m.LoginComponent),
  },

  {
    path: '',
    loadComponent: () =>
      import('./layout/app-layout.component').then(
        (m) => m.AppLayoutComponent,
      ),
    canActivate: [authGuard],
    children: [
      { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
      {
        path: 'dashboard',
        loadComponent: () =>
          import('./features/dashboard/dashboard.component').then(
            (m) => m.DashboardComponent,
          ),
      },
      {
        path: 'monitors',
        loadComponent: () =>
          import('./features/monitors/monitor-list.component').then(
            (m) => m.MonitorListComponent,
          ),
      },
      {
        path: 'monitors/new',
        loadComponent: () =>
          import('./features/monitors/monitor-form.component').then(
            (m) => m.MonitorFormComponent,
          ),
      },
      {
        path: 'monitors/:id/edit',
        loadComponent: () =>
          import('./features/monitors/monitor-form.component').then(
            (m) => m.MonitorFormComponent,
          ),
      },
      {
        path: 'monitors/:id',
        loadComponent: () =>
          import('./features/monitors/monitor-detail.component').then(
            (m) => m.MonitorDetailComponent,
          ),
      },
      {
        path: 'checks',
        loadComponent: () =>
          import('./features/checks/check-list.component').then(
            (m) => m.CheckListComponent,
          ),
      },
      {
        path: 'incidents',
        loadComponent: () =>
          import('./features/incidents/incident-list.component').then(
            (m) => m.IncidentListComponent,
          ),
      },
      {
        path: 'incidents/:id',
        loadComponent: () =>
          import('./features/incidents/incident-detail.component').then(
            (m) => m.IncidentDetailComponent,
          ),
      },
      {
        path: 'alert-rules',
        loadComponent: () =>
          import('./features/alert-rules/alert-rule-list.component').then(
            (m) => m.AlertRuleListComponent,
          ),
      },
      {
        path: 'escalation',
        loadComponent: () =>
          import(
            './features/escalation/escalation-policy-list.component'
          ).then((m) => m.EscalationPolicyListComponent),
      },
      {
        path: 'integrations',
        loadComponent: () =>
          import('./features/integrations/integration-list.component').then(
            (m) => m.IntegrationListComponent,
          ),
      },
      {
        path: 'status-pages',
        loadComponent: () =>
          import('./features/status-pages/status-page-list.component').then(
            (m) => m.StatusPageListComponent,
          ),
      },
      {
        path: 'maintenance',
        loadComponent: () =>
          import('./features/maintenance/maintenance-list.component').then(
            (m) => m.MaintenanceListComponent,
          ),
      },
      {
        path: 'reports',
        loadComponent: () =>
          import('./features/reports/report-list.component').then(
            (m) => m.ReportListComponent,
          ),
      },
      {
        path: 'scheduled-reports',
        loadComponent: () =>
          import(
            './features/scheduled-reports/scheduled-report-list.component'
          ).then((m) => m.ScheduledReportListComponent),
      },
      {
        path: 'sla',
        loadComponent: () =>
          import('./features/sla/sla-list.component').then(
            (m) => m.SlaListComponent,
          ),
      },
      {
        path: 'subscribers',
        loadComponent: () =>
          import('./features/subscribers/subscriber-list.component').then(
            (m) => m.SubscriberListComponent,
          ),
      },
      {
        path: 'email-logs',
        loadComponent: () =>
          import('./features/email-logs/email-log-list.component').then(
            (m) => m.EmailLogListComponent,
          ),
      },
      {
        path: 'users',
        loadComponent: () =>
          import('./features/users/user-list.component').then(
            (m) => m.UserListComponent,
          ),
      },
      {
        path: 'invitations',
        loadComponent: () =>
          import('./features/invitations/invitation-list.component').then(
            (m) => m.InvitationListComponent,
          ),
      },
      {
        path: 'api-keys',
        loadComponent: () =>
          import('./features/api-keys/api-key-list.component').then(
            (m) => m.ApiKeyListComponent,
          ),
      },
      {
        path: 'settings',
        loadComponent: () =>
          import('./features/settings/settings.component').then(
            (m) => m.SettingsComponent,
          ),
      },
      {
        path: 'billing',
        loadComponent: () =>
          import('./features/billing/billing.component').then(
            (m) => m.BillingComponent,
          ),
      },
      {
        path: 'activity-log',
        loadComponent: () =>
          import('./features/activity-log/activity-log.component').then(
            (m) => m.ActivityLogComponent,
          ),
      },
    ],
  },

  { path: '**', redirectTo: 'login' },
];
