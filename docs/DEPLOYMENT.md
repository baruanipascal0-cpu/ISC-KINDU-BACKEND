# Mise en ligne ISC KINDU

## 1. Base MySQL

Creer une base MySQL chez l hebergeur, par exemple:

```text
DB_DATABASE=isc_kindu
DB_USERNAME=isc_kindu_user
DB_PASSWORD=mot_de_passe_fort
```

Copier `.env.production.example` vers `.env`, remplir `APP_URL`, `FRONTEND_PATH` et les identifiants MySQL.

## 2. Commandes serveur

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 3. Dossiers a sauvegarder

```text
Base MySQL
storage/app/public
.env
```

## 4. Sauvegarde

Sur Windows, utiliser `scripts/backup-mysql.ps1`. Sur Linux, programmer un `mysqldump` quotidien et une copie du dossier `storage/app/public`.

## 5. Verification

Verifier ces URLs apres mise en ligne:

```text
/
/actualites.html
/articles.html
/inscriptions.html
/admin/login
/api/site/settings
```
