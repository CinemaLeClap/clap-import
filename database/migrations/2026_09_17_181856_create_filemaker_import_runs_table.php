<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('filemaker_import_runs', function (Blueprint $table) {
            $table->id();
            $table->string('import_key');
            $table->string('mode');
            $table->string('status');
            $table->string('batch_id')->nullable();

            // Curseur incrémental : valeur brute du champ de modification
            // FileMaker, comparée lexicalement. Jamais interprétée comme date.
            $table->string('cursor_from')->nullable();
            $table->string('cursor_to')->nullable();

            $table->unsignedInteger('records_expected')->default(0);
            $table->unsignedInteger('records_upserted')->default(0);
            $table->unsignedInteger('records_deleted')->default(0);
            $table->unsignedInteger('pages_dispatched')->default(0);

            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            // Sert à retrouver le dernier run réussi d'un import, qui porte le
            // curseur de départ du run suivant.
            $table->index(['import_key', 'status', 'finished_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('filemaker_import_runs');
    }
};
