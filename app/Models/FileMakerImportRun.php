<?php

namespace App\Models;

use App\Enums\FileMakerImportMode;
use App\Enums\FileMakerImportStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Journal d'exécution des imports FileMaker.
 *
 * @property int $id
 * @property string $import_key
 * @property FileMakerImportMode $mode
 * @property FileMakerImportStatus $status
 * @property string|null $batch_id
 * @property string|null $cursor_from
 * @property string|null $cursor_to
 * @property int $records_expected
 * @property int $records_upserted
 * @property int $records_deleted
 * @property int $pages_dispatched
 * @property string|null $error
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'import_key',
    'mode',
    'status',
    'batch_id',
    'cursor_from',
    'cursor_to',
    'records_expected',
    'pages_dispatched',
    'started_at',
])]
class FileMakerImportRun extends Model
{
    protected $table = 'filemaker_import_runs';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mode' => FileMakerImportMode::class,
            'status' => FileMakerImportStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * Dernier run terminé avec succès pour cet import. C'est lui qui porte le
     * curseur de départ du prochain import incrémental.
     */
    public static function lastCompletedFor(string $importKey): ?self
    {
        return self::query()
            ->where('import_key', $importKey)
            ->where('status', FileMakerImportStatus::Completed)
            ->orderByDesc('finished_at')
            ->orderByDesc('id')
            ->first();
    }

    public function markCompleted(?string $cursor, int $deleted = 0): void
    {
        $this->update([
            'status' => FileMakerImportStatus::Completed,
            'cursor_to' => $cursor,
            'records_deleted' => $deleted,
            'finished_at' => now(),
        ]);
    }

    public function markFailed(Throwable|string $error): void
    {
        $this->update([
            'status' => FileMakerImportStatus::Failed,
            'error' => $error instanceof Throwable ? $error->getMessage() : $error,
            'finished_at' => now(),
        ]);
    }
}
