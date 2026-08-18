<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\GuestInfoForm;
use App\Models\GuestPastoralProject;
use App\Services\GuestInfoFormPdfTemplateService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Crée ou met à jour un formulaire avec le template PDF Fiche de renseignements.
 */
class SeedGuestFormPdfTemplateCommand extends Command
{
    protected $signature = 'guest-form:seed-pdf-template
        {--form= : ID du formulaire existant à remplir}
        {--project= : ID du projet (crée un formulaire si --form omis)}
        {--title=Fiche de renseignements : Titre du formulaire créé}';

    protected $description = 'Charge le template PDF (rubriques / questions) sur un formulaire d’accueil invités';

    /**
     * Exécute la commande.
     */
    public function handle(GuestInfoFormPdfTemplateService $templateService): int
    {
        $formId = $this->option('form');
        $projectId = $this->option('project');

        if ($formId) {
            $form = GuestInfoForm::query()->find((int) $formId);
            if ($form === null) {
                $this->error('Formulaire introuvable.');

                return self::FAILURE;
            }
        } elseif ($projectId) {
            $project = GuestPastoralProject::query()->find((int) $projectId);
            if ($project === null) {
                $this->error('Projet introuvable.');

                return self::FAILURE;
            }

            $form = $project->form;
            if ($form === null) {
                $plainPassword = Str::password(10, symbols: false);
                $form = new GuestInfoForm([
                    'project_id' => $project->id,
                    'title' => (string) $this->option('title'),
                    'slug' => Str::slug((string) $this->option('title')).'-'.Str::lower(Str::random(4)),
                    'is_published' => false,
                    'intro_html' => 'Merci de renseigner cette fiche afin que nos départements préparent au mieux votre accueil.',
                ]);
                $form->setAccessPasswordPlain($plainPassword);
                $form->save();
                $this->info('Mot de passe département généré : '.$plainPassword);
            }
        } else {
            $this->error('Indiquez --form=ID ou --project=ID.');

            return self::FAILURE;
        }

        $deptIds = $form->project?->departments()->pluck('church_departments.id')->map(fn ($id): int => (int) $id)->all() ?? [];
        $templateService->applyToForm($form, $deptIds);

        $this->info('Template PDF appliqué au formulaire #'.$form->id.' ('.$form->title.').');

        return self::SUCCESS;
    }
}
