# Site public — SPA React et API

Ce document décrit comment le front du site vitrine est intégré dans Laravel, comment il communique avec le backend, et comment faire évoluer le tout sans casser l’admin Filament.

## Vue d’ensemble

- **Interface publique** : une application **React** (TypeScript) servie par **Vite**, montée sur une vue Blade unique `resources/views/site.blade.php`. Le routage des pages (`/`, `/events`, `/teachings`, etc.) est géré côté client par **React Router**.
- **Administration** : **Filament** reste sur le chemin `/admin` et n’est pas concerné par la SPA.
- **Données dynamiques** : la SPA appelle des endpoints **JSON** sous le préfixe **`/api/site`** (fichier `routes/api.php`).

## Arborescence utile

| Élément | Rôle |
|--------|------|
| `resources/js/site/` | Code source de la SPA (pages, composants, styles, hooks). |
| `resources/js/site/main.tsx` | Point d’entrée Vite ; monte React sur `#root`. |
| `resources/js/site/styles/index.css` | Thème Tailwind v4 du site (`@source` inclut les `.tsx`). |
| `resources/views/site.blade.php` | Document HTML minimal ; charge `@vite(['resources/js/site/main.tsx'])`. |
| `routes/web.php` | Route racine `/` et route catch-all `/{path}` avec **exclusion** des chemins système (`admin`, `api`, `livewire`, `build`, etc.). |
| `routes/api.php` | Routes `GET /api/site/*` (événements, posts, galeries, programmes, verset, à la une, meta hero). |
| `app/Http/Controllers/Api/Site/` | Contrôleurs publics en lecture seule. |
| `app/Support/SitePublicSerializer.php` | Normalisation des champs multilingues et forme JSON attendue par le front. |
| `config/site_public.php` | Libellés des types de posts, image de secours, etc. |

## Cycle de développement

1. **Variables d’environnement** (fichier `.env`, voir `.env.example`) :
   - `VITE_SITE_API_BASE` : optionnel ; par défaut la SPA utilise `/api/site`.
   - `VITE_YOUTUBE_API_KEY` : optionnel ; active les appels directs à l’API YouTube depuis le navigateur pour la section enseignements.
2. **Installation des dépendances front** : à la racine du projet Laravel, `npm install`.
3. **Serveur de dev** : lancer **deux processus** (ou `composer run dev` si vous l’avez configuré ainsi) :
   - `php artisan serve` (ou votre stack habituelle),
   - `npm run dev` pour Vite (HMR sur les fichiers `resources/js/site`).
4. **Build de production** : `npm run build` génère le manifeste dans `public/build` ; la même commande compile l’entrée `resources/js/site/main.tsx`.

## API JSON (`/api/site`)

Les listes renvoient en général `{ "data": [ ... ] }`. Les endpoints `verse-of-day` et `hero-meta` peuvent renvoyer `data: null` ou un objet imbriqué (voir ci-dessous).

| Méthode et URL | Description |
|----------------|-------------|
| `GET /api/site/events?limit=20&locale=fr` | Événements actifs (`is_active`), triés avec mise en avant en premier. |
| `GET /api/site/posts?tab=sermons&page=1&per_page=12` | Publications paginées pour la page Enseignements : `tab` = `sermons` (vidéo + article), `meditations` (audio), `playlists` (liées à un événement). Réponse `{ data, meta: { has_more, total, … } }`. |
| `GET /api/site/posts?limit=36&locale=fr` | Liste simple (legacy / accueil) ; mêmes champs sermon + `theme`, `eventId`, `linkUrl` selon le type. |
| `GET /api/site/galleries?limit=48&locale=fr` | Entrées de galerie actives. |
| `GET /api/site/programs?kind=live&locale=fr` | Programmes d’antenne (`schedule_programs`) ; filtre optionnel `kind` : `daily`, `weekly`, `seminar`, `live`, `special`. |
| `GET /api/site/verse-of-day?locale=fr` | Verset du jour courant (`daily_verses`, fenêtre 24 h). |
| `GET /api/site/featured-posts?limit=6&locale=fr` | Posts mis en avant sur l’accueil (`featured_on_home` + fenêtre de dates optionnelle). |
| `GET /api/site/hero-meta?locale=fr` | `{ "data": { "verse": … ou null, "liveSlots": [ … ] } }` pour le bandeau du hero. |

Les réponses exposent des **URL d’images absolues** (`APP_URL` + `/storage/...` pour les fichiers Filament sur le disque `public`, ou `https://i.ytimg.com/...` si seule une vidéo YouTube est renseignée sans vignette).

## Administration Filament (site public)

- **Programmes (site)** : menu **Site public → Programmes (site)** ; modèle `ScheduleProgram`. Les entrées `kind = live` avec `weekday`, `live_hour`, `live_minute` alimentent le décompte « Prochain live » sur l’accueil (via `hero-meta`). Les autres types remplissent la section « Nos rendez-vous ».
- **Versets du jour** : **Site public → Versets du jour** ; modèle `DailyVerse`. La date **Publication** ouvre une fenêtre de visibilité de **24 h** (champ `visible_until` recalculé à l’enregistrement).
- **Posts à la une** : dans **Contenu → Publications**, section **Mise en avant accueil** (booléen, dates optionnelles, ordre).

Après déploiement, exécutez la génération des permissions Filament Shield pour les nouveaux modèles, par exemple :  
`php artisan shield:generate --all` (ou équivalent selon votre configuration), puis attribuez les droits `ScheduleProgram` et `DailyVerse` aux rôles concernés.

## Comportement côté React

Les hooks sous `resources/js/site/hooks/` consomment l’API : `useSiteEvents`, `useSiteSermons`, `useSiteGallery`, `useSitePrograms`, `useHeroMeta`, `useFeaturedPosts`. **Messages récents** et la page **Enseignements** utilisent **uniquement** la table `posts` via l’API (`useSiteSermons(..., false)` : pas de repli sur le mock). Les sections **Programmes** et **Événements** conservent un repli sur `data/content.ts` si l’API est vide.

## Mise à jour du contenu statique

Les textes marketing qui ne viennent pas encore de la base (sections « À propos », navigation, etc.) restent dans `resources/js/site/data/`. Vous pouvez les migrer progressivement vers des endpoints API ou un CMS selon vos priorités.

## Synchronisation avec le dépôt `eglisecmp-site-refont`

Le front a été copié depuis le projet Vite autonome ; pour réimporter une évolution majeure du refont, comparez les dossiers `src/` côté refont et `resources/js/site/` côté Laravel, puis fusionnez en conservant les hooks et `lib/siteApi.ts` propres à cette intégration.

## Sécurité et bonnes pratiques

- Les routes `api/site/*` sont **en lecture seule** ; pour des formulaires (contact, dons), prévoir des routes dédiées avec validation, **CSRF** (formulaires Blade ou token Sanctum) et limitation de débit.
- La clé **YouTube** exposée en `VITE_*` est **visible dans le navigateur** ; limitez la clé par référent / quotas dans la console Google Cloud.

## Dépannage rapide

- **Page blanche** : vérifier la console navigateur et que `npm run dev` tourne ; contrôler que `@vite` pointe bien vers `resources/js/site/main.tsx`.
- **404 sur une URL profonde** (ex. `/events` après rechargement) : le serveur web doit rediriger toutes les URLs « front » vers `public/index.php` (déjà le cas avec `php artisan serve` et une config Apache/Nginx standard Laravel).
- **Filament inaccessible** : vérifier que l’URL commence bien par `/admin` ; la contrainte sur `path` dans `web.php` exclut explicitement ce préfixe.
