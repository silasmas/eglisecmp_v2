<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GuestInfoForm;
use App\Models\GuestInfoFormField;
use App\Models\GuestInfoFormSection;
use Illuminate\Support\Str;

/**
 * Charge le template PDF « Fiche de renseignements » dans un formulaire.
 */
final class GuestInfoFormPdfTemplateService
{
    /**
     * Grille alimentaire du PDF (orateur / conjoint).
     *
     * @return array{rows: list<array{type: string, items: list<string>}>}
     */
    public static function defaultFoodGridOptions(): array
    {
        return [
            'rows' => [
                ['type' => 'Fruits', 'items' => ['Mangue', 'Orange', 'Ananas', 'Pomme', 'Mandarine']],
                ['type' => 'Pâtisseries', 'items' => ['Croissant au beurre', 'Croissant au chocolat', 'Baguette française', 'Croissant au fromage']],
                ['type' => 'Légumes', 'items' => ['Pondu', 'Épinards', 'Salade', 'Aubergine', 'Légume vert']],
                ['type' => 'Charcuteries', 'items' => ['Jambon', 'Dinde', 'Poulet', 'Fromage']],
                ['type' => 'Féculents', 'items' => ['Pomme de terre', 'Plantain', 'Riz', 'Patate douce', 'Pâtes', 'Maïs']],
                ['type' => 'Viande', 'items' => ['Porc', 'Boeuf', 'Volaille', 'Agneau', 'Chèvre', 'Hachée']],
                ['type' => 'Poisson', 'items' => ['Capitaine', 'Sole', 'Thon', 'Tilapia', 'Poisson salé', 'Crevettes']],
            ],
        ];
    }

    /**
     * Remplace les rubriques/champs du formulaire par le template PDF.
     *
     * @param  list<int>  $defaultDepartmentIds  Départements par défaut sur les rubriques.
     */
    public function applyToForm(GuestInfoForm $form, array $defaultDepartmentIds = []): void
    {
        $form->sections()->each(function (GuestInfoFormSection $section): void {
            $section->fields()->delete();
            $section->delete();
        });

        $sort = 0;
        foreach ($this->templateDefinition($defaultDepartmentIds) as $sectionDef) {
            $section = GuestInfoFormSection::query()->create([
                'form_id' => $form->id,
                'title' => $sectionDef['title'],
                'description' => $sectionDef['description'] ?? null,
                'sort_order' => $sort++,
                'department_ids' => $sectionDef['department_ids'] ?? $defaultDepartmentIds,
            ]);

            $fieldSort = 0;
            foreach ($sectionDef['fields'] as $fieldDef) {
                GuestInfoFormField::query()->create([
                    'section_id' => $section->id,
                    'key' => $fieldDef['key'] ?? Str::slug($fieldDef['label'], '_'),
                    'label' => $fieldDef['label'],
                    'type' => $fieldDef['type'],
                    'options' => $fieldDef['options'] ?? null,
                    'department_ids' => $fieldDef['department_ids'] ?? null,
                    'required' => (bool) ($fieldDef['required'] ?? false),
                    'sort_order' => $fieldSort++,
                    'help_text' => $fieldDef['help_text'] ?? null,
                ]);
            }
        }

        if (blank($form->cmp_info_html)) {
            $form->update([
                'cmp_info_html' => $this->defaultCmpInfoHtml(),
            ]);
        }
    }

    /**
     * HTML infos CMP (lecture seule sur le formulaire public).
     */
    public function defaultCmpInfoHtml(): string
    {
        return <<<'HTML'
<p><strong>Centre Missionnaire Philadelphie</strong></p>
<p>Nom du Pasteur principal : Pasteur Ken LUAMBA<br>
Nom des hôtes : Couple Nathalie et Ken LUAMBA<br>
Pays d’accueil : RDC — Ville : Kinshasa<br>
Adresse : 7 avenue de l’imprimerie, Kinshasa/Gombe</p>
<p>Contact : Emmanuel BENI — +243 83 21 17 782 — ebeni@cm-philadelphie.org<br>
CMP : +243 81 04 66 883 — info@cm-philadelphie.org</p>
<p><em>Programme des cultes — Mercredi / Jeudi 17h30–19h30 · Dimanche 07h00–09h00 et 09h30–11h30</em></p>
HTML;
    }

