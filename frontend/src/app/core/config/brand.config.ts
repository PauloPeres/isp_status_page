/**
 * Centralized brand configuration.
 *
 * Change these values once to rebrand the entire application.
 * The backend counterpart reads from the `site_name` setting in the database.
 */
export const BRAND = {
  /** Full product name */
  name: 'KeepUp',

  /** Shorter name for tight spaces (sidebar, mobile header) */
  shortName: 'KeepUp',

  /** Tagline shown on login/register/landing pages */
  tagline: 'Uptime Monitoring & Status Pages',

  /** Path to the logo image (relative to assets/) */
  logoPath: 'assets/icon/logo.svg',

  /** Path to the app icon (used in favicon, PWA) */
  iconPath: 'assets/icon/favicon.png',

  /** Copyright holder entity */
  company: 'IuriLabs',

  /** Public-facing support email */
  supportEmail: 'support@usekeeup.com',

  /** No-reply email for transactional emails */
  noreplyEmail: 'noreply@usekeeup.com',

  /** Marketing / hello email */
  marketingEmail: 'hello@usekeeup.com',

  /** Marketing website URL */
  websiteUrl: 'https://usekeeup.com',

  /** Parent company (legal entity that owns the SaaS) */
  parentCompany: 'IuriLabs',
} as const;

export type Brand = typeof BRAND;
