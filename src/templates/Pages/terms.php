<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terms of Service - <?= \Cake\Core\Configure::read('Brand.fullName', 'ISP Status Page') ?></title>
    <meta name="description" content="Terms of Service for <?= \Cake\Core\Configure::read('Brand.fullName', 'ISP Status Page') ?>, a SaaS uptime monitoring platform by <?= \Cake\Core\Configure::read('Brand.company', 'IuriLabs') ?>.">
    <link rel="icon" type="image/png" href="/img/icon_isp_status_page.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/img/icon_isp_status_page.png">
    <meta name="theme-color" content="#1A2332">
    <link rel="stylesheet" href="/css/design-tokens.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: var(--font-body);
            color: var(--color-gray-700);
            line-height: 1.7;
            background: var(--color-gray-50);
        }
        h1, h2, h3, h4 {
            font-family: var(--font-display);
            color: var(--color-gray-900);
            letter-spacing: -0.01em;
        }
        a { color: var(--color-brand-600); text-decoration: none; }
        a:hover { color: var(--color-brand-700); text-decoration: underline; }

        /* Header */
        .legal-header {
            background: #fff;
            border-bottom: 1px solid var(--color-gray-200);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .legal-header-inner {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .legal-brand {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            color: var(--color-gray-900);
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 1.125rem;
        }
        .legal-brand:hover { text-decoration: none; color: var(--color-brand-600); }
        .legal-brand img { width: 32px; height: 32px; border-radius: 8px; }
        .legal-nav { display: flex; gap: 1.5rem; font-size: 0.875rem; }
        .legal-nav a { color: var(--color-gray-500); }
        .legal-nav a:hover { color: var(--color-brand-600); }

        /* Content */
        .legal-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 3rem 1.5rem 4rem;
        }
        .legal-content h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .legal-meta {
            color: var(--color-gray-500);
            font-size: 0.875rem;
            margin-bottom: 2.5rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--color-gray-200);
        }
        .legal-content h2 {
            font-size: 1.375rem;
            margin-top: 2.5rem;
            margin-bottom: 0.75rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--color-gray-100);
        }
        .legal-content h2:first-of-type {
            border-top: none;
            padding-top: 0;
        }
        .legal-content h3 {
            font-size: 1.125rem;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
        }
        .legal-content p {
            margin-bottom: 1rem;
        }
        .legal-content ul, .legal-content ol {
            margin-bottom: 1rem;
            padding-left: 1.5rem;
        }
        .legal-content li {
            margin-bottom: 0.4rem;
        }
        .legal-content strong {
            color: var(--color-gray-900);
        }

        /* Footer */
        .legal-footer {
            background: var(--color-gray-900);
            color: var(--color-gray-400);
            padding: 2rem 0;
            font-size: 0.875rem;
        }
        .legal-footer-inner {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .legal-footer a { color: var(--color-gray-400); }
        .legal-footer a:hover { color: #fff; text-decoration: none; }
        .legal-footer-links { display: flex; gap: 1.5rem; }

        @media (max-width: 640px) {
            .legal-content h1 { font-size: 1.5rem; }
            .legal-content h2 { font-size: 1.2rem; }
            .legal-header-inner { flex-direction: column; gap: 0.75rem; }
            .legal-footer-inner { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>

<header class="legal-header">
    <div class="legal-header-inner">
        <a href="/" class="legal-brand">
            <img src="/img/icon_isp_status_page.png" alt="<?= \Cake\Core\Configure::read('Brand.name', 'ISP Status') ?>">
            <span><?= \Cake\Core\Configure::read('Brand.name', 'ISP Status') ?></span>
        </a>
        <nav class="legal-nav">
            <a href="/">Home</a>
            <a href="/terms">Terms</a>
            <a href="/privacy">Privacy</a>
        </nav>
    </div>
</header>

<main class="legal-content">
    <h1>Terms of Service</h1>
    <p class="legal-meta">Effective Date: March 30, 2026 &middot; Last Updated: March 30, 2026</p>

    <h2>1. Acceptance of Terms</h2>
    <p>By accessing or using <?= \Cake\Core\Configure::read('Brand.fullName', 'ISP Status Page') ?> ("Service"), operated by <?= \Cake\Core\Configure::read('Brand.company', 'IuriLabs') ?> Limited Company ("<?= \Cake\Core\Configure::read('Brand.company', 'IuriLabs') ?>," "we," "us," or "our"), you agree to be bound by these Terms of Service ("Terms"). If you do not agree to these Terms, you may not use the Service.</p>
    <p>By creating an account, subscribing to a plan, or otherwise using the Service, you represent that you are at least 18 years of age and have the legal capacity to enter into these Terms.</p>

    <h2>2. Description of Service</h2>
    <p><?= \Cake\Core\Configure::read('Brand.fullName', 'ISP Status Page') ?> is a software-as-a-service (SaaS) platform that provides:</p>
    <ul>
        <li><strong>Uptime Monitoring:</strong> Automated checks of your infrastructure via HTTP, Ping, Port, SSL Certificate, and Heartbeat monitors.</li>
        <li><strong>Public Status Pages:</strong> Customizable, branded status pages you can share with your customers and stakeholders.</li>
        <li><strong>Alerting &amp; Notifications:</strong> Real-time alerts delivered via email, Slack, Discord, Telegram, SMS, WhatsApp, and webhooks when monitors detect issues.</li>
        <li><strong>Incident Management:</strong> Tools to create, track, acknowledge, and communicate incidents.</li>
        <li><strong>Integrations:</strong> Connections to third-party systems including IXC, Zabbix, and custom REST APIs.</li>
    </ul>

    <h2>3. Account Registration &amp; Security</h2>
    <p>To use the Service, you must create an account by providing accurate and complete information. You may also register using third-party OAuth providers (Google or GitHub).</p>
    <p>You are responsible for:</p>
    <ul>
        <li>Maintaining the confidentiality of your account credentials, including passwords, API keys, and two-factor authentication recovery codes.</li>
        <li>All activity that occurs under your account.</li>
        <li>Notifying us immediately of any unauthorized use of your account.</li>
    </ul>
    <p>We reserve the right to suspend or terminate accounts that violate these Terms or that we reasonably believe have been compromised.</p>

    <h2>4. Subscription Plans &amp; Billing</h2>
    <p>The Service offers multiple subscription tiers, including a free tier and paid plans. Paid subscriptions are billed on a monthly or annual basis.</p>
    <ul>
        <li><strong>Payment Processing:</strong> All payments are processed securely through Stripe. By subscribing to a paid plan, you agree to Stripe's <a href="https://stripe.com/legal" target="_blank" rel="noopener">Terms of Service</a>.</li>
        <li><strong>Auto-Renewal:</strong> Paid subscriptions automatically renew at the end of each billing period unless you cancel before the renewal date.</li>
        <li><strong>Cancellation:</strong> You may cancel your subscription at any time through the billing portal. Upon cancellation, your plan remains active until the end of the current billing period. No prorated refunds are provided for partial billing periods.</li>
        <li><strong>Price Changes:</strong> We may change subscription prices with at least 30 days' notice. Price changes take effect at the start of your next billing cycle.</li>
        <li><strong>Failed Payments:</strong> If a payment fails, we will attempt to collect the payment for a reasonable period. If payment cannot be collected, your account may be downgraded to the free tier.</li>
    </ul>

    <h2>5. Free Tier Limitations</h2>
    <p>The free tier is provided at no cost and includes limited functionality, including but not limited to:</p>
    <ul>
        <li>A limited number of monitors.</li>
        <li>A limited number of team members.</li>
        <li>Reduced check frequency.</li>
        <li>Limited data retention periods.</li>
        <li>No access to premium features such as custom domains, advanced integrations, or priority support.</li>
    </ul>
    <p>We reserve the right to modify the free tier's features and limitations at any time.</p>

    <h2>6. Notification Credits (SMS/WhatsApp)</h2>
    <p>Certain notification channels, including SMS and WhatsApp, consume notification credits. Credits are included in paid plans based on your subscription tier.</p>
    <ul>
        <li>Credit usage varies by destination country and message type.</li>
        <li>Unused credits do not roll over between billing periods unless otherwise specified in your plan.</li>
        <li>Additional credits may be purchased separately.</li>
        <li>We are not responsible for delivery failures caused by carrier issues, invalid phone numbers, or recipient device settings.</li>
    </ul>

    <h2>7. Acceptable Use Policy</h2>
    <p>You agree not to use the Service to:</p>
    <ul>
        <li>Violate any applicable law, regulation, or third-party rights.</li>
        <li>Send unsolicited notifications, spam, or phishing messages through the alerting system.</li>
        <li>Monitor targets you do not own or have authorization to monitor.</li>
        <li>Interfere with, disrupt, or overload the Service or its infrastructure.</li>
        <li>Attempt to gain unauthorized access to other users' accounts or data.</li>
        <li>Reverse-engineer, decompile, or disassemble any part of the Service.</li>
        <li>Use the Service for any illegal surveillance or data harvesting purposes.</li>
        <li>Resell or redistribute the Service without written authorization.</li>
    </ul>
    <p>Violation of this policy may result in immediate suspension or termination of your account.</p>

    <h2>8. Data &amp; Privacy</h2>
    <p>Your use of the Service is also governed by our <a href="/privacy">Privacy Policy</a>, which describes how we collect, use, store, and protect your information. By using the Service, you consent to the practices described in the Privacy Policy.</p>
    <p>You retain ownership of all data you submit to the Service, including monitor configurations, incident descriptions, and status page content.</p>

    <h2>9. Uptime &amp; Service Level</h2>
    <p>We strive to provide a reliable and available Service. However:</p>
    <ul>
        <li><strong>Free Tier:</strong> The free tier is provided "as is" with no uptime guarantees or service level commitments.</li>
        <li><strong>Paid Plans:</strong> We make reasonable commercial efforts to maintain high availability for paid plans. Specific service level agreements (SLAs) may be offered for Business and Enterprise plans and will be documented separately.</li>
        <li><strong>Scheduled Maintenance:</strong> We may perform scheduled maintenance with reasonable advance notice. Maintenance windows are not counted as downtime.</li>
        <li><strong>Force Majeure:</strong> We are not liable for downtime caused by events beyond our reasonable control, including natural disasters, third-party service outages, or governmental actions.</li>
    </ul>

    <h2>10. Intellectual Property</h2>
    <p>The Service, including its software, design, documentation, logos, and trademarks, is the intellectual property of <?= \Cake\Core\Configure::read('Brand.company', 'IuriLabs') ?> and is protected by copyright, trademark, and other intellectual property laws.</p>
    <p>You are granted a limited, non-exclusive, non-transferable, revocable license to use the Service in accordance with these Terms. This license does not grant you any rights to our intellectual property beyond what is necessary to use the Service.</p>
    <p>Any feedback, suggestions, or ideas you provide about the Service may be used by us without obligation or compensation to you.</p>

    <h2>11. Third-Party Services</h2>
    <p>The Service integrates with and relies upon third-party services, including but not limited to:</p>
    <ul>
        <li><strong>Stripe:</strong> For payment processing and subscription management.</li>
        <li><strong>Twilio:</strong> For SMS and WhatsApp notification delivery.</li>
        <li><strong>Google &amp; GitHub:</strong> For OAuth authentication.</li>
        <li><strong>SMTP Providers:</strong> For email notification delivery.</li>
    </ul>
    <p>Your use of these third-party services is subject to their respective terms and privacy policies. We are not responsible for the availability, accuracy, or conduct of third-party services.</p>

    <h2>12. Limitation of Liability</h2>
    <p>TO THE MAXIMUM EXTENT PERMITTED BY APPLICABLE LAW:</p>
    <ul>
        <li>THE SERVICE IS PROVIDED "AS IS" AND "AS AVAILABLE" WITHOUT WARRANTIES OF ANY KIND, WHETHER EXPRESS, IMPLIED, STATUTORY, OR OTHERWISE, INCLUDING IMPLIED WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, AND NON-INFRINGEMENT.</li>
        <li>IN NO EVENT SHALL <?= strtoupper(\Cake\Core\Configure::read('Brand.company', 'IuriLabs')) ?>, ITS OFFICERS, DIRECTORS, EMPLOYEES, OR AGENTS BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES, INCLUDING BUT NOT LIMITED TO LOSS OF PROFITS, DATA, USE, OR GOODWILL, ARISING OUT OF OR IN CONNECTION WITH YOUR USE OF THE SERVICE.</li>
        <li>OUR TOTAL AGGREGATE LIABILITY FOR ALL CLAIMS ARISING OUT OF OR RELATING TO THESE TERMS OR THE SERVICE SHALL NOT EXCEED THE AMOUNT YOU PAID US IN THE TWELVE (12) MONTHS PRECEDING THE CLAIM, OR ONE HUNDRED U.S. DOLLARS ($100), WHICHEVER IS GREATER.</li>
    </ul>

    <h2>13. Indemnification</h2>
    <p>You agree to indemnify, defend, and hold harmless <?= \Cake\Core\Configure::read('Brand.company', 'IuriLabs') ?> and its officers, directors, employees, and agents from and against any claims, liabilities, damages, losses, costs, and expenses (including reasonable attorneys' fees) arising out of or in connection with:</p>
    <ul>
        <li>Your use of the Service.</li>
        <li>Your violation of these Terms.</li>
        <li>Your violation of any third-party rights.</li>
        <li>Content you submit to or transmit through the Service.</li>
    </ul>

    <h2>14. Termination</h2>
    <p>We may suspend or terminate your access to the Service at any time, with or without cause, and with or without notice. Reasons for termination may include, but are not limited to:</p>
    <ul>
        <li>Violation of these Terms or the Acceptable Use Policy.</li>
        <li>Non-payment of subscription fees.</li>
        <li>Fraudulent or illegal activity.</li>
        <li>Extended periods of inactivity on the free tier.</li>
    </ul>
    <p>Upon termination, your right to use the Service ceases immediately. You may request an export of your data within 30 days of termination, after which your data may be permanently deleted.</p>

    <h2>15. Modifications to Terms</h2>
    <p>We reserve the right to modify these Terms at any time. When we make material changes, we will:</p>
    <ul>
        <li>Update the "Last Updated" date at the top of this page.</li>
        <li>Notify you via email or through the Service at least 30 days before the changes take effect.</li>
    </ul>
    <p>Your continued use of the Service after the effective date of the revised Terms constitutes your acceptance of the changes. If you do not agree to the revised Terms, you must stop using the Service and cancel your account.</p>

    <h2>16. Governing Law &amp; Dispute Resolution</h2>
    <p>These Terms are governed by and construed in accordance with the laws of the State of Wyoming, United States, without regard to its conflict of law provisions.</p>
    <p>Any disputes arising out of or relating to these Terms or the Service shall be resolved exclusively in the state or federal courts located in Laramie County, Wyoming. You consent to the personal jurisdiction of such courts.</p>

    <h2>17. Contact Information</h2>
    <p>If you have questions about these Terms, please contact us:</p>
    <p>
        <strong>IuriLabs Limited Company</strong><br>
        1021 E Lincolnway, 9312<br>
        Cheyenne, WY 82001, United States<br>
        EIN: 32-0835892<br>
        Website: <a href="https://ispstatus.com">ispstatus.com</a>
    </p>
</main>

<footer class="legal-footer">
    <div class="legal-footer-inner">
        <span>&copy; <?= date('Y') ?> <?= \Cake\Core\Configure::read('Brand.company', 'IuriLabs') ?> Limited Company. All rights reserved.</span>
        <div class="legal-footer-links">
            <a href="/">Home</a>
            <a href="/terms">Terms of Service</a>
            <a href="/privacy">Privacy Policy</a>
        </div>
    </div>
</footer>

</body>
</html>
