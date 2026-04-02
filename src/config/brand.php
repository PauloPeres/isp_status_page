<?php
/**
 * Centralized brand configuration.
 *
 * Change these values once to rebrand the entire application.
 * These are compile-time defaults — runtime overrides come from
 * the `site_name` / `site_logo_url` settings in the database.
 *
 * Usage:  Configure::read('Brand.name')
 */
return [
    'Brand' => [
        // Full product name
        'name' => env('BRAND_NAME', 'KeepUp'),

        // Short name (sidebar, mobile)
        'shortName' => env('BRAND_SHORT_NAME', 'KeepUp'),

        // Product name + category (for page titles, legal docs)
        'fullName' => env('BRAND_FULL_NAME', 'KeepUp'),

        // Company / legal entity
        'company' => env('BRAND_COMPANY', 'IuriLabs'),

        // Default "from" name for emails
        'emailFromName' => env('BRAND_EMAIL_FROM_NAME', 'KeepUp'),

        // Default icon filename in webroot/img/
        'iconFile' => 'icon_keepup.png',

        // Public website URL
        'websiteUrl' => env('BRAND_WEBSITE_URL', 'https://usekeeup.com'),

        // Support email
        'supportEmail' => env('BRAND_SUPPORT_EMAIL', 'support@usekeeup.com'),

        // No-reply email for transactional emails
        'noreplyEmail' => env('BRAND_NOREPLY_EMAIL', 'noreply@usekeeup.com'),

        // Marketing / hello email
        'marketingEmail' => env('BRAND_MARKETING_EMAIL', 'hello@usekeeup.com'),

        // Parent company (legal entity that owns the SaaS)
        'parentCompany' => env('BRAND_PARENT_COMPANY', 'IuriLabs'),
    ],
];