    /**
     * Définition des rubriques du PDF.
     *
     * @param  list<int>  $defaultDepartmentIds
     * @return list<array<string, mixed>>
     */
    private function templateDefinition(array $defaultDepartmentIds): array
    {
        $food = self::defaultFoodGridOptions();

        return [
            [
                'title' => 'Informations sur l’invité',
                'description' => 'Identité du pasteur invité et de sa délégation.',
                'department_ids' => $defaultDepartmentIds,
                'fields' => [
                    ['key' => 'invite_pastor_name', 'label' => 'Nom du Pasteur Invité', 'type' => GuestInfoFormField::TYPE_TEXT, 'required' => true],
                    ['key' => 'invite_church', 'label' => 'Nom de l’église de provenance', 'type' => GuestInfoFormField::TYPE_TEXT, 'required' => true],
                    ['key' => 'invite_country', 'label' => 'Pays de provenance', 'type' => GuestInfoFormField::TYPE_TEXT, 'required' => true],
                    ['key' => 'invite_city', 'label' => 'Ville de provenance', 'type' => GuestInfoFormField::TYPE_TEXT],
                    ['key' => 'invite_address', 'label' => 'Adresse', 'type' => GuestInfoFormField::TYPE_TEXTAREA],
                    ['key' => 'invite_phone', 'label' => 'Numéro de téléphone', 'type' => GuestInfoFormField::TYPE_PHONE, 'required' => true],
                    ['key' => 'invite_email', 'label' => 'E-mail', 'type' => GuestInfoFormField::TYPE_EMAIL],
                    ['key' => 'delegation_present', 'label' => 'Présence d’une délégation', 'type' => GuestInfoFormField::TYPE_YES_NO],
                    ['key' => 'delegation_count', 'label' => 'Nombre de personnes (délégation)', 'type' => GuestInfoFormField::TYPE_TEXT],
                    ['key' => 'delegation_names', 'label' => 'Noms des membres de la délégation', 'type' => GuestInfoFormField::TYPE_REPEATER_NAMES],
                ],
            ],
            [
                'title' => 'Conjoint de l’orateur',
                'department_ids' => $defaultDepartmentIds,
                'fields' => [
                    ['key' => 'spouse_name', 'label' => 'Nom du conjoint', 'type' => GuestInfoFormField::TYPE_TEXT],
                    ['key' => 'spouse_phone', 'label' => 'Téléphone du conjoint', 'type' => GuestInfoFormField::TYPE_PHONE],
                    ['key' => 'spouse_email', 'label' => 'E-mail du conjoint', 'type' => GuestInfoFormField::TYPE_EMAIL],
                ],
            ],
            [
                'title' => 'Personne de contact',
                'department_ids' => $defaultDepartmentIds,
                'fields' => [
                    ['key' => 'contact_name', 'label' => 'Nom de la personne de contact', 'type' => GuestInfoFormField::TYPE_TEXT],
                    ['key' => 'contact_phone', 'label' => 'Téléphone', 'type' => GuestInfoFormField::TYPE_PHONE],
                    ['key' => 'contact_email', 'label' => 'E-mail', 'type' => GuestInfoFormField::TYPE_EMAIL],
                ],
            ],
            [
                'title' => 'Informations sur le culte — Matériel nécessaire',
                'description' => 'Cocher les cases nécessaires.',
                'department_ids' => $defaultDepartmentIds,
                'fields' => [
                    [
                        'key' => 'worship_equipment',
                        'label' => 'Matériel nécessaire',
                        'type' => GuestInfoFormField::TYPE_CHECKBOX_GROUP,
                        'options' => [
                            'choices' => [
                                'retroprojecteur' => 'Rétroprojecteur',
                                'micro_main' => 'Micro à main',
                                'vente_ouvrages' => 'Vente d’ouvrages',
                                'vestiaire' => 'Endroit pour changer de tenue après le culte',
                                'piano' => 'Accompagnement piano pendant la prédication',
                                'autres' => 'Autres',
                            ],
                        ],
                    ],
                    ['key' => 'worship_equipment_other', 'label' => 'Préciser (autres besoins matériel)', 'type' => GuestInfoFormField::TYPE_TEXTAREA],
                ],
            ],
            $this->mensurationsSection($defaultDepartmentIds),
            [
                'title' => 'Informations sur le culte — Breuvage & alimentation',
                'department_ids' => $defaultDepartmentIds,
                'fields' => [
                    ['key' => 'needs_beverage', 'label' => 'Besoin de breuvage', 'type' => GuestInfoFormField::TYPE_YES_NO],
                    ['key' => 'dietary_regime', 'label' => 'Régime alimentaire particulier ?', 'type' => GuestInfoFormField::TYPE_YES_NO],
                    ['key' => 'dietary_details', 'label' => 'Si oui, lequel ?', 'type' => GuestInfoFormField::TYPE_TEXTAREA],
                    [
                        'key' => 'food_speaker',
                        'label' => 'Nourriture de l’orateur',
                        'type' => GuestInfoFormField::TYPE_FOOD_GRID,
                        'options' => $food,
                    ],
                    [
                        'key' => 'food_spouse',
                        'label' => 'Nourriture du conjoint de l’orateur',
                        'type' => GuestInfoFormField::TYPE_FOOD_GRID,
                        'options' => $food,
                    ],
                ],
            ],
            [
                'title' => 'Autres besoins',
                'department_ids' => $defaultDepartmentIds,
                'fields' => [
                    ['key' => 'other_needs_yes', 'label' => 'Avez-vous d’autres besoins ?', 'type' => GuestInfoFormField::TYPE_YES_NO],
                    ['key' => 'other_needs_details', 'label' => 'Si oui, lesquels ?', 'type' => GuestInfoFormField::TYPE_TEXTAREA],
                ],
            ],
        ];
    }

