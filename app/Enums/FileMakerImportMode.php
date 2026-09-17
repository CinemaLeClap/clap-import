<?php

namespace App\Enums;

enum FileMakerImportMode: string
{
    /**
     * Ne rapatrie que les enregistrements modifiés depuis le curseur.
     */
    case Delta = 'delta';

    /**
     * Balaie tout le layout, ce qui permet de détecter les suppressions.
     */
    case Full = 'full';
}
