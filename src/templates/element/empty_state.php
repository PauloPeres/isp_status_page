<?php
$icon = $icon ?? '📭';
$description = $description ?? null;
$actionUrl = $actionUrl ?? null;
$actionLabel = $actionLabel ?? 'Get Started';
?>
<div style="text-align:center;padding:60px 20px;color:#6b7280;">
    <div style="font-size:48px;margin-bottom:16px;"><?= $icon ?></div>
    <h3 style="font-size:18px;font-weight:600;color:#1f2937;margin-bottom:8px;"><?= h($title) ?></h3>
    <?php if ($description): ?>
        <p style="font-size:14px;max-width:400px;margin:0 auto 20px;"><?= h($description) ?></p>
    <?php endif; ?>
    <?php if ($actionUrl): ?>
        <a href="<?= $actionUrl ?>" class="btn btn-primary" style="display:inline-block;padding:10px 24px;background:var(--color-primary,#2563eb);color:#fff;border-radius:6px;text-decoration:none;"><?= h($actionLabel) ?></a>
    <?php endif; ?>
</div>
