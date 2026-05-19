<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$targets = [
    $root.'/vendor/flexpik/filament-studio/src/Resources/CollectionManagerResource.php' => [
        [
            'old' => "protected static ?string \$navigationLabel = 'Data Models';",
            'new' => "protected static ?string \$navigationLabel = 'Modeles de donnees';",
        ],
        [
            'old' => "protected static ?string \$modelLabel = 'Data Model';",
            'new' => "protected static ?string \$modelLabel = 'Modele de donnees';",
        ],
        [
            'old' => "protected static ?string \$pluralModelLabel = 'Data Models';",
            'new' => "protected static ?string \$pluralModelLabel = 'Modeles de donnees';",
        ],
        [
            'old' => '            ->columns(3)',
            'new' => '            ->columns(12)',
        ],
        [
            'old' => '                    ->columnSpan(2)',
            'new' => '                    ->columnSpanFull()',
        ],
        [
            'old' => '                    ->columnSpan(1)',
            'new' => '                    ->columnSpanFull()',
        ],
        [
            'old' => "Section::make('Basic Info')",
            'new' => "Section::make('Informations principales')",
        ],
        [
            'old' => "Section::make('Behavior')",
            'new' => "Section::make('Comportement')",
        ],
        [
            'old' => "Section::make('Multilingual')",
            'new' => "Section::make('Multilingue')",
        ],
        [
            'old' => "Section::make('Display & Sorting')",
            'new' => "Section::make('Affichage et tri')",
        ],
        [
            'old' => '                            ->columns(2),',
            'new' => '                            ->columns(1),',
        ],
    ],
    $root.'/vendor/flexpik/filament-studio/src/Resources/CollectionManagerResource/RelationManagers/FieldsRelationManager.php' => [
        [
            'old' => "protected static ?string \$title = 'Fields';",
            'new' => "protected static ?string \$title = 'Champs';",
        ],
        [
            'old' => "Section::make('Field Identity')",
            'new' => "Section::make('Identite du champ')",
        ],
        [
            'old' => "Section::make('Behavior')",
            'new' => "Section::make('Comportement')",
        ],
        [
            'old' => "Section::make('Type-Specific Settings')",
            'new' => "Section::make('Parametres specifiques au type')",
        ],
        [
            'old' => "Tabs\\Tab::make('Visibility')",
            'new' => "Tabs\\Tab::make('Visibilite')",
        ],
        [
            'old' => "Tabs\\Tab::make('Required')",
            'new' => "Tabs\\Tab::make('Obligatoire')",
        ],
        [
            'old' => "Tabs\\Tab::make('Disabled')",
            'new' => "Tabs\\Tab::make('Desactive')",
        ],
    ],
    $root.'/vendor/flexpik/filament-studio/src/Resources/DashboardResource.php' => [
        [
            'old' => "protected static ?string \$navigationLabel = 'Dashboards';",
            'new' => "protected static ?string \$navigationLabel = 'Tableaux de bord';",
        ],
        [
            'old' => "->label('Auto-Refresh Interval (seconds)')",
            'new' => "->label('Intervalle de rafraichissement auto (secondes)')",
        ],
        [
            'old' => "->helperText('Leave empty to disable'),",
            'new' => "->helperText('Laisse vide pour desactiver'),",
        ],
    ],
    $root.'/vendor/flexpik/filament-studio/src/FieldTypes/Types/ImageFieldType.php' => [
        [
            'old' => 'use Filament\\Tables\\Columns\\ImageColumn;',
            'new' => 'use TinusG\\FilamentHoverImageColumn\\HoverImageColumn as ImageColumn;',
        ],
    ],
    $root.'/vendor/flexpik/filament-studio/src/FieldTypes/Types/AvatarFieldType.php' => [
        [
            'old' => 'use Filament\\Tables\\Columns\\ImageColumn;',
            'new' => 'use TinusG\\FilamentHoverImageColumn\\HoverImageColumn as ImageColumn;',
        ],
    ],
    $root.'/vendor/flexpik/filament-studio/src/Resources/CollectionManagerResource/Pages/CreateCollection.php' => [
        [
            'old' => "Wizard\\Step::make('Basic Info')",
            'new' => "Wizard\\Step::make('Informations principales')",
        ],
        [
            'old' => "->description('Name and describe your collection')",
            'new' => "->description('Nomme et decris ta collection')",
        ],
        [
            'old' => "->placeholder('e.g. blog_posts')",
            'new' => "->label('Nom')\n                        ->placeholder('ex: blog_posts')",
        ],
        [
            'old' => "->helperText('Unique identifier (snake_case). Auto-generates label and slug.'),",
            'new' => "->helperText('Identifiant unique (snake_case). Genere automatiquement le libelle et le slug.'),",
        ],
        [
            'old' => "->placeholder('e.g. Blog Post')",
            'new' => "->label('Libelle')\n                        ->placeholder('ex: Article')",
        ],
        [
            'old' => "->helperText('Singular display name shown in navigation and forms.'),",
            'new' => "->helperText('Nom au singulier affiche dans la navigation et les formulaires.'),",
        ],
        [
            'old' => "->placeholder('e.g. Blog Posts')",
            'new' => "->label('Libelle pluriel')\n                        ->placeholder('ex: Articles')",
        ],
        [
            'old' => "->helperText('Plural display name used for list pages and breadcrumbs.'),",
            'new' => "->helperText('Nom au pluriel utilise pour les listes et le fil d Ariane.'),",
        ],
        [
            'old' => "->helperText('Heroicon identifier for sidebar navigation. Browse icons at heroicons.com.'),",
            'new' => "->label('Icone')\n                        ->helperText('Identifiant Heroicon pour la navigation laterale. Consulte heroicons.com.'),",
        ],
        [
            'old' => "->placeholder('Describe what this collection is used for...')",
            'new' => "->label('Description')\n                        ->placeholder('Decris a quoi sert cette collection...')",
        ],
        [
            'old' => "->helperText('Internal description to help team members understand this collection\\'s purpose.')",
            'new' => "->helperText('Description interne pour aider l equipe a comprendre le but de cette collection.')",
        ],
        [
            'old' => "Wizard\\Step::make('System Fields')",
            'new' => "Wizard\\Step::make('Champs systeme')",
        ],
        [
            'old' => "->description('Choose which system fields to include')",
            'new' => "->description('Choisis les champs systeme a inclure')",
        ],
        [
            'old' => "'status' => 'Status — Draft/Published/Archived select field',",
            'new' => "'status' => 'Statut - champ select Brouillon/Publie/Archive',",
        ],
        [
            'old' => "'sort_order' => 'Sort Order — Enables drag-and-drop reordering',",
            'new' => "'sort_order' => 'Ordre de tri - active la reorganisation par glisser-deposer',",
        ],
        [
            'old' => "'created_by' => 'Created By — Auto-tracks who created each record',",
            'new' => "'created_by' => 'Cree par - suit automatiquement l auteur de creation',",
        ],
        [
            'old' => "'updated_by' => 'Updated By — Auto-tracks who last updated each record',",
            'new' => "'updated_by' => 'Mis a jour par - suit automatiquement le dernier editeur',",
        ],
        [
            'old' => "'timestamps' => 'Timestamps — Created At and Updated At fields',",
            'new' => "'timestamps' => 'Horodatage - ajoute Cree le et Mis a jour le',",
        ],
        [
            'old' => "->helperText('System fields are auto-populated and cannot be renamed. You can add more fields later.')",
            'new' => "->helperText('Les champs systeme sont auto-remplis et ne peuvent pas etre renommes. Tu pourras ajouter d autres champs ensuite.')",
        ],
        [
            'old' => "Wizard\\Step::make('Settings')",
            'new' => "Wizard\\Step::make('Parametres')",
        ],
        [
            'old' => "->description('Configure collection behavior')",
            'new' => "->description('Configure le comportement de la collection')",
        ],
        [
            'old' => "->helperText('Limit to a single record (e.g. site settings).'),",
            'new' => "->helperText('Limite la collection a un seul enregistrement (ex: parametres du site).'),",
        ],
        [
            'old' => "->label('Enable Versioning')",
            'new' => "->label('Activer le versionnage')",
        ],
        [
            'old' => "->helperText('Keep a snapshot history of every record update.'),",
            'new' => "->helperText('Conserve un historique des snapshots a chaque mise a jour.'),",
        ],
        [
            'old' => "->label('Enable Soft Deletes')",
            'new' => "->label('Activer la corbeille (soft delete)')",
        ],
        [
            'old' => "->helperText('Move records to trash instead of permanent deletion.'),",
            'new' => "->helperText('Deplace les enregistrements en corbeille au lieu d une suppression definitive.'),",
        ],
        [
            'old' => "'created_at' => 'Created At',",
            'new' => "'created_at' => 'Cree le',",
        ],
        [
            'old' => "'updated_at' => 'Updated At',",
            'new' => "'updated_at' => 'Mis a jour le',",
        ],
        [
            'old' => "'sort_order' => 'Sort Order',",
            'new' => "'sort_order' => 'Ordre de tri',",
        ],
        [
            'old' => "->placeholder('Default (created_at)')",
            'new' => "->placeholder('Par defaut (created_at)')",
        ],
        [
            'old' => "->helperText('Default sort field for record listing.'),",
            'new' => "->helperText('Champ de tri par defaut pour la liste des enregistrements.'),",
        ],
        [
            'old' => "'asc' => 'Ascending',",
            'new' => "'asc' => 'Croissant',",
        ],
        [
            'old' => "'desc' => 'Descending',",
            'new' => "'desc' => 'Decroissant',",
        ],
        [
            'old' => "->helperText('Default ordering direction for the record listing.'),",
            'new' => "->helperText('Direction de tri par defaut pour la liste des enregistrements.'),",
        ],
        [
            'old' => "->helperText('Handlebars template for relationship dropdowns.')",
            'new' => "->helperText('Template Handlebars pour l affichage des relations.')",
        ],
        [
            'old' => "->label('Enable Multilingual')",
            'new' => "->label('Activer le multilingue')",
        ],
        [
            'old' => "->helperText('Allow translatable fields to store values in multiple locales.')",
            'new' => "->helperText('Autorise les champs traduisibles a stocker des valeurs dans plusieurs langues.')",
        ],
        [
            'old' => "->helperText('Select which locales this collection supports.')",
            'new' => "->helperText('Selectionne les langues supportees par cette collection.')",
        ],
        [
            'old' => "->placeholder('Use global default')",
            'new' => "->placeholder('Utiliser la langue globale')",
        ],
        [
            'old' => "->helperText('The fallback locale when a translation is missing.'),",
            'new' => "->helperText('Langue de secours lorsqu une traduction est manquante.'),",
        ],
        [
            'old' => '->columns(2),',
            'new' => '->columns(1),',
        ],
    ],
];

$changed = 0;

foreach ($targets as $file => $replacements) {
    if (! is_file($file)) {
        continue;
    }

    $content = file_get_contents($file);
    if ($content === false) {
        continue;
    }

    $original = $content;

    foreach ($replacements as $replacement) {
        $old = $replacement['old'];
        $new = $replacement['new'];

        if (str_contains($content, $new)) {
            continue;
        }

        $content = str_replace($old, $new, $content);
    }

    if ($content !== $original) {
        file_put_contents($file, $content);
        $changed++;
    }
}

echo "[studio-fr-patch] files changed: {$changed}".PHP_EOL;
