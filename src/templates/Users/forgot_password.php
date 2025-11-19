<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= __('Recuperar Senha') ?> - ISP Status</title>
    <style>
        :root {
            /* Cores Primárias */
            --color-primary: #1E88E5;
            --color-success: #43A047;
            --color-dark: #263238;
            --color-white: #FFFFFF;

            /* Cores Secundárias */
            --color-primary-light: #90CAF9;
            --color-warning: #FDD835;
            --color-error: #E53935;

            /* Tons Neutros */
            --color-gray-light: #ECEFF1;
            --color-gray-medium: #B0BEC5;

            /* Hover States */
            --color-primary-hover: #1976D2;
            --color-error-hover: #D32F2F;

            /* Espaçamento */
            --space-md: 16px;
            --space-lg: 24px;
            --space-xl: 32px;

            /* Border Radius */
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-xl: 20px;

            /* Sombras */
            --shadow-md: 0 2px 8px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-hover) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-box {
            background: var(--color-white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 420px;
            padding: 50px 40px;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo {
            width: 80px;
            height: 80px;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            margin-bottom: 20px;
        }

        h1 {
            color: var(--color-dark);
            font-size: 28px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 8px;
        }

        .subtitle {
            color: var(--color-gray-medium);
            font-size: 15px;
            text-align: center;
            margin-bottom: 40px;
            line-height: 1.6;
        }

        .input-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            color: var(--color-dark);
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        input {
            width: 100%;
            padding: 15px 18px;
            border: 2px solid var(--color-gray-light);
            border-radius: var(--radius-lg);
            font-size: 15px;
            color: var(--color-dark);
            background: var(--color-white);
            transition: all 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(30, 136, 229, 0.1);
        }

        input::placeholder {
            color: var(--color-gray-medium);
        }

        .btn {
            width: 100%;
            padding: 16px;
            background: var(--color-primary);
            color: var(--color-white);
            border: none;
            border-radius: var(--radius-lg);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 8px;
            box-shadow: 0 4px 12px rgba(30, 136, 229, 0.3);
        }

        .btn:hover {
            background: var(--color-primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(30, 136, 229, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .alert {
            padding: 14px 18px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
        }

        .alert-error {
            background: #FFEBEE;
            color: #C62828;
            border-left: 4px solid var(--color-error);
        }

        .alert-success {
            background: #E8F5E9;
            color: #2E7D32;
            border-left: 4px solid var(--color-success);
        }

        /* CakePHP Flash Messages */
        .message {
            padding: 14px 18px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
        }

        .message.error {
            background: #FFEBEE;
            color: #C62828;
            border-left: 4px solid var(--color-error);
        }

        .message.success {
            background: #E8F5E9;
            color: #2E7D32;
            border-left: 4px solid var(--color-success);
        }

        .message.warning {
            background: #FFF9E6;
            color: #F57C00;
            border-left: 4px solid var(--color-warning);
        }

        .message.info {
            background: #E3F2FD;
            color: #1565C0;
            border-left: 4px solid var(--color-primary);
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: var(--color-primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: var(--color-primary-hover);
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .login-box {
                padding: 40px 30px;
            }

            h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo-container">
            <img src="/img/icon_isp_status_page.png" alt="ISP Status" class="logo">
            <h1><?= __('Recuperar Senha') ?></h1>
            <p class="subtitle">
                <?= __('Informe seu email e enviaremos um link para redefinir sua senha.') ?>
            </p>
        </div>

        <?= $this->Flash->render() ?>

        <form method="post" action="<?= $this->Url->build(['controller' => 'Users', 'action' => 'forgotPassword']) ?>">
            <?php if (isset($this->request)): ?>
                <input type="hidden" name="_csrfToken" value="<?= $this->request->getAttribute('csrfToken') ?>">
            <?php endif; ?>

            <div class="input-group">
                <label for="email"><?= __('Email') ?></label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="<?= __('Digite seu email cadastrado') ?>"
                    required
                    autofocus
                    autocomplete="email"
                >
            </div>

            <button type="submit" class="btn"><?= __('Enviar Link de Recuperação') ?></button>
        </form>

        <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'login']) ?>" class="back-link">
            ← <?= __('Voltar para o login') ?>
        </a>
    </div>
</body>
</html>
