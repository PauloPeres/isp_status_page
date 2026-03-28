<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($statusPage->name ?? 'Status') ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: transparent; }
        .widget {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 8px;
            background: <?= h($bgColor) ?>;
            font-size: 14px;
            font-weight: 600;
            color: <?= h($statusColor) ?>;
            border: 1px solid <?= h($statusColor) ?>33;
        }
        .dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: <?= h($statusColor) ?>;
        }
        .widget-link {
            text-decoration: none;
            color: inherit;
        }
    </style>
</head>
<body>
    <a href="<?= $this->Url->build(['controller' => 'StatusPages', 'action' => 'show', $statusPage->slug]) ?>" target="_blank" class="widget-link">
        <div class="widget">
            <span class="dot"></span>
            <span><?= h($statusLabel) ?></span>
        </div>
    </a>
</body>
</html>
