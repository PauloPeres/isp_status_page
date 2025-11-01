<?php
/**
 * Email Layout - Default
 *
 * @var \App\View\AppView $this
 * @var string $siteName
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?= $this->fetch('title') ?></title>
    <style type="text/css">
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 16px;
            line-height: 1.6;
            color: #333333;
            background-color: #f4f4f4;
        }

        .email-wrapper {
            width: 100%;
            background-color: #f4f4f4;
            padding: 20px 0;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .email-header {
            background: linear-gradient(135deg, #1976D2 0%, #1565C0 100%);
            padding: 30px 20px;
            text-align: center;
        }

        .email-header h1 {
            margin: 0;
            color: #ffffff;
            font-size: 24px;
            font-weight: 700;
        }

        .email-body {
            padding: 40px 30px;
        }

        .email-body h2 {
            margin-top: 0;
            color: #1976D2;
            font-size: 20px;
        }

        .email-body p {
            margin: 16px 0;
            color: #555555;
        }

        .button {
            display: inline-block;
            padding: 14px 28px;
            margin: 20px 0;
            background-color: #1976D2;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            text-align: center;
        }

        .button:hover {
            background-color: #1565C0;
        }

        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #1976D2;
            padding: 16px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .warning-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 16px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .error-box {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 16px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .success-box {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            padding: 16px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .email-footer {
            background-color: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            border-top: 1px solid #dee2e6;
        }

        .email-footer p {
            margin: 8px 0;
            font-size: 14px;
            color: #6c757d;
        }

        .email-footer a {
            color: #1976D2;
            text-decoration: none;
        }

        .email-footer a:hover {
            text-decoration: underline;
        }

        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                border-radius: 0;
            }

            .email-body {
                padding: 30px 20px;
            }

            .button {
                display: block;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="email-header">
                <h1><?= isset($siteName) ? h($siteName) : 'ISP Status' ?></h1>
            </div>

            <div class="email-body">
                <?= $this->fetch('content') ?>
            </div>

            <div class="email-footer">
                <p>
                    Este é um email automático. Por favor, não responda.
                </p>
                <p>
                    &copy; <?= date('Y') ?> <?= isset($siteName) ? h($siteName) : 'ISP Status' ?>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
