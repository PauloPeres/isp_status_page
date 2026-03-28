<?php
/**
 * Breadcrumb Navigation Element
 *
 * @var array $breadcrumbs — array of ['title' => 'X', 'url' => '/path' or null for current]
 */
if (empty($breadcrumbs)) return;
?>
<nav class="breadcrumbs" aria-label="breadcrumb">
    <?php foreach ($breadcrumbs as $i => $crumb): ?>
        <?php if ($i > 0): ?><span class="breadcrumb-separator">/</span><?php endif; ?>
        <?php if (!empty($crumb['url'])): ?>
            <a href="<?= $crumb['url'] ?>" class="breadcrumb-link"><?= h($crumb['title']) ?></a>
        <?php else: ?>
            <span class="breadcrumb-current"><?= h($crumb['title']) ?></span>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
