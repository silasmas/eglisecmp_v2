<?php

declare(strict_types=1);

/**
 * Génère le brief designer des tailles d’images CMP en Excel.
 *
 * Usage : php scripts/generate_image_sizes_brief.php
 */

require __DIR__.'/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$outDir = __DIR__.'/../docs';
if (! is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

$outPath = $outDir.'/CMP-brief-tailles-images-designer.xlsx';

$spreadsheet = new Spreadsheet();

/**
 * Applique un style d’en-tête sur la première ligne.
 */
function styleHeader(Worksheet $sheet, int $colCount): void
{
    $range = 'A1:'.chr(64 + $colCount).'1';
    $sheet->getStyle($range)->getFont()->setBold(true);
    $sheet->getStyle($range)->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setRGB('7B1D3E');
    $sheet->getStyle($range)->getFont()->getColor()->setRGB('FFFFFF');
    $sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getRowDimension(1)->setRowHeight(22);
}

/**
 * Remplit une feuille à partir d’en-têtes + lignes.
 *
 * @param  list<string>  $headers
 * @param  list<list<string>>  $rows
 */
function fillSheet(Worksheet $sheet, string $title, array $headers, array $rows): void
{
    $sheet->setTitle($title);
    foreach ($headers as $i => $header) {
        $sheet->setCellValue([$i + 1, 1], $header);
    }
    foreach ($rows as $r => $row) {
        foreach ($row as $c => $value) {
            $sheet->setCellValue([$c + 1, $r + 2], $value);
        }
    }
    styleHeader($sheet, count($headers));
    foreach (range(1, count($headers)) as $col) {
        $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
    }
    $sheet->setAutoFilter([1, 1, count($headers), max(1, count($rows) + 1)]);
    $sheet->freezePane('A2');
}

// --- Feuille 0 : Synthèse ---
$sheet0 = $spreadsheet->getActiveSheet();
fillSheet($sheet0, '0-Synthese', [
    'Priorité',
    'Pack designer',
    'Taille livrable (px)',
    'Ratio',
    'Usage',
    'Notes',
], [
    ['1', 'Bannières de pages (PageHero)', '1920 × 840', '~16:7 / 7:3', 'Toutes les pages hors accueil', 'Master unique compatible 1600×700 et 1400×600. Zone texte à gauche/centre. Éviter détails en bas (dégradé).'],
    ['1', 'Hero accueil (plein écran)', '1920 × 1080', '16:9 / plein viewport', 'Page d’accueil — fond min-h-screen', 'Source code actuelle : 1800×1100. Sujet centré. Peu de détails en bas (UI + dégradé).'],
    ['2', 'Aperçu À propos (accueil)', '1600 × 1000', '16:10', 'AboutPreviewSection', 'Source placeholder : 1200×800. Affichage aspect 16/10.'],
    ['2', 'Encarts À propos / Contact / Découvrir', '1200 × 900', '4:3', 'Colonnes latérales pages À propos, Contact, Découvrir, JoinContact', 'Sources code : 500×375 ou 700×525.'],
    ['2', 'CTA « Nous rendre visite »', '1920 × 840', '7:3', 'VisitCTASection', 'Source : 1400×600.'],
    ['3', 'Portraits pasteurs (Leadership)', '800 × 1000', '4:5', 'Cartes Leadership', 'Source défaut : 400×500. Affichage aspect 4/5.'],
    ['3', 'Photos dirigeants extensions', '400 × 400', '1:1', 'Avatar circulaire Extensions', 'Affiché 40–64 px ; livrer 400×400 pour netteté.'],
    ['3', 'Cartes événements', '1200 × 800', '3:2', 'EventCard / événements', 'Fallback source : 1200×800.'],
    ['3', 'Médias / programmes / galerie', '1200 × 900', '4:3', 'Grilles médias & programmes', 'Sources data : 600×400.'],
    ['3', 'Vidéos / sermons', '1280 × 720', '16:9', 'Lecteurs & vignettes vidéo', 'aspect-video.'],
    ['3', 'Mur de témoignages — hero', '1920 × 1080', '16:9', 'TestimonyWallPage', 'Source : 1600×900, plein écran.'],
    ['3', 'Badge ouvrier (fond)', '2480 × 3508', 'A4 portrait', 'WorkerBadgeCard', 'Ratio CSS 2480/3508 @ ~300 dpi.'],
    ['3', 'Photo ouvrier (crop)', '800 × 800', '1:1', 'Inscription ouvrier', 'Crop carré UI.'],
]);

// --- Feuille 1 : Bannières ---
$sheet1 = $spreadsheet->createSheet();
fillSheet($sheet1, '1-Bannieres-PageHero', [
    'Ordre',
    'Page / écran',
    'Fichier source',
    'Taille exacte dans le code (px)',
    'Ratio source',
    'Livrable designer conseillé (px)',
    'Affichage',
    'Remarques',
], [
    ['1', 'Défaut (si pas d’image)', 'components/ui/PageHero.tsx', '1600 × 700', '16:7 (~2,29:1)', '1920 × 840', 'Pleine largeur, object-cover, hauteur ~320–420 px', 'DEFAULT_PAGE_BANNER'],
    ['2', 'À propos — Notre histoire', 'pages/AboutPage.tsx', '1400 × 600', '7:3 (~2,33:1)', '1920 × 840', 'PageHero standard', ''],
    ['3', 'Découvrir', 'pages/DiscoverPage.tsx', '1400 × 600', '7:3', '1920 × 840', 'PageHero standard', ''],
    ['4', 'Vision & Mission', 'pages/VisionPage.tsx', '1600 × 700', '16:7', '1920 × 840', 'PageHero standard', 'Pas d’image de section sous le hero'],
    ['5', 'Leadership — Nos pasteurs', 'pages/LeadershipPage.tsx', '1600 × 700', '16:7', '1920 × 840', 'PageHero standard', ''],
    ['6', 'Contact', 'pages/ContactPage.tsx', '1600 × 700', '16:7', '1920 × 840', 'PageHero standard', ''],
    ['7', 'Cellules', 'pages/CellsPage.tsx', '1400 × 600', '7:3', '1920 × 840', 'PageHero standard', ''],
    ['8', 'Extensions', 'pages/ExtensionsPage.tsx', '1400 × 600', '7:3', '1920 × 840', 'PageHero standard', ''],
    ['9', 'Rejoindre', 'pages/JoinPage.tsx', '1400 × 600', '7:3', '1920 × 840', 'PageHero standard', ''],
    ['10', 'Médias', 'pages/MediaPage.tsx', '1400 × 600', '7:3', '1920 × 840', 'PageHero standard', ''],
    ['11', 'Enseignements', 'pages/TeachingsPage.tsx', '1400 × 600', '7:3', '1920 × 840', 'PageHero standard', ''],
    ['12', 'Événements', 'pages/EventsPage.tsx', '1400 × 600', '7:3', '1920 × 840', 'PageHero standard', ''],
    ['13', 'Conférence Bunda', 'pages/BundaPage.tsx', '1400 × 600', '7:3', '1920 × 840', 'PageHero standard', 'Peut être remplacée par image API édition'],
    ['14', 'Offrandes', 'pages/OffrandesPage.tsx', '1600 × 700', '16:7', '1920 × 840', 'PageHero standard', ''],
    ['15', 'Rendez-vous pastoral', 'pages/AppointmentPage.tsx', '1600 × 700', '16:7', '1920 × 840', 'PageHero standard', ''],
    ['16', 'Demande de prière', 'pages/PrayerRequestPage.tsx', '1600 × 700', '16:7', '1920 × 840', 'PageHero standard', ''],
    ['17', 'Présentation d’enfant', 'pages/ChildPresentationPage.tsx', '1600 × 700', '16:7', '1920 × 840', 'PageHero standard', ''],
    ['18', 'Lecture playlist', 'pages/PlaylistWatchPage.tsx', '1600 × 700', '16:7', '1920 × 840', 'PageHero standard', ''],
    ['19', 'Lecture message', 'pages/MessageWatchPage.tsx', '1600 × 700', '16:7', '1920 × 840', 'PageHero standard', ''],
    ['20', 'Raccourcis QR', 'pages/QrShortcutsLandingPage.tsx', '1600 × 700', '16:7', '1920 × 840', 'PageHero standard', ''],
    ['21', 'Désabonnement alerte', 'pages/AlertUnsubscribePage.tsx', '1400 × 600', '7:3', '1920 × 840', 'PageHero standard', ''],
    ['22', 'Page d’erreur site', 'pages/SiteErrorPage.tsx', '1600 × 700', '16:7', '1920 × 840', 'PageHero standard', ''],
    ['23', 'Inscription ouvrier', 'pages/WorkerRegistrationPage.tsx', '1600 × 700 (défaut PageHero)', '16:7', '1920 × 840', 'PageHero compact', 'Pas de backgroundImage custom → défaut'],
    ['24', 'Badge ouvrier (page)', 'pages/WorkerBadgePage.tsx', '1600 × 700 (défaut)', '16:7', '1920 × 840', 'PageHero', 'Vérifier si image custom absente'],
]);

// --- Feuille 2 : À propos ---
$sheet2 = $spreadsheet->createSheet();
fillSheet($sheet2, '2-Apropos-Accueil', [
    'Ordre',
    'Emplacement',
    'Fichier source',
    'Taille exacte dans le code (px)',
    'Ratio CSS affichage',
    'Livrable designer conseillé (px)',
    'Remarques',
], [
    ['1', 'Hero accueil — fond plein écran', 'components/sections/HeroSection.tsx', '1800 × 1100', 'min-h-screen, object-cover', '1920 × 1080 (ou 2400 × 1480)', 'Fallback image si pas de vidéo. Zone sûre au centre.'],
    ['2', 'Aperçu À propos — grande image', 'components/sections/AboutPreviewSection.tsx', '1200 × 800 (DEFAULT_PLACEHOLDER_IMAGE)', 'aspect-[16/10]', '1600 × 1000', 'Colonne gauche grille 2 cols. Overlay texte en bas.'],
    ['3', 'Page À propos — encart latéral', 'pages/AboutPage.tsx', '500 × 375', 'aspect-[4/3]', '1200 × 900', 'Colonne droite sous « Nos valeurs ».'],
    ['4', 'Page Découvrir — encart', 'pages/DiscoverPage.tsx', '700 × 525', 'aspect-[4/3]', '1200 × 900', ''],
    ['5', 'Page Contact — encart', 'pages/ContactPage.tsx', '500 × 375', 'aspect-[4/3]', '1200 × 900', ''],
    ['6', 'Section Rejoindre / contact (accueil)', 'components/sections/JoinContactSection.tsx', '500 × 375', 'aspect-[4/3]', '1200 × 900', ''],
    ['7', 'CTA « Nous rendre visite »', 'components/sections/VisitCTASection.tsx', '1400 × 600', 'fond large object-cover', '1920 × 840', 'Bandeau CTA bas de page / section.'],
]);

// --- Feuille 3 : Autres ---
$sheet3 = $spreadsheet->createSheet();
fillSheet($sheet3, '3-Autres-visuels', [
    'Ordre',
    'Emplacement',
    'Fichier / contexte',
    'Taille exacte dans le code (px)',
    'Ratio affichage',
    'Livrable designer conseillé (px)',
    'Remarques',
], [
    ['1', 'Mur de témoignages — hero', 'pages/TestimonyWallPage.tsx', '1600 × 900', 'plein écran (~16:9)', '1920 × 1080', 'Hero dédié (pas PageHero).'],
    ['2', 'Leadership — portrait pasteur', 'pages/LeadershipPage.tsx', '400 × 500', 'aspect-[4/5]', '800 × 1000', 'Fallback DEFAULT_PORTRAIT ; object-top.'],
    ['3', 'Extensions — photo dirigeant (liste)', 'pages/ExtensionsPage.tsx', 'affichage 40×40 / 48×48 / 64×64', '1:1 cercle', '400 × 400', 'Upload admin Filament ; crop face recommandé.'],
    ['4', 'RDV — avatar pasteur', 'pages/AppointmentPage.tsx', 'affichage 64×64', '1:1 cercle', '256 × 256', ''],
    ['5', 'Actions rapides — bandeau image', 'components/sections/QuickActionsSection.tsx', '1200 × 400', '~3:1', '1800 × 600', 'Tuile haute 220–280 px.'],
    ['6', 'Carte événement (fallback)', 'components/cards/EventCard.tsx', '1200 × 800', 'cover carte haute (~3:2)', '1200 × 800', 'min-h ~22–26 rem.'],
    ['7', 'Carrousel événements (accueil)', 'components/sections/EventsSection.tsx', 'hauteur aff. 400–464 px', 'cover large', '1400 × 900', 'h-[25rem] / sm:h-[29rem].'],
    ['8', 'Enseignement vedette (accueil)', 'components/sections/TeachingsSection.tsx', '— (API / placeholder)', 'aspect-[16/10] mobile ; zone ~7/12 desktop', '1600 × 1000', 'Hauteur grille lg:h-[32rem].'],
    ['9', 'Cartes programmes / sermons (data)', 'data/content.ts', '600 × 400', '3:2', '1200 × 800', 'Thumbnails programmes.'],
    ['10', 'Images événements (data)', 'data/content.ts', '800 × 500 ou 600 × 400', 'varie', '1200 × 800', ''],
    ['11', 'Galerie médias', 'data/content.ts + MediaCard', '600 × 400', 'aspect-[4/3]', '1200 × 900', ''],
    ['12', 'Familles bénies', 'data/blessedFamilies.ts', '800 × 600 (parfois 800×700)', '4:3 / proche', '1600 × 1200', 'ChildPresentationPage.'],
    ['13', 'Modale détail événement', 'components/ui/EventDetailModal.tsx', '—', 'aspect-[21/10]', '1680 × 800', 'Bandeau haut modale.'],
    ['14', 'Modale bandeau hero', 'components/ui/HeroStripModal.tsx', '—', 'aspect-[21/9] (ou 16/10 upcoming)', '1680 × 720', ''],
    ['15', 'Sermon / vidéo', 'components/cards/SermonCard.tsx', '—', '16:9 (aspect-video)', '1280 × 720', 'featured aussi 16:9.'],
    ['16', 'Témoignage — images slider', 'TestimonyImageSlider.tsx', '—', 'aspect-[4/3]', '1200 × 900', ''],
    ['17', 'Badge ouvrier — fond A4', 'styles/worker-badge.css', 'ratio 2480 / 3508', 'A4 portrait', '2480 × 3508 @ 300 dpi', 'Print + écran.'],
    ['18', 'Photo ouvrier crop', 'PhotoCropField.tsx', 'carré UI', '1:1', '800 × 800', ''],
    ['19', 'Placeholder global site', 'lib/placeholderImage.ts', '1200 × 800', '3:2', '1200 × 800', 'DEFAULT_PLACEHOLDER_IMAGE'],
    ['20', 'FramedImage (composant générique)', 'components/ui/FramedImage.tsx', 'selon props', 'défaut aspect-[4/3]', '1200 × 900', ''],
]);

// --- Feuille 4 : Consignes ---
$sheet4 = $spreadsheet->createSheet();
fillSheet($sheet4, '4-Consignes-designer', [
    'N°',
    'Consigne',
    'Détail',
], [
    ['1', 'Format fichier', 'JPG ou WebP, sRGB. Pour print badge : TIFF/PNG possible.'],
    ['2', 'Recadrage', 'Toutes les images sont en object-cover : le sujet doit rester dans la zone centrale sûre.'],
    ['3', 'Bannières PageHero', 'Texte blanc superposé à gauche. Dégradé sombre en haut, fondu blanc en bas → éviter infos importantes en bas.'],
    ['4', 'Master bannière', 'Un seul format 1920×840 suffit pour toutes les pages (remplace 1600×700 et 1400×600).'],
    ['5', 'Hero accueil', 'Plein écran ; peut être remplacé par une vidéo. Image = fallback.'],
    ['6', 'Nommage suggéré', 'cmp-banner-{page}.jpg · cmp-about-preview.jpg · cmp-about-side.jpg · cmp-leader-{nom}.jpg · cmp-extension-leader-{ville}.jpg'],
    ['7', 'Source technique', 'Inventaire code React resources/js/site — paramètres Unsplash w×h + classes Tailwind aspect-*.'],
    ['8', 'Date brief', date('Y-m-d')],
]);

$spreadsheet->setActiveSheetIndex(0);

$writer = new Xlsx($spreadsheet);
$writer->save($outPath);

// Copie texte lisible (même ordre)
$txtPath = $outDir.'/CMP-brief-tailles-images-designer.txt';
$txt = [];
$txt[] = 'CMP — BRIEF DESIGNER : TAILLES D’IMAGES DU SITE PUBLIC';
$txt[] = str_repeat('=', 72);
$txt[] = 'Généré le '.date('Y-m-d H:i');
$txt[] = 'Fichier Excel associé : CMP-brief-tailles-images-designer.xlsx';
$txt[] = '';
$txt[] = 'RECOMMANDATION LIVRAISON';
$txt[] = '- Bannières pages : 1920 × 840 px (master unique)';
$txt[] = '- Hero accueil : 1920 × 1080 px';
$txt[] = '- Aperçu À propos : 1600 × 1000 px (16:10)';
$txt[] = '- Encarts 4:3 : 1200 × 900 px';
$txt[] = '- object-cover : zone sûre au centre ; éviter bas de bannière';
$txt[] = '';

$sheetsData = [
    '0 — SYNTHÈSE (packs à produire)' => [
        ['Priorité', 'Pack', 'Livrable', 'Ratio', 'Usage'],
        ['1', 'Bannières PageHero', '1920×840', '~16:7', 'Toutes pages hors accueil'],
        ['1', 'Hero accueil', '1920×1080', '16:9', 'Fond plein écran'],
        ['2', 'Aperçu À propos', '1600×1000', '16:10', 'Home AboutPreview'],
        ['2', 'Encarts À propos/Contact', '1200×900', '4:3', 'Pages À propos, Contact, Découvrir'],
        ['2', 'CTA visite', '1920×840', '7:3', 'VisitCTASection'],
        ['3', 'Portraits pasteurs', '800×1000', '4:5', 'Leadership'],
        ['3', 'Dirigeants extensions', '400×400', '1:1', 'Avatars'],
        ['3', 'Événements', '1200×800', '3:2', 'EventCard'],
        ['3', 'Médias/galerie', '1200×900', '4:3', 'Grilles'],
        ['3', 'Vidéo', '1280×720', '16:9', 'Sermons'],
        ['3', 'Mur témoignages', '1920×1080', '16:9', 'Hero mur'],
        ['3', 'Badge ouvrier fond', '2480×3508', 'A4', 'Print'],
        ['3', 'Photo ouvrier', '800×800', '1:1', 'Crop'],
    ],
];

foreach ($sheetsData as $title => $table) {
    $txt[] = $title;
    $txt[] = str_repeat('-', 72);
    foreach ($table as $row) {
        $txt[] = implode(' | ', $row);
    }
    $txt[] = '';
}

$txt[] = '1 — BANNIÈRES DE PAGE (PageHero) — détail page par page';
$txt[] = str_repeat('-', 72);
$banners = [
    '1. Défaut PageHero — 1600×700 — livrer 1920×840',
    '2. À propos — 1400×600 — livrer 1920×840',
    '3. Découvrir — 1400×600 — livrer 1920×840',
    '4. Vision & Mission — 1600×700 — livrer 1920×840',
    '5. Leadership — 1600×700 — livrer 1920×840',
    '6. Contact — 1600×700 — livrer 1920×840',
    '7. Cellules — 1400×600 — livrer 1920×840',
    '8. Extensions — 1400×600 — livrer 1920×840',
    '9. Rejoindre — 1400×600 — livrer 1920×840',
    '10. Médias — 1400×600 — livrer 1920×840',
    '11. Enseignements — 1400×600 — livrer 1920×840',
    '12. Événements — 1400×600 — livrer 1920×840',
    '13. Bunda — 1400×600 — livrer 1920×840',
    '14. Offrandes — 1600×700 — livrer 1920×840',
    '15. Rendez-vous — 1600×700 — livrer 1920×840',
    '16. Prière — 1600×700 — livrer 1920×840',
    '17. Présentation enfant — 1600×700 — livrer 1920×840',
    '18. Playlist watch — 1600×700 — livrer 1920×840',
    '19. Message watch — 1600×700 — livrer 1920×840',
    '20. QR shortcuts — 1600×700 — livrer 1920×840',
    '21. Désabonnement alerte — 1400×600 — livrer 1920×840',
    '22. Erreur site — 1600×700 — livrer 1920×840',
    '23. Inscription ouvrier — défaut 1600×700 — livrer 1920×840',
    '24. Badge ouvrier page — défaut 1600×700 — livrer 1920×840',
];
foreach ($banners as $line) {
    $txt[] = $line;
}
$txt[] = '';
$txt[] = 'Affichage commun : pleine largeur viewport, object-cover, hauteur ~320–420 px desktop.';
$txt[] = 'Éviter détails importants en bas (dégradé vers blanc).';
$txt[] = '';

$txt[] = '2 — ACCUEIL & SECTIONS À PROPOS';
$txt[] = str_repeat('-', 72);
$about = [
    '1. Hero accueil fond — 1800×1100 — ratio plein écran — livrer 1920×1080',
    '2. AboutPreviewSection grande image — 1200×800 — ratio 16:10 — livrer 1600×1000',
    '3. AboutPage encart latéral — 500×375 — ratio 4:3 — livrer 1200×900',
    '4. DiscoverPage encart — 700×525 — ratio 4:3 — livrer 1200×900',
    '5. ContactPage encart — 500×375 — ratio 4:3 — livrer 1200×900',
    '6. JoinContactSection — 500×375 — ratio 4:3 — livrer 1200×900',
    '7. VisitCTASection — 1400×600 — ratio 7:3 — livrer 1920×840',
];
foreach ($about as $line) {
    $txt[] = $line;
}
$txt[] = '';

$txt[] = '3 — AUTRES VISUELS';
$txt[] = str_repeat('-', 72);
$others = [
    '1. Mur témoignages hero — 1600×900 — livrer 1920×1080',
    '2. Leadership portraits — 400×500 (aff. 4:5) — livrer 800×1000',
    '3. Extensions dirigeant — cercle 40–64 px — livrer 400×400',
    '4. RDV avatar pasteur — 64×64 — livrer 256×256',
    '5. QuickActions bandeau — 1200×400 — livrer 1800×600',
    '6. EventCard fallback — 1200×800 — livrer 1200×800',
    '7. EventsSection carrousel — hauteur 400–464 px — livrer 1400×900',
    '8. Teachings vedette — 16:10 — livrer 1600×1000',
    '9. Programmes thumbnails — 600×400 — livrer 1200×800',
    '10. Événements data — 800×500 / 600×400 — livrer 1200×800',
    '11. Galerie médias — 600×400 aff. 4:3 — livrer 1200×900',
    '12. Familles bénies — 800×600 — livrer 1600×1200',
    '13. Modale event — ratio 21:10 — livrer 1680×800',
    '14. Modale hero strip — ratio 21:9 — livrer 1680×720',
    '15. Sermon/vidéo — 16:9 — livrer 1280×720',
    '16. Testimony slider — 4:3 — livrer 1200×900',
    '17. Badge ouvrier fond A4 — 2480×3508',
    '18. Photo ouvrier crop — 800×800',
    '19. Placeholder global — 1200×800',
    '20. FramedImage défaut — 4:3 — livrer 1200×900',
];
foreach ($others as $line) {
    $txt[] = $line;
}
$txt[] = '';
$txt[] = '4 — CONSIGNES';
$txt[] = str_repeat('-', 72);
$txt[] = '- JPG/WebP sRGB ; badge print : PNG/TIFF possible';
$txt[] = '- object-cover → sujet dans zone centrale';
$txt[] = '- Nommage : cmp-banner-{page}.jpg, cmp-about-preview.jpg, cmp-about-side.jpg, etc.';
$txt[] = '- Source : code React resources/js/site';
$txt[] = '';

file_put_contents($txtPath, implode(PHP_EOL, $txt));

echo "OK Excel : {$outPath}".PHP_EOL;
echo "OK Texte : {$txtPath}".PHP_EOL;
