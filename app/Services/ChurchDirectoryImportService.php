<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ChurchCell;
use App\Models\ChurchDepartment;
use App\Models\ChurchExtension;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Import départements / cellules / extensions depuis un fichier Excel (.xlsx / .xls / .csv).
 */
final class ChurchDirectoryImportService
{
    public const TYPE_DEPARTMENTS = 'departements';

    public const TYPE_CELLS = 'cellules';

    public const TYPE_EXTENSIONS = 'extensions';

    /**
     * Types d’import supportés (clé => libellé).
     *
     * @return array<string, string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_DEPARTMENTS => 'Départements',
            self::TYPE_CELLS => 'Cellules',
            self::TYPE_EXTENSIONS => 'Extensions',
        ];
    }

    /**
     * En-têtes de colonnes par type.
     *
     * @return list<string>
     */
    public static function headersFor(string $type): array
    {
        return match ($type) {
            self::TYPE_DEPARTMENTS => ['name', 'color', 'description', 'sort_order'],
            self::TYPE_CELLS => ['name', 'commune', 'day', 'time', 'host', 'description', 'sort_order'],
            self::TYPE_EXTENSIONS => ['name', 'city', 'country', 'address', 'lat', 'lng', 'description', 'leader_name', 'sort_order'],
            default => ['name'],
        };
    }

    /**
     * Lignes d’exemple pour le modèle Excel.
     *
     * @return list<list<string|int|float|null>>
     */
    public static function sampleRowsFor(string $type): array
    {
        return match ($type) {
            self::TYPE_DEPARTMENTS => [
                ['Accueil', '#2563EB', '', 1],
                ['Intercession', '#7C3AED', '', 2],
                ['Sécurité', '#DC2626', '', 3],
            ],
            self::TYPE_CELLS => [
                ['Cellule Gombe', 'Gombe', 'Mardi', '18h00', 'Famille Kabongo', 'Communion et prière', 1],
                ['Cellule Lingwala', 'Lingwala', 'Mercredi', '18h30', 'Famille Mbayo', 'Étude biblique', 2],
            ],
            self::TYPE_EXTENSIONS => [
                ['CMP Siège', 'Kinshasa', 'RD Congo', '4524, Avenue des Forces Armées, Gombe', -4.30545, 15.28672, 'Maison mère CMP', 'Pasteur Ken Luamba', 1],
                ['CMP Lubumbashi', 'Lubumbashi', 'RD Congo', 'Lubumbashi, Haut-Katanga', -11.6876, 27.5026, 'Extension Katanga', '', 2],
            ],
            default => [],
        };
    }

    /**
     * Nom de fichier modèle .xlsx.
     *
     * @param  string  $type  Type d’import
     * @return string
     */
    public static function templateFilename(string $type): string
    {
        return 'cmp-modele-'.$type.'.xlsx';
    }

    /**
     * Télécharge un modèle Excel prérempli.
     *
     * @param  string  $type  departements|cellules|extensions
     */
    public static function downloadTemplate(string $type): StreamedResponse
    {
        $type = self::normalizeTypeKey($type);
        $filename = self::templateFilename($type);
        $spreadsheet = self::buildTemplateSpreadsheet($type);

        return response()->streamDownload(
            static function () use ($spreadsheet): void {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            },
            $filename,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
        );
    }

    /**
     * Importe un fichier Excel / CSV depuis un chemin disque.
     *
     * @param  string  $type  departements|cellules|extensions
     * @param  string  $absolutePath  Chemin absolu du fichier uploadé
     * @return array{success: bool, created: int, updated: int, skipped: int, errors: list<string>, message: string}
     */
    public function importFromPath(string $type, string $absolutePath): array
    {
        $type = self::normalizeTypeKey($type);
        $rows = $this->readSpreadsheetRows($absolutePath);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $index => $cols) {
            $lineNo = $index + 2; // ligne 1 = en-têtes

            try {
                $result = match ($type) {
                    self::TYPE_DEPARTMENTS => $this->upsertDepartment($cols),
                    self::TYPE_CELLS => $this->upsertCell($cols),
                    self::TYPE_EXTENSIONS => $this->upsertExtension($cols),
                    default => 'skipped',
                };

                if ($result === 'created') {
                    $created++;
                } elseif ($result === 'updated') {
                    $updated++;
                } else {
                    $skipped++;
                }
            } catch (Throwable $throwable) {
                $errors[] = "Ligne {$lineNo} : ".$throwable->getMessage();
                $skipped++;
            }
        }

        $label = self::types()[$type] ?? $type;
        $message = sprintf(
            '%s — créés : %d, mis à jour : %d, ignorés : %d%s',
            $label,
            $created,
            $updated,
            $skipped,
            $errors !== [] ? ' (voir erreurs)' : '',
        );

