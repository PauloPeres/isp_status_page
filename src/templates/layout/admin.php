<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        <?= $this->fetch('title') ?> - ISP Status Admin
    </title>
    <?= $this->Html->meta('icon') ?>

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

    <?= $this->fetch('script') ?>
</body>
</html>
