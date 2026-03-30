<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Privacy Policy - ISP Status Page</title>
    <meta name="description" content="Privacy Policy for ISP Status Page, a SaaS uptime monitoring platform by IuriLabs.">
    <link rel="icon" type="image/png" href="/img/icon_isp_status_page.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/img/icon_isp_status_page.png">
    <meta name="theme-color" content="#6366F1">
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
            <img src="/img/icon_isp_status_page.png" alt="ISP Status">
            <span>ISP Status</span>
        </a>
        <nav class="legal-nav">
            <a href="/">Home</a>
            <a href="/terms">Terms</a>
            <a href="/privacy">Privacy</a>
        </nav>
    </div>
</header>

<main class="legal-content">
    <h1>Privacy Policy</h1>
    <p class="legal-meta">Effective Date: March 30, 2026 &middot; Last Updated: March 30, 2026</p>

    <p>IuriLabs Limited Company ("IuriLabs," "we," "us," or "our") operates ISP Status Page ("Service"). This Privacy Policy explains how we collect, use, store, and protect your information when you use our Service.</p>

    <h2>1. Information We Collect</h2>

    <h3>1.1 Account Data</h3>
    <p>When you create an account, we collect:</p>
    <ul>
        <li>Name and email address.</li>
        <li>Password (stored as a bcrypt hash; we never store plaintext passwords).</li>
        <li>Organization name and details.</li>
        <li>OAuth profile information if you sign in via Google or GitHub (name, email, profile picture URL).</li>
    </ul>

    <h3>1.2 Monitoring Data</h3>
    <p>When you configure and use monitors, we collect and store:</p>
    <ul>
        <li>Monitor configurations (URLs, IP addresses, ports, check intervals).</li>
        <li>Check results (response times, status codes, error messages).</li>
        <li>Incident records (timestamps, status changes, acknowledgements).</li>
        <li>Status page content (titles, descriptions, custom messages).</li>
    </ul>

    <h3>1.3 Usage Data</h3>
    <p>We automatically collect certain information about how you use the Service:</p>
    <ul>
        <li>IP address and approximate geographic location.</li>
        <li>Browser type, operating system, and device information.</li>
        <li>Pages viewed and features used within the Service.</li>
        <li>Timestamps of actions performed.</li>
    </ul>

    <h3>1.4 Payment Data</h3>
    <p>When you subscribe to a paid plan, payment information is collected and processed by Stripe. We do not store your full credit card number, CVV, or other sensitive payment details on our servers. We receive and store:</p>
    <ul>
        <li>Stripe customer and subscription identifiers.</li>
        <li>Billing email address.</li>
        <li>Last four digits of your payment card (for display purposes).</li>
        <li>Payment history and invoice data.</li>
    </ul>

    <h2>2. How We Use Information</h2>
    <p>We use the information we collect to:</p>
    <ul>
        <li>Provide, operate, and maintain the Service.</li>
        <li>Process your transactions and manage your subscription.</li>
        <li>Send you alerts and notifications you have configured.</li>
        <li>Communicate with you about your account, including service announcements and security alerts.</li>
        <li>Improve and develop new features for the Service.</li>
        <li>Detect, prevent, and address technical issues, fraud, and security threats.</li>
        <li>Comply with legal obligations.</li>
    </ul>

    <h2>3. Data Storage &amp; Security</h2>
    <p>We take the security of your data seriously and implement appropriate technical and organizational measures, including:</p>
    <ul>
        <li>All data is transmitted over HTTPS/TLS encrypted connections.</li>
        <li>Passwords are hashed using bcrypt with appropriate cost factors.</li>
        <li>API keys and tokens are stored encrypted at rest.</li>
        <li>Database access is restricted and monitored.</li>
        <li>Infrastructure is hosted on secure, professionally managed servers.</li>
        <li>Regular backups are performed to prevent data loss.</li>
    </ul>
    <p>While we strive to protect your data, no method of electronic transmission or storage is 100% secure. We cannot guarantee absolute security.</p>

    <h2>4. Third-Party Service Providers</h2>
    <p>We use the following third-party services to operate the Service. Each provider has access only to the data necessary to perform their function:</p>
    <ul>
        <li><strong>Stripe</strong> (<a href="https://stripe.com/privacy" target="_blank" rel="noopener">Privacy Policy</a>): Payment processing and subscription management. Receives billing and payment information.</li>
        <li><strong>Twilio</strong> (<a href="https://www.twilio.com/legal/privacy" target="_blank" rel="noopener">Privacy Policy</a>): SMS and WhatsApp notification delivery. Receives phone numbers and notification content.</li>
        <li><strong>Google</strong> (<a href="https://policies.google.com/privacy" target="_blank" rel="noopener">Privacy Policy</a>): OAuth authentication. Receives authentication requests; provides name and email.</li>
        <li><strong>GitHub</strong> (<a href="https://docs.github.com/en/site-policy/privacy-policies/github-general-privacy-statement" target="_blank" rel="noopener">Privacy Policy</a>): OAuth authentication. Receives authentication requests; provides name and email.</li>
        <li><strong>SMTP/Email Providers:</strong> Email notification delivery. Receives recipient email addresses and notification content.</li>
    </ul>

    <h2>5. Cookies &amp; Tracking</h2>
    <p>We take a minimal approach to cookies and tracking:</p>
    <ul>
        <li><strong>Authentication Tokens:</strong> We use JWT (JSON Web Tokens) stored in your browser's localStorage to maintain your authenticated session. These are essential for the Service to function.</li>
        <li><strong>Session Data:</strong> Server-side sessions may be used for CSRF protection and temporary state management.</li>
        <li><strong>No Third-Party Tracking:</strong> We do not use third-party analytics, advertising trackers, or social media tracking pixels.</li>
        <li><strong>No Cross-Site Tracking:</strong> We do not track your activity across other websites.</li>
    </ul>

    <h2>6. Data Retention</h2>
    <p>We retain your data based on your subscription plan tier and the type of data:</p>
    <ul>
        <li><strong>Check Results &amp; Metrics:</strong> Retained for the period defined by your plan tier (e.g., 7 days for free, 90 days for Pro, 365 days for Business). Older data is automatically purged.</li>
        <li><strong>Incident Records:</strong> Retained for the duration of your account or as required by your plan.</li>
        <li><strong>Account Data:</strong> Retained for as long as your account is active. Upon account deletion, your data is removed within 30 days, except where retention is required by law.</li>
        <li><strong>Billing Records:</strong> Retained as required for tax and legal compliance purposes (typically 7 years).</li>
        <li><strong>Backups:</strong> Data may persist in encrypted backups for up to 90 days after deletion from the live system.</li>
    </ul>

    <h2>7. Data Sharing</h2>
    <p><strong>We do not sell, rent, or trade your personal information to third parties.</strong></p>
    <p>We may share your information only in the following circumstances:</p>
    <ul>
        <li><strong>Service Providers:</strong> With the third-party providers listed in Section 4, solely to operate the Service.</li>
        <li><strong>Legal Requirements:</strong> When required by law, regulation, legal process, or governmental request.</li>
        <li><strong>Protection of Rights:</strong> To protect the rights, safety, and property of IuriLabs, our users, or the public.</li>
        <li><strong>Business Transfer:</strong> In connection with a merger, acquisition, or sale of assets, in which case your data would remain subject to this Privacy Policy.</li>
        <li><strong>With Your Consent:</strong> When you explicitly authorize us to share your information.</li>
    </ul>

    <h2>8. Your Rights</h2>
    <p>You have the following rights regarding your data:</p>
    <ul>
        <li><strong>Access:</strong> You may request a copy of the personal data we hold about you.</li>
        <li><strong>Correction:</strong> You may request correction of inaccurate or incomplete personal data.</li>
        <li><strong>Deletion:</strong> You may request deletion of your personal data. We will comply unless retention is required by law.</li>
        <li><strong>Export:</strong> You may request an export of your data in a machine-readable format (JSON or CSV).</li>
        <li><strong>Objection:</strong> You may object to certain processing of your data.</li>
        <li><strong>Restriction:</strong> You may request that we restrict processing of your data in certain circumstances.</li>
    </ul>
    <p>To exercise any of these rights, contact us using the information provided in Section 13. We will respond to your request within 30 days.</p>

    <h2>9. International Data Transfers</h2>
    <p>The Service is operated from the United States. If you access the Service from outside the United States, your data may be transferred to, stored, and processed in the United States or other countries where our service providers operate.</p>
    <p>By using the Service, you consent to the transfer of your data to the United States and other jurisdictions that may have different data protection laws than your country of residence.</p>

    <h2>10. Children's Privacy</h2>
    <p>The Service is not intended for individuals under the age of 18. We do not knowingly collect personal information from anyone under 18 years of age. If we become aware that we have collected personal information from a person under 18, we will take steps to delete that information promptly.</p>
    <p>If you are a parent or guardian and believe your child has provided us with personal information, please contact us so we can take appropriate action.</p>

    <h2>11. Changes to This Policy</h2>
    <p>We may update this Privacy Policy from time to time. When we make material changes, we will:</p>
    <ul>
        <li>Update the "Last Updated" date at the top of this page.</li>
        <li>Notify you via email or through the Service at least 30 days before the changes take effect.</li>
    </ul>
    <p>Your continued use of the Service after the effective date of the revised Privacy Policy constitutes your acceptance of the changes.</p>

    <h2>12. CCPA Notice (California Residents)</h2>
    <p>If you are a California resident, you have additional rights under the California Consumer Privacy Act (CCPA):</p>
    <ul>
        <li><strong>Right to Know:</strong> You may request that we disclose the categories and specific pieces of personal information we have collected about you, the categories of sources, the business purpose for collecting it, and the categories of third parties with whom we share it.</li>
        <li><strong>Right to Delete:</strong> You may request deletion of your personal information, subject to certain exceptions.</li>
        <li><strong>Right to Opt-Out:</strong> You have the right to opt out of the "sale" of your personal information. However, we do not sell personal information.</li>
        <li><strong>Non-Discrimination:</strong> We will not discriminate against you for exercising your CCPA rights.</li>
    </ul>
    <p>To submit a CCPA request, contact us using the information provided in Section 13. We will verify your identity before processing your request.</p>

    <h2>13. Contact Information</h2>
    <p>If you have questions or concerns about this Privacy Policy or our data practices, please contact us:</p>
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
        <span>&copy; <?= date('Y') ?> IuriLabs Limited Company. All rights reserved.</span>
        <div class="legal-footer-links">
            <a href="/">Home</a>
            <a href="/terms">Terms of Service</a>
            <a href="/privacy">Privacy Policy</a>
        </div>
    </div>
</footer>

</body>
</html>