        return [
            'success' => $errors === [] && ($created + $updated) > 0,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'message' => $message,
        ];
    }

    /**
     * Construit le classeur modèle.
     *
     * @param  string  $type  Type d’import
     */
    private static function buildTemplateSpreadsheet(string $type): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(Str::limit(self::types()[$type] ?? $type, 28));

        $headers = self::headersFor($type);
        foreach ($headers as $colIndex => $header) {
            $sheet->setCellValue([$colIndex + 1, 1], $header);
        }

        foreach (self::sampleRowsFor($type) as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValue([$colIndex + 1, $rowIndex + 2], $value);
            }
        }

        foreach (range(1, count($headers)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        $sheet->getStyle('1:1')->getFont()->setBold(true);

        return $spreadsheet;
    }

    /**
     * Lit les lignes de données (sans l’en-tête).
     *
     * @return list<list<string>>
     */
    private function readSpreadsheetRows(string $absolutePath): array
    {
        $spreadsheet = IOFactory::load($absolutePath);
        $sheet = $spreadsheet->getActiveSheet();
        $matrix = $sheet->toArray(null, true, true, false);

        if ($matrix === []) {
            return [];
        }

        // Ignore la première ligne si c’est un en-tête (name / nom)
        $first = $matrix[0] ?? [];
        $firstCell = Str::lower(trim((string) ($first[0] ?? '')));
        if (in_array($firstCell, ['name', 'nom'], true)) {
            array_shift($matrix);
        }

        $rows = [];
        foreach ($matrix as $row) {
            $cols = array_map(
                static fn (mixed $cell): string => trim((string) ($cell ?? '')),
                is_array($row) ? $row : [],
            );

            // Retirer les colonnes vides de fin
            while ($cols !== [] && end($cols) === '') {
                array_pop($cols);
            }

            if ($cols === [] || ($cols[0] ?? '') === '') {
                continue;
            }

            $rows[] = $cols;
        }

        return $rows;
    }

    /**
     * @param  string  $type  Clé brute
     * @return string Clé normalisée
     */
    private static function normalizeTypeKey(string $type): string
    {
        $type = Str::lower(trim($type));

        return isset(self::types()[$type]) ? $type : self::TYPE_DEPARTMENTS;
    }

    /**
     * @param  list<string>  $cols
     * @return 'created'|'updated'|'skipped'
     */
    private function upsertDepartment(array $cols): string
    {
        $name = trim((string) ($cols[0] ?? ''));
        if ($name === '') {
            return 'skipped';
        }

        $slug = Str::slug($name);
        $payload = [
            'name' => $name,
            'color' => $this->colorOrDefault($cols[1] ?? null),
            'description' => $this->nullable($cols[2] ?? null),
            'sort_order' => $this->intOr($cols[3] ?? null, 0),
            'is_active' => true,
        ];

        $existing = ChurchDepartment::query()->where('slug', $slug)->first();
        if ($existing === null) {
            ChurchDepartment::query()->create([...$payload, 'slug' => $slug]);

            return 'created';
        }

        $existing->update($payload);

        return 'updated';
    }

    /**
     * @param  list<string>  $cols
     * @return 'created'|'updated'|'skipped'
     */
    private function upsertCell(array $cols): string
    {
        $name = trim((string) ($cols[0] ?? ''));
        $commune = trim((string) ($cols[1] ?? ''));
        if ($name === '' || $commune === '') {
            throw new \InvalidArgumentException('name et commune sont obligatoires.');
        }

        $slug = Str::slug($name);
        $payload = [
            'name' => $name,
            'commune' => $commune,
            'day' => $this->nullable($cols[2] ?? null),
            'time' => $this->nullable($cols[3] ?? null),
            'host' => $this->nullable($cols[4] ?? null),
            'description' => $this->nullable($cols[5] ?? null),
            'sort_order' => $this->intOr($cols[6] ?? null, 0),
            'is_active' => true,
        ];

        $existing = ChurchCell::query()->where('slug', $slug)->first();
        if ($existing === null) {
            ChurchCell::query()->create([...$payload, 'slug' => $slug]);

            return 'created';
        }

        $existing->update($payload);

        return 'updated';
    }

    /**
     * @param  list<string>  $cols
     * @return 'created'|'updated'|'skipped'
     */
    private function upsertExtension(array $cols): string
    {
        $name = trim((string) ($cols[0] ?? ''));
        $city = trim((string) ($cols[1] ?? ''));
        $country = trim((string) ($cols[2] ?? ''));
        if ($name === '' || $city === '' || $country === '') {
            throw new \InvalidArgumentException('name, city et country sont obligatoires.');
        }

        $payload = [
            'city' => $city,
            'country' => $country,
            'address' => $this->nullable($cols[3] ?? null),
            'lat' => $this->floatOr($cols[4] ?? null, 0.0),
            'lng' => $this->floatOr($cols[5] ?? null, 0.0),
            'description' => $this->nullable($cols[6] ?? null),
            'leader_name' => $this->nullable($cols[7] ?? null),
            'sort_order' => $this->intOr($cols[8] ?? null, 0),
            'is_active' => true,
        ];

        $existing = ChurchExtension::query()->where('name', $name)->first();
        if ($existing === null) {
            ChurchExtension::query()->create([...$payload, 'name' => $name]);

            return 'created';
        }

        $existing->update($payload);

        return 'updated';
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function colorOrDefault(?string $value): string
    {
        $value = trim((string) $value);
        if ($value !== '' && preg_match('/^#[0-9A-Fa-f]{3,8}$/', $value)) {
            return $value;
        }

        return '#7b1d3e';
    }

    private function intOr(?string $value, int $default): int
    {
        $value = trim((string) $value);

        return $value !== '' && is_numeric($value) ? (int) $value : $default;
    }

    private function floatOr(?string $value, float $default): float
    {
        $value = trim((string) $value);

        return $value !== '' && is_numeric($value) ? (float) $value : $default;
    }
}
