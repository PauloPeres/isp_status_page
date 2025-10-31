<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        <?= $this->fetch('title') ?> - ISP Status
    </title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= $this->Url->build('/favicon.svg') ?>">
    <link rel="alternate icon" type="image/x-icon" href="<?= $this->Url->build('/favicon.ico') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= $this->Url->build('/favicon.svg') ?>">
    <link rel="manifest" href="<?= $this->Url->build('/site.webmanifest') ?>">
    <meta name="theme-color" content="#1E88E5">

    <meta name="description" content="Página de status em tempo real dos serviços de internet">
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

    <?= $this->fetch('script') ?>
</body>
</html>
