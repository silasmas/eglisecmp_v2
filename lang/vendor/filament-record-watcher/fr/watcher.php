<?php

return [

    'pages' => [
        'my_watches' => 'Mes suivis',
    ],

    'notification' => [
        'title' => ':label a été modifié',
    ],

    'actions' => [
        'watch' => 'Suivre',
        'edit_watch' => 'Modifier le suivi',
        'watch_heading' => 'Suivre cet enregistrement',
        'watch_description' => 'Vous recevrez une notification dans le panneau à chaque modification. Ajoutez des règles optionnelles pour filtrer les cas où vous êtes averti.',
        'watch_success' => 'Vous suivez cet enregistrement.',

        'unwatch' => 'Ne plus suivre',
        'unwatch_heading' => 'Arrêter le suivi',
        'unwatch_description' => 'Vous ne recevrez plus de notifications pour les changements sur cet enregistrement.',
        'unwatch_success' => 'Suivi désactivé.',

        'conditions' => 'Conditions',
        'conditions_help' => 'Toutes les règles doivent correspondre. Laissez vide pour être notifié à tout changement.',
        'field' => 'Champ',
        'operator' => 'Opérateur',
        'value' => 'Valeur',
        'value_help' => 'Laissez vide avec l’opérateur « modifié ».',
        'add_rule' => 'Ajouter une règle',

        'pause' => 'Pause',
        'resume' => 'Reprendre',

        'history' => 'Historique',
        'history_heading' => 'Historique des changements',
        'close' => 'Fermer',
    ],

    'history' => [
        'empty' => 'Aucun changement enregistré pour ce suivi pour le moment.',
        'system' => 'Système',
    ],

    'table' => [
        'type' => 'Type',
        'record' => 'Enregistrement',
        'conditions' => 'Conditions',
        'any_change' => 'Tout changement',
        'rule_count' => '{1} 1 règle|[2,*] :count règles',
        'events' => 'Événements',
        'paused' => 'En pause',
        'since' => 'Suivi depuis',
    ],

];
