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
    | Tuiles hero (modales) — lieu
    |--------------------------------------------------------------------------
    |
    | Texte et image de la carte « Nous trouver ». Le résumé apparaît sous le
    | titre sur le bandeau ; la description complète est affichée dans la modale.
    |
    */
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
    | « Priere » correspond au rôle historique en base ; « intercession » est
    | accepté comme alias métier.
    |
    */
    'prayer_notification_roles' => [
        'intercession',
        'Intercession',
        'Priere',
    ],

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

];
