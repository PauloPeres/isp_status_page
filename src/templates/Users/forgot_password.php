<!DOCTYPE html>
<html lang="<?= h(str_replace('_', '-', \Cake\I18n\I18n::getLocale())) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= __d('users', 'Recover Password') ?> - ISP Status</title>
    <link rel="stylesheet" href="/css/auth.css">
</head>
<body>
    <div class="login-box">
        <div class="logo-container">
            <img src="/img/icon_isp_status_page.png" alt="ISP Status" class="logo">
            <h1><?= __d('users', 'Recover Password') ?></h1>
            <p class="subtitle multiline">
                <?= __d('users', 'Enter your email and we will send you a link to reset your password.') ?>
            </p>
        </div>

        <?= $this->Flash->render() ?>

        <form method="post" action="<?= $this->Url->build(['controller' => 'Users', 'action' => 'forgotPassword']) ?>">
            <?php if (isset($this->request)): ?>
                <input type="hidden" name="_csrfToken" value="<?= $this->request->getAttribute('csrfToken') ?>">
            <?php endif; ?>

            <div class="input-group">
                <label for="email"><?= __d('users', 'Email') ?></label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="<?= __d('users', 'Enter your registered email') ?>"
                    required
                    autofocus
                    autocomplete="email"
                >
            </div>

            <button type="submit" class="btn"><?= __d('users', 'Send Recovery Link') ?></button>
        </form>

        <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'login']) ?>" class="back-link">
            &larr; <?= __d('users', 'Back to login') ?>
        </a>
    </div>
</body>
</html>
