<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use YacoubAlhaidari\FilamentTour\Concerns\HasTourSteps;

/**
 * Étape de visite guidée Filament Tour pour une ressource / page admin.
 * Personnalise l’affichage selon les droits d’accès de l’utilisateur connecté.
 */
trait ProvidesAdminTourStep
{
    use HasTourSteps;

    /**
     * Identifiant unique de l’étape (attribut data-tour).
     */
    public static function getTourStepId(): ?string
    {
        return str(class_basename(static::class))->kebab()->toString();
    }

    /**
     * Titre affiché dans le guide.
     */
    public static function getTourStepTitle(): ?string
    {
        if (method_exists(static::class, 'getNavigationLabel')) {
            $label = static::getNavigationLabel();
            if (filled($label)) {
                return $label;
            }
        }

        if (method_exists(static::class, 'getModelLabel')) {
            return static::getModelLabel();
        }

        return null;
    }

    /**
     * N’inclut l’étape que si l’utilisateur peut accéder au menu.
     */
    public static function hasTourStep(): bool
    {
        if (empty(static::getTourStepId())) {
            return false;
        }

        if (method_exists(static::class, 'canAccess')) {
            try {
                return (bool) static::canAccess();
            } catch (\Throwable) {
                return false;
            }
        }

        return true;
    }
}
