# Email Configuration Guide

This guide explains how to configure email functionality for the ISP Status Page application.

## Overview

The application uses CakePHP's email system to send:
- **Verification emails** when users subscribe to notifications
- **Incident notifications** when services go down or come back up
- **Test emails** to verify email configuration

## Configuration Methods

### Method 1: Environment Variables (Recommended)

The easiest way to configure email is through environment variables in your `.env` file.

1. Copy `.env.example` to `.env` if you haven't already:
   ```bash
   cp config/.env.example config/.env
   ```

2. Add one of the following configurations to your `.env` file:

#### For Development (Debug Mode)
This logs emails instead of sending them - useful for testing:

```bash
export EMAIL_TRANSPORT_DEFAULT_URL="debug://"
```

#### For Production (SMTP)

**Generic SMTP:**
```bash
export EMAIL_TRANSPORT_DEFAULT_URL="smtp://username:password@smtp.example.com:587?tls=true"
```

**Gmail:**
```bash
export EMAIL_TRANSPORT_DEFAULT_URL="smtp://your-email@gmail.com:your-app-password@smtp.gmail.com:587?tls=true"
```

> **Note for Gmail:** You need to use an [App Password](https://support.google.com/accounts/answer/185833), not your regular Gmail password.

**SendGrid:**
```bash
export EMAIL_TRANSPORT_DEFAULT_URL="smtp://apikey:your-sendgrid-api-key@smtp.sendgrid.net:587?tls=true"
```

**Mailgun:**
```bash
export EMAIL_TRANSPORT_DEFAULT_URL="smtp://postmaster@your-domain.mailgun.org:your-mailgun-password@smtp.mailgun.org:587?tls=true"
```

### Method 2: Database Settings

You can also configure email settings through the Admin Panel:

1. Log in to the admin panel
2. Go to **Settings** > **Email**
3. Configure the following settings:
   - `email_from`: Email address to send from (e.g., `noreply@example.com`)
   - `email_from_name`: Display name for sent emails (e.g., `ISP Status`)
   - `smtp_host`: SMTP server hostname
   - `smtp_port`: SMTP server port (usually 587 for TLS or 465 for SSL)
   - `smtp_username`: SMTP authentication username
   - `smtp_password`: SMTP authentication password
   - `smtp_tls`: Enable TLS encryption (recommended)

> **Note:** Environment variables take precedence over database settings.

## Testing Email Configuration

To verify your email configuration is working:

1. Go to **Admin Panel** > **Settings**
2. Scroll to the **Email Settings** section
3. Enter your email address in the test field
4. Click **"Send Test Email"**
5. Check your inbox for the test email

If the email doesn't arrive:
- Check the application logs in `logs/error.log`
- Verify your SMTP credentials
- Check if your email provider requires app-specific passwords
- Ensure TLS/SSL settings match your provider's requirements

## Email Templates

The application includes the following email templates:

### Subscriber Verification Email
- **Template:** `templates/email/html/subscriber_verification.php`
- **Sent when:** User subscribes to notifications
- **Contains:** Verification link to confirm email address

### Incident Notification (Down)
- **Template:** `templates/email/html/incident_down.php`
- **Sent when:** A service goes down
- **Contains:** Incident details, severity, start time

### Incident Notification (Resolved)
- **Template:** `templates/email/html/incident_resolved.php`
- **Sent when:** An incident is resolved
- **Contains:** Resolution details, downtime duration, resolution notes

### Test Email
- **Template:** `templates/email/html/test.php`
- **Sent when:** Testing email configuration
- **Contains:** Confirmation that email system is working

## Email Layout

All emails use a responsive HTML layout located at:
- `templates/layout/email/html/default.php`

The layout includes:
- Professional header with site name
- Responsive design that works on mobile devices
- Styled buttons and information boxes
- Footer with unsubscribe information

## Customization

### Changing Email Templates

To customize email content, edit the template files in `templates/email/html/`:

```php
// Example: Customize verification email
// File: templates/email/html/subscriber_verification.php

<h2>Welcome!</h2>
<p>Thanks for subscribing to <?= h($siteName) ?> notifications!</p>
```

### Changing Email Layout

To customize the overall email design, edit:
- `templates/layout/email/html/default.php`

You can modify:
- Colors and fonts
- Header/footer content
- Overall styling

## Troubleshooting

### Emails not sending

1. **Check logs:** Look in `logs/error.log` for error messages
2. **Verify credentials:** Ensure SMTP username and password are correct
3. **Test connection:** Use a tool like `telnet` to test SMTP connectivity:
   ```bash
   telnet smtp.example.com 587
   ```
4. **Check firewall:** Ensure port 587 (or 465) is not blocked

### Gmail-specific issues

- Use App Passwords instead of your regular password
- Enable "Less secure app access" if not using App Passwords (not recommended)
- Check [Google's SMTP settings](https://support.google.com/mail/answer/7126229)

### Verification emails not received

- Check spam folder
- Verify the `email_from` address is valid
- Some email providers reject emails from certain domains

### Debug mode emails

When using `debug://` transport, emails are logged to:
- `logs/debug.log`

You can see the email content in the log file without actually sending.

## Production Recommendations

For production use:

1. **Use a dedicated email service:** SendGrid, Mailgun, Amazon SES, etc.
2. **Set up SPF and DKIM:** Prevent emails from going to spam
3. **Use a dedicated sending domain:** E.g., `noreply@notifications.yourdomain.com`
4. **Monitor email logs:** Track delivery rates and failures
5. **Implement rate limiting:** Prevent email spam
6. **Use environment variables:** Keep credentials secure

## Email Service Integration

The `EmailService` class (`src/Service/EmailService.php`) provides these methods:

```php
// Send verification email
$emailService->sendVerificationEmail($subscriber);

// Send incident notification to multiple subscribers
$emailService->sendIncidentNotification($incident, $subscribers);

// Send test email
$emailService->sendTestEmail('test@example.com');
```

## Security Notes

- **Never commit `.env` files** to version control
- **Use app-specific passwords** when possible
- **Encrypt SMTP passwords** in database settings
- **Use TLS/SSL** for SMTP connections
- **Validate email addresses** before sending
- **Implement rate limiting** to prevent abuse

## Support

For issues or questions about email configuration:
- Check logs in `logs/error.log`
- Review CakePHP email documentation
- Verify your email provider's SMTP settings
- Test with debug mode first

---

**Last Updated:** October 2024
