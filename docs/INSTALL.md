# Guia de Instalação

Este guia descreve como instalar e configurar o ISP Status Page.

## Requisitos

### Software Necessário

- **PHP**: 8.1 ou superior
- **Composer**: 2.x
- **SQLite3**: Incluído no PHP
- **Web Server**: Apache, Nginx ou built-in PHP server
- **Cron**: Para execução de tarefas agendadas

### Extensões PHP Necessárias

```bash
# Ubuntu/Debian
sudo apt-get install php8.1-cli php8.1-sqlite3 php8.1-mbstring php8.1-xml php8.1-curl php8.1-intl

# macOS (com Homebrew)
brew install php@8.1
brew install composer

# Windows
# Baixe PHP de https://windows.php.net/download/
# Instale Composer de https://getcomposer.org/download/
```

## Instalação - Desenvolvimento

### 1. Clone o Repositório

```bash
git clone https://github.com/seu-usuario/isp_status_page.git
cd isp_status_page
```

### 2. Instale Dependências

```bash
composer install
```

### 3. Configure o Ambiente

```bash
# Copie o arquivo de configuração de exemplo
cp .env.example .env

# Edite o .env com suas configurações
nano .env
```

**Configurações mínimas necessárias**:
```env
APP_NAME="ISP Status Page"
APP_DEBUG=true
SECURITY_SALT="gere-um-salt-aleatorio-aqui"

# Banco de dados (SQLite é o padrão)
DATABASE_URL="sqlite:///database.db"

# Email (configure se quiser testar alertas)
EMAIL_HOST="smtp.gmail.com"
EMAIL_PORT=587
EMAIL_USERNAME="seu-email@gmail.com"
EMAIL_PASSWORD="sua-senha-ou-app-password"
EMAIL_FROM="seu-email@gmail.com"
```

**Gerar Security Salt**:
```bash
# Após TASK-000 ser completada
bin/cake security generate_salt
```

### 4. Crie o Banco de Dados

```bash
# Criar arquivo do banco (se não existir)
touch database.db

# Executar migrations
bin/cake migrations migrate

# Popular com dados iniciais
bin/cake migrations seed
```

### 5. Configure Permissões

```bash
# Linux/macOS
chmod -R 777 tmp/
chmod -R 777 logs/
chmod 666 database.db

# Ou com www-data (Apache/Nginx)
sudo chown -R www-data:www-data tmp/ logs/ database.db
```

### 6. Inicie o Servidor

```bash
# Servidor de desenvolvimento (porta 8765)
bin/cake server

# Ou especifique porta
bin/cake server -p 8080

# Ou com host específico
bin/cake server -H 0.0.0.0 -p 8080
```

Acesse: http://localhost:8765

### 7. Login Inicial

Credenciais padrão (seeds):
- **Username**: admin
- **Password**: admin123

**IMPORTANTE**: Altere a senha após primeiro login!

## Instalação - Produção

### 1. Preparação do Servidor

```bash
# Ubuntu/Debian com Apache
sudo apt-get update
sudo apt-get install apache2 php8.1 php8.1-cli php8.1-sqlite3 php8.1-mbstring php8.1-xml php8.1-curl php8.1-intl composer git

# Habilitar módulos Apache
sudo a2enmod rewrite
sudo a2enmod headers
sudo systemctl restart apache2
```

### 2. Clone e Configure

```bash
# Clone para diretório web
cd /var/www/
sudo git clone https://github.com/seu-usuario/isp_status_page.git
cd isp_status_page

# Instale dependências (sem dev)
sudo composer install --no-dev --optimize-autoloader

# Configure ambiente
sudo cp .env.example .env
sudo nano .env
```

**Configurações de produção**:
```env
APP_DEBUG=false
DATABASE_URL="sqlite:///database.db"
SECURITY_SALT="salt-super-seguro-gerado"

# Email real
EMAIL_HOST="smtp.seuservidor.com"
EMAIL_PORT=587
EMAIL_USERNAME="noreply@seudominio.com"
EMAIL_PASSWORD="senha-segura"

STATUS_PAGE_TITLE="Status - Seu ISP"
STATUS_PAGE_PUBLIC=true
```

### 3. Configure Banco de Dados

```bash
sudo bin/cake migrations migrate
sudo bin/cake migrations seed
```

### 4. Configure Permissões

```bash
sudo chown -R www-data:www-data /var/www/isp_status_page
sudo chmod -R 755 /var/www/isp_status_page
sudo chmod -R 777 /var/www/isp_status_page/tmp
sudo chmod -R 777 /var/www/isp_status_page/logs
sudo chmod 666 /var/www/isp_status_page/database.db
```

### 5. Configure Apache Virtual Host

```bash
sudo nano /etc/apache2/sites-available/status.conf
```

```apache
<VirtualHost *:80>
    ServerName status.seudominio.com
    DocumentRoot /var/www/isp_status_page/webroot

    <Directory /var/www/isp_status_page/webroot>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/isp_status_error.log
    CustomLog ${APACHE_LOG_DIR}/isp_status_access.log combined
</VirtualHost>
```

**Habilitar site**:
```bash
sudo a2ensite status.conf
sudo systemctl reload apache2
```

### 6. Configure SSL (Let's Encrypt)

```bash
sudo apt-get install certbot python3-certbot-apache
sudo certbot --apache -d status.seudominio.com
```

### 7. Configure Cron Jobs

```bash
sudo crontab -e -u www-data
```

