<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Supprime les entrées créées pour court-circuiter les migrations vendor sans
 * timestamp (ordre incorrect). Le paquet est désormais chargé sans migrations
 * automatiques via App\Providers\FilamentRecordWatcherServiceProvider.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('migrations')->whereIn('migration', [
            'create_watches_table',
            'create_watch_events_table',
        ])->delete();
    }

    public function down(): void
    {
        //
    }
};
