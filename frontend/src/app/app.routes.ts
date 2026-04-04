import { Routes } from '@angular/router';
import { authGuard, superAdminGuard } from './core/guards/auth.guard';

export const routes: Routes = [
  {
    path: 'login',
    loadComponent: () =>
      import('./auth/login/login.component').then((m) => m.LoginComponent),
  },
  {
    path: 'register',
    loadComponent: () =>
      import('./auth/register/register.component').then(
        (m) => m.RegisterComponent,
      ),
  },
  {
    path: 'oauth-callback',
    loadComponent: () =>
      import('./auth/oauth-callback/oauth-callback.component').then(
        (m) => m.OAuthCallbackComponent,
      ),
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
        path: 'onboarding',
        loadComponent: () =>
          import('./features/onboarding/onboarding.component').then(
            (m) => m.OnboardingComponent,
          ),
      },
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
        path: 'monitors/import',
        loadComponent: () =>
          import('./features/monitors/monitor-import.component').then(
            (m) => m.MonitorImportComponent,
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
        path: 'incidents/new',
        loadComponent: () =>
          import('./features/incidents/incident-form.component').then(
            (m) => m.IncidentFormComponent,
          ),
      },
      {
        path: 'incidents/:id',
        loadComponent: () =>
          import('./features/incidents/incident-detail.component').then(
            (m) => m.IncidentDetailComponent,
          ),
      },
      // Redirects from old routes to new notification system
      { path: 'alert-rules', redirectTo: 'notifications', pathMatch: 'full' },
      { path: 'alert-rules/new', redirectTo: 'notifications/new', pathMatch: 'full' },
      { path: 'alert-rules/:id/edit', redirectTo: 'notifications', pathMatch: 'full' },
      { path: 'escalation', redirectTo: 'notifications', pathMatch: 'full' },
      { path: 'escalation/new', redirectTo: 'notifications/new', pathMatch: 'full' },
      { path: 'escalation/:id/edit', redirectTo: 'notifications', pathMatch: 'full' },
      {
        path: 'channels',
        loadComponent: () =>
          import('./features/channels/channel-list.component').then(
            (m) => m.ChannelListComponent,
          ),
      },
      {
        path: 'channels/new',
        loadComponent: () =>
          import('./features/channels/channel-form.component').then(
            (m) => m.ChannelFormComponent,
          ),
      },
      {
        path: 'channels/:id/edit',
        loadComponent: () =>
          import('./features/channels/channel-form.component').then(
            (m) => m.ChannelFormComponent,
          ),
      },
      {
        path: 'notifications',
        loadComponent: () =>
          import('./features/notifications/notification-policy-list.component').then(
            (m) => m.NotificationPolicyListComponent,
          ),
      },
      {
        path: 'notifications/new',
        loadComponent: () =>
          import('./features/notifications/notification-policy-form.component').then(
            (m) => m.NotificationPolicyFormComponent,
          ),
      },
      {
        path: 'notifications/:id/edit',
        loadComponent: () =>
          import('./features/notifications/notification-policy-form.component').then(
            (m) => m.NotificationPolicyFormComponent,
          ),
      },
      {
        path: 'integrations',
        loadComponent: () =>
          import('./features/integrations/integration-list.component').then(
            (m) => m.IntegrationListComponent,
          ),
      },
      {
        path: 'integrations/new',
        loadComponent: () =>
          import('./features/integrations/integration-form.component').then(
            (m) => m.IntegrationFormComponent,
          ),
      },
      {
        path: 'integrations/:id/edit',
        loadComponent: () =>
          import('./features/integrations/integration-form.component').then(
            (m) => m.IntegrationFormComponent,
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
        path: 'status-pages/new',
        loadComponent: () =>
          import('./features/status-pages/status-page-form.component').then(
            (m) => m.StatusPageFormComponent,
          ),
      },
      {
        path: 'status-pages/:id/edit',
        loadComponent: () =>
          import('./features/status-pages/status-page-form.component').then(
            (m) => m.StatusPageFormComponent,
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
        path: 'maintenance/new',
        loadComponent: () =>
          import('./features/maintenance/maintenance-form.component').then(
            (m) => m.MaintenanceFormComponent,
          ),
      },
      {
        path: 'maintenance/:id/edit',
        loadComponent: () =>
          import('./features/maintenance/maintenance-form.component').then(
            (m) => m.MaintenanceFormComponent,
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
        path: 'scheduled-reports/new',
        loadComponent: () =>
          import(
            './features/scheduled-reports/scheduled-report-form.component'
          ).then((m) => m.ScheduledReportFormComponent),
      },
      {
        path: 'scheduled-reports/:id/edit',
        loadComponent: () =>
          import(
            './features/scheduled-reports/scheduled-report-form.component'
          ).then((m) => m.ScheduledReportFormComponent),
      },
      {
        path: 'sla',
        loadComponent: () =>
          import('./features/sla/sla-list.component').then(
            (m) => m.SlaListComponent,
          ),
      },
      {
        path: 'sla/new',
        loadComponent: () =>
          import('./features/sla/sla-form.component').then(
            (m) => m.SlaFormComponent,
          ),
      },
      {
        path: 'sla/:id/edit',
        loadComponent: () =>
          import('./features/sla/sla-form.component').then(
            (m) => m.SlaFormComponent,
          ),
      },
      {
        path: 'sla/:id',
        loadComponent: () =>
          import('./features/sla/sla-detail.component').then(
            (m) => m.SlaDetailComponent,
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
        path: 'settings/sip',
        loadComponent: () =>
          import('./features/settings/sip-settings.component').then(
            (m) => m.SipSettingsComponent,
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
        path: 'billing/credits',
        loadComponent: () =>
          import('./features/billing/credit-usage.component').then(
            (m) => m.CreditUsageComponent,
          ),
      },
      {
        path: 'chat',
        loadComponent: () =>
          import('./features/chat/chat-page.component').then(
            (m) => m.ChatPageComponent,
          ),
      },
      {
        path: 'activity-log',
        loadComponent: () =>
          import('./features/activity-log/activity-log.component').then(
            (m) => m.ActivityLogComponent,
          ),
      },
      {
        path: 'two-factor',
        loadComponent: () =>
          import('./features/two-factor/two-factor.component').then(
            (m) => m.TwoFactorComponent,
          ),
      },
      {
        path: 'profile',
        loadComponent: () =>
          import('./features/profile/profile.component').then(
            (m) => m.ProfileComponent,
          ),
      },
    ],
  },

  {
    path: 'super-admin',
    loadComponent: () =>
      import('./layout/app-layout.component').then(
        (m) => m.AppLayoutComponent,
      ),
    canActivate: [superAdminGuard],
    children: [
      {
        path: '',
        loadComponent: () =>
          import('./features/super-admin/super-admin-dashboard.component').then(
            (m) => m.SuperAdminDashboardComponent,
          ),
      },
      {
        path: 'organizations',
        loadComponent: () =>
          import('./features/super-admin/super-admin-orgs.component').then(
            (m) => m.SuperAdminOrgsComponent,
          ),
      },
      {
        path: 'users',
        loadComponent: () =>
          import('./features/super-admin/super-admin-users.component').then(
            (m) => m.SuperAdminUsersComponent,
          ),
      },
      {
        path: 'plans',
        loadComponent: () =>
          import('./features/super-admin/super-admin-plans.component').then(
            (m) => m.SuperAdminPlansComponent,
          ),
      },
      {
        path: 'revenue',
        loadComponent: () =>
          import('./features/super-admin/super-admin-revenue.component').then(
            (m) => m.SuperAdminRevenueComponent,
          ),
      },
      {
        path: 'health',
        loadComponent: () =>
          import('./features/super-admin/super-admin-health.component').then(
            (m) => m.SuperAdminHealthComponent,
          ),
      },
      {
        path: 'queue',
        loadComponent: () =>
          import('./features/super-admin/queue/queue-dashboard.component').then(
            (m) => m.QueueDashboardComponent,
          ),
      },
      {
        path: 'blog-posts',
        loadComponent: () =>
          import('./features/super-admin/blog/blog-post-list.component').then(
            (m) => m.BlogPostListComponent,
          ),
      },
      {
        path: 'blog-posts/new',
        loadComponent: () =>
          import('./features/super-admin/blog/blog-post-form.component').then(
            (m) => m.BlogPostFormComponent,
          ),
      },
      {
        path: 'blog-posts/:id/edit',
        loadComponent: () =>
          import('./features/super-admin/blog/blog-post-form.component').then(
            (m) => m.BlogPostFormComponent,
          ),
      },
    ],
  },

  { path: '**', redirectTo: 'login' },
];
