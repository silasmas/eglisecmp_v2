# Déploiement — site public + admin

## Après `git pull` sur le serveur

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

Si `public/build` n’est pas versionné (ancienne config), compiler le front :

```bash
npm ci
npm run build
```

## Fichiers à ne jamais laisser en production

- **`public/hot`** — s’il existe, Laravel charge Vite sur le port 5173 → **page blanche** si Vite n’est pas lancé. Supprimer : `rm -f public/hot`

## Migrations récentes (mai 2026)

- `minister_reception_schedules` — créneaux pasteurs (rendez-vous)
- champs RDV sur `site_inquiries`

Optionnel :

```bash
php artisan db:seed --class=MinisterReceptionScheduleSeeder --force
```

## Vérification

- Accueil : `/` (HTML doit référencer `/build/assets/main-*.js`, pas `localhost:5173`)
- API : `GET /api/site/hero-meta`
- Offrandes : `/offrandes`
