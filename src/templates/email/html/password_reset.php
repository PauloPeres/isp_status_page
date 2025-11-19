<?php
/**
 * Password Reset Email Template
 *
 * @var \App\View\AppView $this
 * @var string $resetLink
 * @var object $user
 */
$this->assign('title', 'Recuperação de Senha');
?>

<h2>🔐 Recuperação de Senha</h2>

<p>Olá!</p>

<p>
    Você solicitou a redefinição de senha da sua conta no ISP Status Page.
</p>

<!-- User Info Box -->
<div class="info-box" style="margin: 20px 0;">
    <p style="margin: 0;">
        <strong>👤 Usuário:</strong> <?= h($user->username) ?><br>
        <strong>📧 Email:</strong> <?= h($user->email) ?>
    </p>
</div>

<p>
    Para redefinir sua senha, clique no botão abaixo:
</p>

<!-- Reset Button -->
<p style="text-align: center; margin: 30px 0;">
    <a href="<?= $resetLink ?>" class="button">
        Redefinir Minha Senha
    </a>
</p>

<!-- Alternative Link -->
<div class="info-box">
    <p><strong>Link alternativo:</strong></p>
    <p style="margin: 10px 0 0 0;">
        Se o botão acima não funcionar, copie e cole o link abaixo no seu navegador:
    </p>
    <p style="word-break: break-all; margin: 10px 0 0 0;">
        <a href="<?= $resetLink ?>"><?= $resetLink ?></a>
    </p>
</div>

<!-- Warning -->
<div class="warning-box">
    <p style="margin: 0;">
        <strong>⏰ Importante:</strong>
        Este link expira em 1 hora por segurança.
    </p>
</div>

<p>
    Se você não solicitou a redefinição de senha, ignore este email. Sua senha permanecerá inalterada.
</p>
