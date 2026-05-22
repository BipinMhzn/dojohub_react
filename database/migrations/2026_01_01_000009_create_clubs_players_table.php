<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            $table->ulid('belt_rank_id')->nullable();        // rank at time of joining
            $table->string('grade_certificate', 100)->nullable();
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();        // null = currently active
            $this->editorLogs($table);

            $table->foreign('club_id')->references('id')->on('clubs')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('player_id')->references('id')->on('players')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('belt_rank_id')->references('id')->on('belt_ranks')->onDelete('restrict')->onUpdate('cascade');

            $table->index(['club_id', 'player_id']);
            $table->index('player_id');
            $table->index('club_id');
        });

        DB::statement('ALTER TABLE clubs_players ADD CONSTRAINT chk_cp_dates CHECK (ended_at IS NULL OR started_at <= ended_at)');
    }

    public function down(): void
    {
        Schema::dropIfExists('clubs_players');
    }
};
