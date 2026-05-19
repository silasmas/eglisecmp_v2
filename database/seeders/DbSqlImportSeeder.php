<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DbSqlImportSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('db.sql');

        if (! File::exists($path)) {
            $this->command?->warn('Le fichier database/db.sql est introuvable, import ignore.');

            return;
        }

        $content = File::get($path);

        preg_match_all('/INSERT INTO `([^`]+)` .*?;(\r?\n|$)/s', $content, $matches, PREG_SET_ORDER);

        if (empty($matches)) {
            $this->command?->warn('Aucune instruction INSERT INTO detectee dans database/db.sql.');

            return;
        }

        $statementsByTable = [];
        foreach ($matches as $match) {
            $table = $match[1];
            $sql = trim($match[0]);
            $statementsByTable[$table][] = $sql;
        }

        $driver = DB::getDriverName();

        try {
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
            }

            DB::transaction(function () use ($statementsByTable): void {
                foreach ($statementsByTable as $table => $statements) {
                    if (! Schema::hasTable($table)) {
                        continue;
                    }

                    $hasRows = DB::table($table)->exists();
                    if ($hasRows) {
                        continue;
                    }

                    foreach ($statements as $statement) {
                        DB::unprepared($statement);
                    }
                }
            });
        } catch (Throwable $exception) {
            throw $exception;
        } finally {
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        }

        $this->command?->info('Import de base depuis database/db.sql termine.');
    }
}
