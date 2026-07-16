# Deploy e operação

## Checklist produção

1. Servidor PHP-FPM / Nginx apontando para `public/`  
2. SSL (HTTPS) — obrigatório para webhook Meta  
3. `.env` com `APP_DEBUG=false`, `APP_ENV=production`  
4. `php artisan migrate --force`  
5. `php artisan config:cache && php artisan route:cache && php artisan view:cache`  
6. `npm run build` e assets versionados via Vite manifest  
7. `php artisan storage:link`  
8. Worker de fila (Supervisor):

```ini
[program:mgi-queue]
command=php /var/www/chatboot/artisan queue:work --sleep=1 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
```

9. (Opcional) Reverb:

```bash
php artisan reverb:start
```

10. Backup diário do MySQL  
11. Trocar senha do `admin@chatflow.com`  
12. Configurar Meta webhook para a URL pública  

## Monitoramento sugerido
- Tamanho da tabela `jobs` / `failed_jobs`  
- Logs `storage/logs/laravel.log`  
- Taxa de `messages.status = failed`  
- Conversas `waiting` com `waiting_since` antigo (SLA)  

## Atualização
```bash
git pull
composer install --no-dev -o
npm ci && npm run build
php artisan migrate --force
php artisan optimize
# reinicie queue workers
```
