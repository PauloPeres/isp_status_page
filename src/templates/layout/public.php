<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        <?= $this->fetch('title') ?><?php if (isset($siteName)): ?> - <?= h($siteName) ?><?php else: ?> - ISP Status<?php endif; ?>
    </title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= $this->Url->build('/img/icon_isp_status_page.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= $this->Url->build('/img/icon_isp_status_page.png') ?>">
    <meta name="theme-color" content="#1E88E5">

    <meta name="description" content="<?= __('Página de status em tempo real dos serviços de internet') ?>">
    <meta name="robots" content="index, follow">

    <?= $this->Html->css(['public']) ?>
    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
</head>
<body>
    <?= $this->element('public/header') ?>

    <main class="public-main">
        <?= $this->fetch('content') ?>
    </main>

    <?= $this->element('public/footer') ?>

    <?= $this->Html->script('datetime-utils') ?>
    <?= $this->fetch('script') ?>
</body>
</html>
