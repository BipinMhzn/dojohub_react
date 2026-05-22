<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use \Database\Traits\EditorTrait;

    public function up(): void
    {
        Schema::create('tournament_registration_coaches', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('registration_id');
            $table->ulid('coach_id');
            $this->editorLogs($table);

            $table->foreign('registration_id')->references('id')->on('tournament_registrations')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('coach_id')->references('id')->on('coaches')->onDelete('restrict')->onUpdate('cascade');

            $table->unique(['registration_id', 'coach_id', 'deleted_at'], 'trc_registration_coach_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_registration_coaches');
    }
};
