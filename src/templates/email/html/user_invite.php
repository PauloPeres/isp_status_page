<?php
/**
 * User Invitation Email Template
 *
 * @var \App\View\AppView $this
 * @var object $user
 * @var string $password
 * @var string $loginUrl
 * @var string $siteName
 */
$this->assign('title', 'Convite de Acesso');
?>

<h2>🎉 Bem-vindo ao <?= h($siteName) ?>!</h2>

<p>Olá <strong><?= h($user->username) ?></strong>!</p>

<p>
    Uma conta foi criada para você no sistema <strong><?= h($siteName) ?></strong>.
    Abaixo estão suas credenciais de acesso:
</p>

<!-- Credentials Box -->
<div class="info-box" style="margin: 20px 0; background: #f0f9ff; border-left: 4px solid #1E88E5;">
    <p style="margin: 0;">
        <strong>👤 Usuário:</strong> <?= h($user->username) ?><br>
        <strong>📧 Email:</strong> <?= h($user->email) ?><br>
        <strong>🔑 Senha Temporária:</strong> <code style="background: #e0e0e0; padding: 2px 6px; border-radius: 3px;"><?= h($password) ?></code><br>
        <strong>🎭 Função:</strong> <?= h(ucfirst($user->role)) ?>
    </p>
</div>

<p>
    Para acessar o sistema, clique no botão abaixo:
</p>

<!-- Login Button -->
<p style="text-align: center; margin: 30px 0;">
    <a href="<?= $loginUrl ?>" class="button">
        Acessar o Sistema
    </a>
</p>

<!-- Alternative Link -->
<div class="info-box">
    <p><strong>Link alternativo:</strong></p>
    <p style="margin: 10px 0 0 0;">
        Se o botão acima não funcionar, copie e cole o link abaixo no seu navegador:
    </p>
    <p style="word-break: break-all; margin: 10px 0 0 0;">
        <a href="<?= $loginUrl ?>"><?= $loginUrl ?></a>
    </p>
</div>

<!-- Warning Box -->
<div class="warning-box">
    <p style="margin: 0;">
        <strong>🔒 Importante - Primeira Vez:</strong>
        Por segurança, você <strong>será obrigado a alterar sua senha</strong> no primeiro login.
        Por favor, escolha uma senha segura que apenas você saiba.
    </p>
</div>

<!-- Security Tips -->
<div class="info-box">
    <p style="margin: 0 0 10px 0;"><strong>💡 Dicas de Segurança:</strong></p>
    <ul style="margin: 0; padding-left: 20px;">
        <li>Use uma senha com no mínimo 8 caracteres</li>
        <li>Combine letras maiúsculas, minúsculas, números e símbolos</li>
        <li>Não compartilhe sua senha com ninguém</li>
        <li>Não use a mesma senha de outros serviços</li>
    </ul>
</div>

<p>
    Se você tiver alguma dúvida ou não solicitou esta conta, entre em contato com o administrador do sistema.
</p>

<p>
    Atenciosamente,<br>
    <strong>Equipe <?= h($siteName) ?></strong>
</p>
