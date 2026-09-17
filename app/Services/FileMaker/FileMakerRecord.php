<?php

namespace App\Services\FileMaker;

/**
 * Un enregistrement tel que renvoyé par le Web Publishing Engine.
 */
readonly class FileMakerRecord
{
    /**
     * @param  array<string, string|array<int, string>>  $fields
     */
    public function __construct(
        public string $recordId,
        public string $modId,
        public array $fields,
    ) {}

    /**
     * Valeur brute d'un champ. Un champ répétitif renvoie un tableau.
     *
     * @return string|array<int, string>|null
     */
    public function field(string $name): string|array|null
    {
        return $this->fields[$name] ?? null;
    }

    /**
     * Valeur scalaire d'un champ, en repliant les chaînes vides sur null.
     */
    public function string(string $name): ?string
    {
        $value = $this->field($name);

        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        return $value === null || $value === '' ? null : $value;
    }
}
