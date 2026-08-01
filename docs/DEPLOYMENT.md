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

## 6. Deploiement Render avec Docker

Le backend contient un `Dockerfile` pret pour Render. Creer un nouveau **Web Service** depuis le depot GitHub du backend, choisir l'environnement **Docker**, puis definir ces variables:

```text
APP_NAME=ISC KINDU
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...
APP_URL=https://votre-backend.onrender.com
DB_CONNECTION=mysql
DB_HOST=...
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public
LOG_CHANNEL=stderr
RUN_MIGRATIONS=true
CORS_ALLOWED_ORIGINS=https://isc-kindu-frontend.baruanipascal0.workers.dev
```

Si vous utilisez la base PostgreSQL de Render, remplacer les lignes `DB_*` par:

```text
DB_CONNECTION=pgsql
DB_URL=postgresql://...
DB_SSLMODE=require
```

Render fournit automatiquement la variable `PORT`; le script `scripts/render-start.sh` configure Apache pour l'utiliser.

Apres le premier deploiement, verifier:

```text
/up
/api/site/settings
/api/news
/admin/login
```

Les fichiers envoyes depuis l'administration sont stockes dans `storage/app/public`. Pour une production durable, utiliser un disque persistant Render ou un stockage externe, sinon ces fichiers peuvent disparaitre lors d'un redeploiement selon le type d'offre.
