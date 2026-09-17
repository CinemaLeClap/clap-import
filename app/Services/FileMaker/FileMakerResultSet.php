<?php

namespace App\Services\FileMaker;

readonly class FileMakerResultSet
{
    /**
     * @param  int  $totalCount  Nombre d'enregistrements dans l'ensemble trouvé côté FileMaker, pas dans cette page.
     * @param  array<int, FileMakerRecord>  $records
     */
    public function __construct(
        public int $totalCount,
        public array $records,
    ) {}

    public function isEmpty(): bool
    {
        return $this->records === [];
    }

    public function count(): int
    {
        return count($this->records);
    }
}
