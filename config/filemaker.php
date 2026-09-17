<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Connexion FileMaker Server (XML Custom Web Publishing)
    |--------------------------------------------------------------------------
    |
    | FileMaker 16 expose le format « fmresultset » sur /fmi/xml/fmresultset.xml.
    | Deux prérequis côté serveur : le XML Web Publishing doit être activé dans
    | la console d'administration, et le compte utilisé doit porter le privilège
    | étendu « fmxml ».
    |
    | La Data API REST de FileMaker 16 n'est volontairement pas utilisée : elle
    | n'était qu'une version d'essai, avec une date d'expiration, remplacée par
    | /fmi/data/v1/ à partir de FileMaker 17.
    |
    */

    'host' => env('FILEMAKER_HOST'),

    'database' => env('FILEMAKER_DATABASE'),

    'username' => env('FILEMAKER_USERNAME'),

    'password' => env('FILEMAKER_PASSWORD'),

    'verify_ssl' => env('FILEMAKER_VERIFY_SSL', true),

    'connect_timeout' => (int) env('FILEMAKER_CONNECT_TIMEOUT', 5),

    'timeout' => (int) env('FILEMAKER_TIMEOUT', 60),

    /*
    |--------------------------------------------------------------------------
    | Taille de page par défaut
    |--------------------------------------------------------------------------
    |
    | Nombre d'enregistrements demandés par requête CWP. Chaque page devient un
    | job en file d'attente. Surchargeable par import.
    |
    */

    'page_size' => (int) env('FILEMAKER_PAGE_SIZE', 500),

    /*
    |--------------------------------------------------------------------------
    | Imports
    |--------------------------------------------------------------------------
    |
    | Un import correspond à un layout FileMaker. Le XML CWP renvoie toujours
    | l'intégralité des champs présents sur le layout : créez un layout dédié ne
    | portant que les champs à importer, c'est le seul moyen de limiter la taille
    | des réponses.
    |
    | primary_key_field  Identifiant stable et unique côté FileMaker. Sert de clé
    |                    d'« upsert » et de tri de pagination. Ne jamais utiliser
    |                    le record-id interne de FileMaker : il change si
    |                    l'enregistrement est recréé.
    |
    | modified_field     Champ texte trié au format « YYYYMMDDHHMMSS », alimenté
    |                    par un calcul FileMaker sur l'horodatage de
    |                    modification. Volontairement du texte et non une date :
    |                    FileMaker interprète les dates d'une recherche selon la
    |                    locale de création du fichier, ce qui fait échouer les
    |                    filtres silencieusement. Mettre à null désactive
    |                    l'import incrémental pour cet import.
    |
    */

    'imports' => [

        'exemple' => [
            'layout' => env('FILEMAKER_LAYOUT_EXEMPLE', 'API_Exemple'),
            'primary_key_field' => 'id',
            'modified_field' => 'zz_modified_sort',
            'page_size' => null,
        ],

    ],

];
