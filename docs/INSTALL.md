# Guia de Instalação

Este guia descreve como instalar e configurar o ISP Status Page.

## Requisitos

### Software Necessario

- **PHP**: 8.1 ou superior (8.4 recomendado)
- **Composer**: 2.x
- **PostgreSQL**: 16+
- **Redis**: 7+
- **Web Server**: Apache, Nginx ou built-in PHP server
- **Cron**: Para execucao de tarefas agendadas

### Extensoes PHP Necessarias

```bash
# Ubuntu/Debian
sudo apt-get install php8.4-cli php8.4-pgsql php8.4-mbstring php8.4-xml php8.4-curl php8.4-intl php8.4-redis postgresql-16 redis-server

# macOS (com Homebrew)
brew install php@8.4 postgresql@16 redis
brew install composer
pecl install redis

# Windows
# Baixe PHP de https://windows.php.net/download/
# Instale Composer de https://getcomposer.org/download/
# Instale PostgreSQL de https://www.postgresql.org/download/windows/
# Instale Redis via WSL ou Memurai (Windows Redis alternative)
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

# Banco de dados (PostgreSQL)
DATABASE_URL="postgres://isp_status:isp_status@localhost:5432/isp_status_page"

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
# Criar banco PostgreSQL
sudo -u postgres createuser isp_status --pwprompt
sudo -u postgres createdb isp_status_page --owner=isp_status

# Executar migrations
bin/cake migrations migrate

# Popular com dados iniciais
bin/cake migrations seed
```

### 5. Configure Permissoes

```bash
# Linux/macOS
chmod -R 777 tmp/
chmod -R 777 logs/

# Ou com www-data (Apache/Nginx)
sudo chown -R www-data:www-data tmp/ logs/
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
sudo apt-get install apache2 php8.4 php8.4-cli php8.4-pgsql php8.4-mbstring php8.4-xml php8.4-curl php8.4-intl php8.4-redis postgresql-16 redis-server composer git

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
DATABASE_URL="postgres://isp_status:senha-segura@localhost:5432/isp_status_page"
REDIS_HOST=localhost
REDIS_PORT=6379
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
# Criar banco PostgreSQL
sudo -u postgres createuser isp_status --pwprompt
sudo -u postgres createdb isp_status_page --owner=isp_status

# Executar migrations e seeds
sudo bin/cake migrations migrate
sudo bin/cake migrations seed
```

### 4. Configure Permissoes

```bash
sudo chown -R www-data:www-data /var/www/isp_status_page
sudo chmod -R 755 /var/www/isp_status_page
sudo chmod -R 777 /var/www/isp_status_page/tmp
sudo chmod -R 777 /var/www/isp_status_page/logs
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

## Instalacao - Docker (Recomendado)

```bash
# Instalacao completa em um comando
make quick-start

# Ou manualmente
docker-compose up -d

# Acesse: http://localhost:8765
```

Veja `docs/DOCKER.md` para detalhes completos sobre a configuracao Docker com 3 containers (app, postgres, redis).

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

**Solucao**:
```bash
# Verifique se o PostgreSQL esta rodando
sudo systemctl status postgresql

# Verifique a conexao
psql -h localhost -U isp_status -d isp_status_page -c "SELECT 1;"

# Verifique a variavel DATABASE_URL no .env
grep DATABASE_URL .env

# Verifique se o Redis esta rodando
redis-cli ping
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

**Solucoes**:
```bash
# Limpar cache
bin/cake cache clear_all

# Limpar cache Redis
redis-cli FLUSHALL

# Otimizar banco PostgreSQL
psql -U isp_status -d isp_status_page -c "VACUUM ANALYZE;"

# Verificar tamanho do banco
psql -U isp_status -d isp_status_page -c "SELECT pg_size_pretty(pg_database_size('isp_status_page'));"

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
# Backup completo (PostgreSQL + config + logs)
pg_dump -h localhost -U isp_status isp_status_page > backups/database-$(date +%Y%m%d).sql
tar -czf backup-$(date +%Y%m%d).tar.gz backups/database-$(date +%Y%m%d).sql config/ logs/

# Apenas banco
pg_dump -h localhost -U isp_status isp_status_page > backups/database-$(date +%Y%m%d).sql
```

### Restore

```bash
# Restaurar banco
psql -h localhost -U isp_status isp_status_page < backups/database-20241031.sql
```

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

### Metricas

```bash
# Tamanho do banco
psql -U isp_status -d isp_status_page -c "SELECT pg_size_pretty(pg_database_size('isp_status_page'));"

# Numero de monitores ativos
psql -U isp_status -d isp_status_page -c "SELECT COUNT(*) FROM monitors WHERE active=true;"

# Ultimas verificacoes
psql -U isp_status -d isp_status_page -c "SELECT * FROM monitor_checks ORDER BY created DESC LIMIT 10;"
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
