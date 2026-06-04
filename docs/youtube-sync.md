# YouTube — configuration et synchronisation

## 1. Variables `.env`

Une seule paire active (supprimez la ligne `ta_clef_serveur` si elle existe encore) :

```env
YOUTUBE_API_KEY=votre_cle_google_cloud
YOUTUBE_CHANNEL_ID=UCxxxxxxxxxxxxxxxx
```

Puis :

```bash
php artisan config:clear
```

## 2. Vérifier le live (déjà branché sur le site)

```bash
php artisan youtube:test-live
```

- Si un culte est **en direct** sur YouTube → titre + URL affichés.
- Sinon → message « Aucun live actif » (normal).

Sur le site : popup accueil, badge **Live YouTube** au-dessus du menu flottant, tuile hero **Live**.

Test API navigateur (optionnel) :

`GET /api/site/youtube/live`

## 3. Synchroniser les enseignements (vidéos, shorts, playlists)

### Simulation (sans écrire en base)

```bash
php artisan youtube:sync --dry-run
```

### Import réel

```bash
php artisan youtube:sync
```

Ou dans l’admin Filament : **Contenu → Publications → Synchroniser YouTube**.

### Ce qui est importé

| Source YouTube | Table | Usage site |
|----------------|-------|------------|
| Vidéos de la chaîne | `posts` | Onglet **Messages** (type vidéo) |
| Shorts (≤ 60 s ou #short) | `posts` (`youtube_kind=short`) | Idem |
| Playlists | `events` + lien `posts.event_id` | Onglet **Playlists** |
| Live en cours | Pas d’import ; détection temps réel | Hero + popup |

Champs ajoutés : `youtube_video_id`, `youtube_kind`, `youtube_playlist_id`, `youtube_synced_at` sur `posts` ; `youtube_playlist_id` sur `events`.

### Planification automatique

La commande `youtube:sync` est planifiée **toutes les heures** (`routes/console.php`). En production, configurez le cron Laravel :

```bash
* * * * * cd /chemin/eglisecmp_v2 && php artisan schedule:run >> /dev/null 2>&1
```

## 4. Google Cloud — prérequis API

1. Projet Google Cloud
2. Activer **YouTube Data API v3**
3. Créer une **clé API** (restreindre par IP ou référent en prod)
4. Quota : ~1 unité par vidéo lue ; une sync complète ≈ quelques centaines d’unités

## 5. Après la première sync

1. Filament → **Publications** : vérifier titres, vignettes, dates.
2. Site → **Enseignements** : onglets Messages et Playlists.
3. Ajuster à la main : prédicateur, jour de culte, mise en avant accueil (non gérés par YouTube).

## 6. Onglet Méditations (cultes hebdomadaires)

Les playlists dont le titre contient les mots-clés définis dans `config/site_public.php` → `youtube_meditation_playlist_groups` apparaissent dans **Enseignements → Méditations** :

- Culte d'enseignement (mercredi)
- Culte de jeudi etoko (jeudi)
- Cultes dominicaux (dimanche)

Les autres playlists vont dans **Enseignements → Playlists**.

## 7. Synchronisation automatique

- Commande : `php artisan youtube:sync` (planifiée **toutes les 30 minutes** via le scheduler Laravel).
- Aucun clic manuel requis en production si le cron `schedule:run` est actif.
- Le bouton Filament reste utile pour forcer une sync immédiate après un nouveau culte.

### Logique (résumé)

1. **Playlists** : l’API liste les playlists de la chaîne → création/mise à jour d’un **événement** par playlist (titre, description, miniature).
2. **Vidéos** : l’API lit la playlist « uploads » → pour chaque vidéo : titre, **description complète**, miniature HD, durée, lien, date → **publication** (`posts`).
3. **Liens** : chaque vidéo d’une playlist reçoit `event_id` pour l’onglet Playlists ; les cultes hebdomadaires reçoivent aussi `weekly_service_day`.
4. **Affichage** : la SPA lit la base via `/api/site/posts` et `/api/site/teachings/*` — pas d’appel YouTube côté navigateur (économie de quota et rapidité).

## 8. Limites connues

- Les **audios** (type 2) ne viennent pas de YouTube ; ils restent saisis à la main.
- Une vidéo retirée de YouTube n’est **pas désactivée** automatiquement (éviter de masquer du contenu archivé).
- L’ID chaîne doit être `UC…`, pas `@NomDeChaîne`.