Adicione:
```cron
# Verificação de monitores (a cada minuto)
* * * * * cd /var/www/isp_status_page && bin/cake monitor_check >> /dev/null 2>&1

# Limpeza diária (às 3h da manhã)
0 3 * * * cd /var/www/isp_status_page && bin/cake cleanup >> /dev/null 2>&1

# Backup diário (às 2h da manhã)
0 2 * * * cd /var/www/isp_status_page && bin/cake backup >> /dev/null 2>&1
```

## Instalação - Docker (Futuro)

```dockerfile
# Dockerfile será criado em desenvolvimento futuro
FROM php:8.1-apache
# ... configurações
```

```bash
docker-compose up -d
```

## Configuração Nginx (Alternativa ao Apache)

```nginx
server {
    listen 80;
    server_name status.seudominio.com;
    root /var/www/isp_status_page/webroot;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        try_files $uri =404;
        include fastcgi_params;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.ht {
        deny all;
    }

    access_log /var/log/nginx/isp_status_access.log;
    error_log /var/log/nginx/isp_status_error.log;
}
```

## Verificação da Instalação

### 1. Testes Básicos

```bash
# Executar testes
vendor/bin/phpunit

# Verificar configuração
bin/cake version

# Verificar conexão com banco
bin/cake migrations status
```

### 2. Acesso Web

- **Status Page**: http://seu-dominio/
- **Admin**: http://seu-dominio/admin
- **Login**: http://seu-dominio/users/login

### 3. Verificar Cron

```bash
# Executar manualmente
bin/cake monitor_check

# Ver logs
tail -f logs/error.log
tail -f logs/debug.log
```

## Troubleshooting

### Erro: "Unable to connect to database"

**Solução**:
```bash
# Verifique se o arquivo existe
ls -l database.db

# Verifique permissões
chmod 666 database.db

# Verifique configuração
cat config/app_local.php | grep database
```

### Erro: "tmp directory is not writable"

**Solução**:
```bash
chmod -R 777 tmp/
# Ou
sudo chown -R www-data:www-data tmp/
```

### Cron não está executando

**Solução**:
```bash
# Verificar se cron está rodando
sudo systemctl status cron

# Ver logs do cron
sudo tail -f /var/log/syslog | grep CRON

# Testar comando manualmente
sudo -u www-data bin/cake monitor_check
```

### Emails não estão sendo enviados

**Solução**:
```bash
# Testar configuração SMTP
bin/cake email test your@email.com

# Ver logs
tail -f logs/error.log | grep Email
```

### Performance lenta

**Soluções**:
```bash
# Limpar cache
bin/cake cache clear_all

# Otimizar banco
sqlite3 database.db "VACUUM;"

# Verificar tamanho do banco
ls -lh database.db

# Executar cleanup
bin/cake cleanup
```

## Atualização

### Atualizar o Sistema

```bash
# Backup primeiro!
cp database.db database.db.backup

# Pull últimas mudanças
git pull origin main

# Atualizar dependências
composer install --no-dev

# Executar novas migrations
bin/cake migrations migrate

# Limpar cache
bin/cake cache clear_all

# Reiniciar servidor
sudo systemctl restart apache2
```

## Backup

### Backup Manual

```bash
# Backup completo
tar -czf backup-$(date +%Y%m%d).tar.gz database.db config/ logs/

# Apenas banco
cp database.db backups/database-$(date +%Y%m%d).db
```

### Restore

```bash
# Restaurar banco
cp backups/database-20241031.db database.db

# Ou de tar
tar -xzf backup-20241031.tar.gz
```

## Migração de SQLite para MySQL/PostgreSQL

### 1. Exportar dados

```bash
# Exportar para SQL
sqlite3 database.db .dump > export.sql
```

### 2. Criar novo banco

```sql
-- MySQL
CREATE DATABASE isp_status_page CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Atualizar configuração

```env
# .env
DATABASE_URL="mysql://user:pass@localhost/isp_status_page?encoding=utf8mb4"
```

### 4. Executar migrations no novo banco

```bash
bin/cake migrations migrate
```

### 5. Importar dados (manualmente ou via script)

## Monitoramento da Aplicação

### Logs Importantes

```bash
# Erros da aplicação
tail -f logs/error.log

# Debug (se habilitado)
tail -f logs/debug.log

# Queries (se habilitado)
tail -f logs/queries.log

# Logs do Apache
tail -f /var/log/apache2/isp_status_error.log
```

### Métricas

```bash
# Tamanho do banco
ls -lh database.db

# Número de monitores ativos
sqlite3 database.db "SELECT COUNT(*) FROM monitors WHERE active=1;"

# Últimas verificações
sqlite3 database.db "SELECT * FROM monitor_checks ORDER BY created DESC LIMIT 10;"
```

## Segurança

### Checklist de Produção

- [ ] `APP_DEBUG=false` no .env
- [ ] Security Salt único e forte
- [ ] Senha do admin alterada
- [ ] SSL/HTTPS configurado
- [ ] Firewall configurado
- [ ] Backups automáticos funcionando
- [ ] Logs sendo rotacionados
- [ ] Permissões de arquivo corretas
- [ ] Database fora do webroot (ou protegido)
- [ ] .env não está no controle de versão
- [ ] Atualizações de segurança do SO aplicadas

## Suporte

- **Documentação**: [docs/](../README.md)
- **Issues**: GitHub Issues
- **Email**: (a definir)

## Próximos Passos

Após instalação bem-sucedida:

1. Acesse o admin e altere a senha
2. Configure as settings em Admin → Settings
3. Crie seu primeiro monitor em Admin → Monitors
4. Aguarde alguns minutos para verificações
5. Acesse a status page pública
6. Configure alertas conforme necessário

---

**Instalação concluída! 🎉**
