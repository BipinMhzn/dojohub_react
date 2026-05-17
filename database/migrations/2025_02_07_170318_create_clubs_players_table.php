<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use \Database\Traits\EditorTrait;

    public function up(): void
    {
        Schema::create('clubs_players', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('club_id');
            $table->ulid('player_id');
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->tinyInteger('belt_rank')->unsigned()->nullable();
            $table->string('grade', 50)->nullable();
            $this->editorLogs($table);

            $table->foreign('club_id')->references('id')->on('clubs')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('player_id')->references('id')->on('players')->onDelete('restrict')->onUpdate('cascade');

            $table->index(['club_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clubs_players');
    }
};
