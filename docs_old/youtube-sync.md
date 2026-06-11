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

Par défaut la synchro est **incrémentale** : elle mémorise le nombre de vidéos par playlist et ne parcourt que les uploads / playlists modifiés. Si rien de nouveau :

> Aucun nouveau contenu YouTube (dernière synchro : …).

Forcer un scan complet (comme avant) :

```bash
php artisan youtube:sync --full
```

Ou dans l’admin Filament : **Contenu → Publications → Synchroniser YouTube**.

### Éviter le 504 Gateway Time-out (admin)

La synchro complète dure **10 à 20 minutes** (100+ playlists). Si elle tourne dans la requête HTTP, **nginx** coupe la connexion → page **504**.

Le bouton admin lance désormais la synchro **en arrière-plan** :

- avec `QUEUE_CONNECTION=database` → job `SyncYoutubeChannelJob` (lancer un worker : `php artisan queue:work --timeout=3600`)
- sinon → processus `nohup php artisan youtube:sync` (log : `storage/logs/youtube-sync.log`)

En production, préférez :

```env
QUEUE_CONNECTION=database
```

### Ce qui est importé

| Source YouTube | Table | Usage site |
|----------------|-------|------------|
| Vidéos de la chaîne | `posts` | Onglet **Messages** (type vidéo) |
| Shorts (≤ 60 s ou #short) | `posts` (`youtube_kind=short`) | Idem |
| Playlists | `events` + lien `posts.event_id` | Onglet **Playlists** |
| Live en cours | Pas d’import ; détection temps réel | Hero + popup |

Champs ajoutés : `youtube_video_id`, `youtube_kind`, `youtube_playlist_id`, `youtube_synced_at` sur `posts` ; `youtube_playlist_id` sur `events`.

### Planification automatique

- `youtube:sync` — toutes les **30 minutes** (incrémental)
- `youtube:check-live` — toutes les **3 minutes** (notifications live, si activé)

En production, configurez le cron Laravel :

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
2. **Vidéos** : depuis la playlist « uploads », parcours du **plus récent vers l’ancien** ; arrêt après 8 vidéos déjà en base (configurable). Les playlists dont le **nombre d’éléments** n’a pas changé sont ignorées.
3. **Liens** : chaque vidéo d’une playlist modifiée reçoit `event_id` pour l’onglet Playlists ; les cultes hebdomadaires reçoivent aussi `weekly_service_day`.
4. **État** : cache `youtube.channel.sync.state` mémorise la dernière synchro et les compteurs par playlist.
5. **Affichage** : la SPA lit la base via `/api/site/posts` et `/api/site/teachings/*` — pas d’appel YouTube côté navigateur (économie de quota et rapidité).

## 8. Live sur le site + notifications

### Affichage (sans import en base)

- Service `YoutubeLiveStatusService` : appel API `search?eventType=live` sur la chaîne, résultat mis en **cache 90 s**.
- API : `GET /api/site/youtube/live` + fusion dans le hero (`PublicHeroMetaController`).
- Front : `YoutubeLiveProvider` interroge l’API toutes les ~90 s → badge menu, modale, tuile hero.

### Notifications email / SMS (opt-in uniquement)

```env
YOUTUBE_LIVE_NOTIFY_ENABLED=true
```

Commandes planifiées :

- `youtube:check-live` (toutes les 3 min) → abonnés **live**
- `events:check-alerts` (toutes les 5 min) → abonnés **événements** (en cours, à la une, rappel 24 h)

Les destinataires sont enregistrés dans la table `alert_subscriptions` (cases cochées volontairement) :

- Formulaire **footer**, page **Événements**, modale **live**, modale **détail événement**
- Cases à cocher lors du dépôt d’un **témoignage** (+ téléphone optionnel pour SMS)

Désabonnement : lien dans chaque e-mail → `/alertes/desabonnement?token=…`

**Important** : l’API YouTube ne permet pas d’envoyer des mails aux abonnés de la chaîne. Seuls les inscrits sur le site sont notifiés.

## 9. Limites connues

- Les **audios** (type 2) ne viennent pas de YouTube ; ils restent saisis à la main.
- Une vidéo retirée de YouTube n’est **pas désactivée** automatiquement (éviter de masquer du contenu archivé).
- L’ID chaîne doit être `UC…`, pas `@NomDeChaîne`.
