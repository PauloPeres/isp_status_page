<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        <?= $this->fetch('title') ?> - ISP Status
    </title>
    <?= $this->Html->meta('icon') ?>

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
