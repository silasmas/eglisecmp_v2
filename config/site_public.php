<?php

declare(strict_types=1);

/**
 * Paramètres pour l'API JSON consommée par le site public (SPA React).
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Libellés des types de publication (posts.type)
    |--------------------------------------------------------------------------
    |
    | Clé = valeur entière stockée en base. Utilisé côté front pour la pastille
    | « catégorie » des enseignements. Complétez selon votre convention métier.
    |
    */
    'post_type_labels' => [
        0 => 'Publication',
        1 => 'Message vidéo',
        2 => 'Méditation',
        3 => 'Enseignement',
    ],

    /*
    |--------------------------------------------------------------------------
    | Filtres onglets page Enseignements (SPA)
    |--------------------------------------------------------------------------
    |
    | Correspond aux types stockés dans posts.type (Filament : 1=Video, 2=Audio, 3=Article).
    |
    */
    'teachings_tabs' => [
        'sermons' => [1, 3],
        'meditations' => [2],
    ],

    /*
    |--------------------------------------------------------------------------
    | Image de secours
    |--------------------------------------------------------------------------
    |
    | URL utilisée lorsqu'aucune vignette n'est disponible pour un post ou un événement.
    |
    */
    'placeholder_image_url' => 'https://images.unsplash.com/photo-1507692049790-de58290a4334?w=600&h=400&fit=crop',

    /*
    |--------------------------------------------------------------------------
    | Orateur par défaut (messages YouTube sans pasteur lié)
    |--------------------------------------------------------------------------
    */
    'default_speaker_name' => 'Centre Missionnaire Philadelphie',

    /*
    |--------------------------------------------------------------------------
    | Tuiles hero (modales) — lieu
    |--------------------------------------------------------------------------
    |
    | Texte et image de la carte « Nous trouver ». Le résumé apparaît sous le
    | titre sur le bandeau ; la description complète est affichée dans la modale.
    |
    */
    /*
    |--------------------------------------------------------------------------
    | Vignettes par défaut — programmes hebdomadaires (Mercredi, Jeudi, Dimanche)
    |--------------------------------------------------------------------------
    |
    | Jour 0 = dimanche, 3 = mercredi, 4 = jeudi (convention Carbon).
    | Remplacées par les images du dashboard si renseignées sur le programme.
    |
    */
    'weekly_program_default_banners' => [
        0 => '/images/programs/dimanche.jpg',
        3 => '/images/programs/mercredi.jpg',
        4 => '/images/programs/jeudi.jpg',
    ],

    'hero_strip' => [
        'location' => [
            'title' => 'Nous trouver',
            'summary' => '4524, avenue des Forces Armées… · Kinshasa / Gombe',
            'description' => "Centre Missionnaire Philadelphie\n4524, Avenue des Forces armées (ex Haut-Commandement), Kinshasa / Gombe\nB.P. 14 Kinshasa 2\nTél. (+243) 81 046 68 83 - 081 783 64 11\ninfo@cm-philadelphie.org",
            'banner_image' => 'https://images.unsplash.com/photo-1438232992991-995b7058bbb3?w=1200&h=600&fit=crop',
            'maps_url' => 'https://www.google.com/maps/search/?api=1&query=Centre+Missionnaire+Philadelphie,+4524+Avenue+des+Forces+Arm%C3%A9es,+Kinshasa,+Gombe',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Réactions SPA (clé technique => libellé affiché)
    |--------------------------------------------------------------------------
    */
    'reaction_keys' => [
        'amen' => 'Amen',
        'pray' => 'Prière',
        'heart' => 'Merci',
        'hallelujah' => 'Alléluia',
    ],

    /*
    |--------------------------------------------------------------------------
    | Réactions mur de témoignages
    |--------------------------------------------------------------------------
    */
    'testimony_reaction_keys' => [
        'amen' => 'Amen',
        'gloire' => 'Gloire à Dieu',
        'beni' => 'Béni soit Dieu',
        'aime' => 'Aimé',
    ],

    /*
    |--------------------------------------------------------------------------
    | Icônes des programmes (clé Lucide => libellé admin)
    |--------------------------------------------------------------------------
    |
    | La clé est stockée en base (`icon_key`) et mappée côté SPA (lucide-react).
    |
    */
    'program_icons' => [
        'book-open' => 'Livre / Bible',
        'heart-handshake' => 'Fraternité / entraide',
        'users' => 'Communauté / groupe',
        'church' => 'Église / culte',
        'calendar-days' => 'Calendrier / rendez-vous',
        'radio' => 'Live / diffusion',
        'play' => 'Vidéo / message',
        'map-pin' => 'Lieu / localisation',
        'sparkles' => 'Événement spécial',
        'sunrise' => 'Matin / prière matinale',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rôles notifiés lors d’une requête de prière (Spatie Shield)
    |--------------------------------------------------------------------------
    |
    | Noms des rôles recevant un courriel et une notification Filament.
    | « intercession » et « priere » (ou « Priere ») couvrent les deux équipes notifiables.
    |
    */
    'prayer_notification_roles' => [
        'intercession',
        'Intercession',
        'priere',
        'Priere',
    ],

    /*
    |--------------------------------------------------------------------------
    | Courriels fixes de l’équipe de prière (fallback)
    |--------------------------------------------------------------------------
    |
    | Liste d’adresses séparées par des virgules dans PRAYER_TEAM_EMAILS (.env).
    | Utilisée si aucun compte utilisateur intercession n’a d’e-mail.
    |
    */
    'prayer_team_emails' => array_values(array_filter(array_map(
        static fn (string $email): string => trim($email),
        explode(',', (string) env('PRAYER_TEAM_EMAILS', '')),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Courriels transactionnels — logo public
    |--------------------------------------------------------------------------
    |
    | URL absolue du logo (fallback si l’intégration CID échoue). Par défaut :
    | {APP_URL}/images/logo-cmp.png
    |
    */
    'mail_logo_url' => env('MAIL_LOGO_URL'),

    /*
    |--------------------------------------------------------------------------
    | Mur de témoignages (SPA)
    |--------------------------------------------------------------------------
    */
    'testimony_wall' => [
        'categories' => [
            'Tous',
            'Vidéos',
            'Guérison',
            'Provision',
            'Famille',
            'Délivrance',
            'Éducation',
            'Protection',
            'Autre',
        ],
        'postItColors' => [
            ['name' => 'Jaune doux', 'value' => '#FFF6D9', 'border' => '#F5D693'],
            ['name' => 'Rose clair', 'value' => '#FFE5E5', 'border' => '#FFD6DC'],
            ['name' => 'Vert menthe', 'value' => '#E4FFEB', 'border' => '#B8E6C3'],
            ['name' => 'Lavande', 'value' => '#F3E5F5', 'border' => '#E1BEE7'],
            ['name' => 'Pêche', 'value' => '#FFE0B2', 'border' => '#FFCC80'],
            ['name' => 'Bleu ciel', 'value' => '#E3F2FD', 'border' => '#BBDEFB'],
        ],
        'fontStyles' => [
            ['name' => 'Sans-serif', 'value' => 'Inter, sans-serif'],
            ['name' => 'Serif', 'value' => 'Merriweather, serif'],
            ['name' => 'Indie Flower', 'value' => 'Indie Flower, cursive'],
            ['name' => 'Caveat', 'value' => 'Caveat, cursive'],
            ['name' => 'Patrick Hand', 'value' => 'Patrick Hand, cursive'],
        ],
        'maxTitleLength' => 50,
        'maxTextLength' => 500,
        'maxVideoUploadMb' => 5,
        'perPage' => 12,
    ],

    /*
    |--------------------------------------------------------------------------
    | Chaîne YouTube (détection live API Data v3)
    |--------------------------------------------------------------------------
    |
    | YOUTUBE_CHANNEL_ID : identifiant chaîne (UC…), pas l’URL @handle.
    | YOUTUBE_API_KEY : clé API Google Cloud (YouTube Data API v3 activée).
    |
    */
    'youtube_channel_id' => env('YOUTUBE_CHANNEL_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | Synchronisation chaîne → publications (enseignements)
    |--------------------------------------------------------------------------
    */
    'youtube_sync' => [
        'default_locale' => 'fr',
        'max_videos_per_run' => 500,
        'max_playlist_videos_per_run' => 120,
        'import_shorts' => true,
        /** Arrêt après N vidéos déjà en base (uploads / playlists modifiées). */
        'incremental_stop_after_existing' => (int) env('YOUTUBE_SYNC_STOP_AFTER_EXISTING', 8),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification démarrage live (email + SMS)
    |--------------------------------------------------------------------------
    |
    | Les abonnés YouTube natifs ne sont pas joignables via l’API : seuls les
    | comptes du site (users) et les contacts témoignages sont notifiés.
    |
    */
    'youtube_live_notify' => [
        'enabled' => (bool) env('YOUTUBE_LIVE_NOTIFY_ENABLED', false),
        'site_url' => env('APP_URL', 'https://cmp-philadelphie.org'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Playlists YouTube → onglet Méditations (cultes hebdomadaires)
    |--------------------------------------------------------------------------
    |
    | `match` : fragments présents dans le titre de la playlist YouTube (insensible à la casse).
    |
    */
    'youtube_meditation_playlist_groups' => [
        [
            'label' => 'Culte d\'enseignement',
            'match' => ['culte d\'enseignement', 'culte de mercredi'],
        ],
        [
            'label' => 'Culte de jeudi etoko',
            'match' => ['jeudi etoko', 'culte de jeudi', 'culte jeudi'],
        ],
        [
            'label' => 'Cultes dominicaux',
            'match' => ['cultes dominicaux', 'culte dominical', 'culte du dimanche'],
        ],
    ],

];
