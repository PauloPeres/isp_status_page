<?php
/**
 * Test Email Template (Text Version)
 *
 * @var \App\View\AppView $this
 * @var string $siteName
 */
?>
<?= __d('emails', 'EMAIL DE TESTE') ?>

<?= str_repeat('=', 60) ?>


<?= __d('emails', 'Olá!') ?>

<?= __d('emails', 'Este é um email de teste do sistema {0}.', $siteName) ?>

<?= __d('emails', 'Sucesso!') ?>

<?= __d('emails', 'Se você está recebendo este email, significa que o sistema de envio de emails está configurado corretamente.') ?>


<?= __d('emails', 'Informações do Email:') ?>

- <?= __d('emails', 'Data/Hora:') ?> <?= date('d/m/Y H:i:s') ?>

- <?= __d('emails', 'Fuso horário:') ?> <?= date_default_timezone_get() ?>

- <?= __d('emails', 'Sistema:') ?> <?= $siteName ?>


<?= __d('emails', 'Agora você pode começar a enviar notificações aos seus usuários!') ?>

<?= str_repeat('=', 60) ?>


<?= __d('emails', 'Este é um email automático de teste. Não é necessário responder.') ?>
