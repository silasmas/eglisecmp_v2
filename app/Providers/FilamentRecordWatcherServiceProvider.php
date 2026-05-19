<?php

namespace App\Providers;

use ReflectionClass;
use Spatie\LaravelPackageTools\Package;
use Wezlo\FilamentRecordWatcher\FilamentRecordWatcherServiceProvider as VendorFilamentRecordWatcherServiceProvider;

/**
 * Provider de remplacement : pas de migrations auto (ordre vendor incorrect).
 * getPackageBaseDir() est surchargé car un provider sous App\Providers fausserait
 * le chemin des vues/config/lang vers app/ au lieu du paquet vendor.
 */
class FilamentRecordWatcherServiceProvider extends VendorFilamentRecordWatcherServiceProvider
{
    protected function getPackageBaseDir(): string
    {
        return dirname((new ReflectionClass(VendorFilamentRecordWatcherServiceProvider::class))->getFileName());
    }

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasTranslations()
            ->hasViews();
    }
}
