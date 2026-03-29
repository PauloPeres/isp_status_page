<?php
/**
 * @var \App\View\AppView $this
 * @var array $errors
 * @var array $formData
 */

$this->assign('title', __('Contact Sales'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= __('Contact Sales') ?> - ISP Status</title>
    <link rel="icon" type="image/png" href="/img/icon_isp_status_page.png">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        .sales-nav {
            background: #fff;
            border-bottom: 1px solid #e0e0e0;
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .sales-nav a {
            color: #1E88E5;
            text-decoration: none;
            font-weight: 600;
        }
        .sales-nav img { height: 32px; }
        .sales-container {
            max-width: 700px;
            margin: 3rem auto;
            padding: 0 1.5rem;
        }
        .sales-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .sales-header h1 {
            font-size: 2rem;
            color: #1a1a2e;
            margin-bottom: 0.5rem;
        }
        .sales-header p {
            color: #6c757d;
            font-size: 1.1rem;
        }
        .sales-card {
            background: #fff;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.35rem;
            color: #333;
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.65rem 0.85rem;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.2s;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1E88E5;
            box-shadow: 0 0 0 3px rgba(30,136,229,0.15);
        }
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        .form-error {
            color: #E53935;
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .btn-submit {
            display: block;
            width: 100%;
            padding: 0.85rem;
            background: #1a1a2e;
            color: #fff;
            font-size: 1.05rem;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 0.5rem;
        }
        .btn-submit:hover {
            background: #2d2d4a;
        }
        .flash-message {
            padding: 0.85rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        .flash-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }
        .flash-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }
        .enterprise-features {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e0e0e0;
        }
        .enterprise-features li {
            list-style: none;
            font-size: 0.9rem;
            color: #555;
            padding: 0.2rem 0;
        }
        .enterprise-features li::before {
            content: "\2713";
            color: #43A047;
            font-weight: 700;
            margin-right: 0.5rem;
        }
        @media (max-width: 600px) {
            .form-row { grid-template-columns: 1fr; }
            .enterprise-features { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<nav class="sales-nav">
    <a href="/">
        <img src="/img/icon_isp_status_page.png" alt="ISP Status">
    </a>
    <a href="/"><?= __('ISP Status') ?></a>
</nav>

<div class="sales-container">
    <div class="sales-header">
        <h1><?= __('Contact Our Sales Team') ?></h1>
        <p><?= __('Get a custom Enterprise plan tailored to your organization.') ?></p>
    </div>

    <?= $this->Flash->render() ?>

    <div class="sales-card">
        <form method="post" action="/contact/sales">
            <?php if (!empty($this->request->getAttribute('csrfToken'))): ?>
                <input type="hidden" name="_csrfToken" value="<?= h($this->request->getAttribute('csrfToken')) ?>">
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label for="name"><?= __('Your Name') ?> *</label>
                    <input type="text" id="name" name="name" value="<?= h($formData['name'] ?? '') ?>" required placeholder="<?= __('John Smith') ?>">
                    <?php if (!empty($errors['name'])): ?>
                        <div class="form-error"><?= h($errors['name']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="email"><?= __('Work Email') ?> *</label>
                    <input type="email" id="email" name="email" value="<?= h($formData['email'] ?? '') ?>" required placeholder="john@company.com">
                    <?php if (!empty($errors['email'])): ?>
                        <div class="form-error"><?= h($errors['email']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="company"><?= __('Company Name') ?> *</label>
                    <input type="text" id="company" name="company" value="<?= h($formData['company'] ?? '') ?>" required placeholder="<?= __('Acme Corp') ?>">
                    <?php if (!empty($errors['company'])): ?>
                        <div class="form-error"><?= h($errors['company']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="expected_monitors"><?= __('Expected Monitors') ?> *</label>
                    <input type="number" id="expected_monitors" name="expected_monitors" value="<?= h($formData['expected_monitors'] ?? '') ?>" required min="1" placeholder="500">
                    <?php if (!empty($errors['expected_monitors'])): ?>
                        <div class="form-error"><?= h($errors['expected_monitors']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="message"><?= __('Tell us about your needs') ?> *</label>
                <textarea id="message" name="message" required placeholder="<?= __('Describe your monitoring requirements, expected scale, compliance needs, etc.') ?>"><?= h($formData['message'] ?? '') ?></textarea>
                <?php if (!empty($errors['message'])): ?>
                    <div class="form-error"><?= h($errors['message']) ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn-submit"><?= __('Send Inquiry') ?></button>
        </form>

        <ul class="enterprise-features">
            <li><?= __('Unlimited monitors') ?></li>
            <li><?= __('15-second check intervals') ?></li>
            <li><?= __('Unlimited team members') ?></li>
            <li><?= __('Unlimited status pages') ?></li>
            <li><?= __('50,000 API req/hr') ?></li>
            <li><?= __('365-day data retention') ?></li>
            <li><?= __('SSO / SAML authentication') ?></li>
            <li><?= __('SLA tracking') ?></li>
            <li><?= __('Dedicated support manager') ?></li>
            <li><?= __('Custom domain support') ?></li>
        </ul>
    </div>
</div>

</body>
</html>