    /**
     * Rubrique mensurations orateur / épouse (tailles t-shirt / chemise).
     *
     * @param  list<int>  $defaultDepartmentIds
     * @return array<string, mixed>
     */
    public function mensurationsSection(array $defaultDepartmentIds = []): array
    {
        $sizeChoices = [
            's_32' => 'S / 32',
            'm_40' => 'M / 40',
            'l_42' => 'L / 42',
            'xl_44' => 'XL / 44',
            'xxl_46' => 'XXL / 46',
            'xxxl_48' => 'XXXL / 48',
            'autres' => 'Autres',
        ];

        return [
            'title' => 'Mensurations',
            'description' => 'Veuillez encercler / choisir la taille de t-shirt ou chemise.',
            'department_ids' => $defaultDepartmentIds,
            'fields' => [
                [
                    'key' => 'shirt_size_speaker',
                    'label' => 'Taille T-shirt / Chemise — Orateur',
                    'type' => GuestInfoFormField::TYPE_SINGLE_CHOICE,
                    'required' => true,
                    'options' => ['choices' => $sizeChoices],
                ],
                [
                    'key' => 'shirt_size_speaker_other',
                    'label' => 'Préciser la taille (orateur)',
                    'type' => GuestInfoFormField::TYPE_TEXT,
                    'options' => [
                        'visible_when' => ['field' => 'shirt_size_speaker', 'equals' => 'autres'],
                    ],
                ],
                [
                    'key' => 'spouse_coming',
                    'label' => 'L’épouse accompagne-t-elle l’orateur ?',
                    'type' => GuestInfoFormField::TYPE_YES_NO,
                    'required' => true,
                ],
                [
                    'key' => 'shirt_size_spouse',
                    'label' => 'Taille T-shirt / Chemise — Épouse',
                    'type' => GuestInfoFormField::TYPE_SINGLE_CHOICE,
                    'options' => [
                        'choices' => $sizeChoices,
                        'visible_when' => ['field' => 'spouse_coming', 'equals' => 'Oui'],
                    ],
                ],
                [
                    'key' => 'shirt_size_spouse_other',
                    'label' => 'Préciser la taille (épouse)',
                    'type' => GuestInfoFormField::TYPE_TEXT,
                    'options' => [
                        'visible_when' => ['field' => 'shirt_size_spouse', 'equals' => 'autres'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Ajoute les champs mensurations manquants sans écraser le reste du formulaire.
     *
     * @param  list<int>  $defaultDepartmentIds
     * @return int Nombre de champs créés
     */
    public function mergeMensurationsFields(GuestInfoForm $form, array $defaultDepartmentIds = []): int
    {
        $sectionDef = $this->mensurationsSection($defaultDepartmentIds);
        $existingKeys = $form->sections()
            ->with('fields')
            ->get()
            ->flatMap(fn (GuestInfoFormSection $s) => $s->fields->pluck('key'))
            ->all();

        $section = $form->sections()->where('title', $sectionDef['title'])->first();
        if ($section === null) {
            $maxSort = (int) $form->sections()->max('sort_order');
            $section = GuestInfoFormSection::query()->create([
                'form_id' => $form->id,
                'title' => $sectionDef['title'],
                'description' => $sectionDef['description'] ?? null,
                'sort_order' => $maxSort + 1,
                'department_ids' => $sectionDef['department_ids'] ?? $defaultDepartmentIds,
            ]);
        }

        $created = 0;
        $fieldSort = (int) $section->fields()->max('sort_order');
        foreach ($sectionDef['fields'] as $fieldDef) {
            $key = $fieldDef['key'] ?? Str::slug($fieldDef['label'], '_');
            if (in_array($key, $existingKeys, true)) {
                continue;
            }
            GuestInfoFormField::query()->create([
                'section_id' => $section->id,
                'key' => $key,
                'label' => $fieldDef['label'],
                'type' => $fieldDef['type'],
                'options' => $fieldDef['options'] ?? null,
                'department_ids' => $fieldDef['department_ids'] ?? null,
                'required' => (bool) ($fieldDef['required'] ?? false),
                'sort_order' => ++$fieldSort,
                'help_text' => $fieldDef['help_text'] ?? null,
            ]);
            $created++;
        }

        return $created;
    }
}
