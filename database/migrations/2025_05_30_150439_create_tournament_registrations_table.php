<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use \Database\Traits\EditorTrait;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tournament_registrations', function (Blueprint $table) {
            $table->id();
            $table->ulid('tournament_id');
            $table->ulid('club_id');
            $table->ulid('player_id');
            $table->integer('player_age')->unsigned();
            $table->decimal('player_weight', 5,2)->unsigned();
            $this->editorLogs($table);

            $table->foreign('tournament_id')->references('id')->on('tournaments')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('club_id')->references('id')->on('clubs')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('player_id')->references('id')->on('players')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_registrations');
    }
};
