<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Copie brute d'un enregistrement FileMaker, avant transformation vers les
 * tables métier. Aucune validation n'est appliquée ici : la table de transit
 * doit pouvoir accueillir ce que FileMaker envoie, quel qu'en soit l'état.
 *
 * @property int $id
 * @property string $import_key
 * @property string $fm_primary_key
 * @property string $fm_record_id
 * @property string $fm_mod_id
 * @property string|null $fm_modified_value
 * @property array<string, string|array<int, string>> $payload
 * @property string $checksum
 * @property int|null $last_seen_run_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'import_key',
    'fm_primary_key',
    'fm_record_id',
    'fm_mod_id',
    'fm_modified_value',
    'payload',
    'checksum',
    'last_seen_run_id',
])]
class FileMakerStagedRecord extends Model
{
    protected $table = 'filemaker_staged_records';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    /**
     * @return BelongsTo<FileMakerImportRun, $this>
     */
    public function lastSeenRun(): BelongsTo
    {
        return $this->belongsTo(FileMakerImportRun::class, 'last_seen_run_id');
    }
}
