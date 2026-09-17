<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('filemaker_staged_records', function (Blueprint $table) {
            $table->id();
            $table->string('import_key');

            // Clé métier FileMaker. Le record-id interne est conservé à titre
            // indicatif seulement : il change si l'enregistrement est recréé.
            $table->string('fm_primary_key');
            $table->string('fm_record_id');
            $table->string('fm_mod_id');
            $table->string('fm_modified_value')->nullable();

            $table->json('payload');

            // Empreinte du payload : permet de ne retoucher les tables métier
            // que pour les enregistrements réellement modifiés.
            $table->string('checksum', 64);

            $table->foreignId('last_seen_run_id')->nullable()
                ->constrained('filemaker_import_runs')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(['import_key', 'fm_primary_key']);

            // Calcul du curseur incrémental (max de la valeur de modification).
            $table->index(['import_key', 'fm_modified_value']);

            // Détection des suppressions après un balayage complet.
            $table->index(['import_key', 'last_seen_run_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('filemaker_staged_records');
    }
};
