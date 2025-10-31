<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        <?= $this->fetch('title') ?> - ISP Status Admin
    </title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= $this->Url->build('/favicon.svg') ?>">
    <link rel="alternate icon" type="image/x-icon" href="<?= $this->Url->build('/favicon.ico') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= $this->Url->build('/favicon.svg') ?>">
    <link rel="manifest" href="<?= $this->Url->build('/site.webmanifest') ?>">
    <meta name="theme-color" content="#1E88E5">

    <?= $this->Html->css(['admin']) ?>
    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
</head>
<body>
    <?= $this->element('admin/navbar') ?>

    <div class="admin-container">
        <?= $this->element('admin/sidebar') ?>

        <main class="admin-content">
            <?= $this->Flash->render() ?>

            <div class="content-wrapper">
                <?= $this->fetch('content') ?>
            </div>

            <?= $this->element('admin/footer') ?>
        </main>
    </div>

    <?= $this->Html->script('datetime-utils') ?>
    <?= $this->fetch('script') ?>
</body>
</html>
