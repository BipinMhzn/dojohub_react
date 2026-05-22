<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use \Database\Traits\EditorTrait;

    public function up(): void
    {
        Schema::create('tournament_match_competitors', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('match_id');
            $table->ulid('registration_id');
            $table->tinyInteger('position')->unsigned();        // 0=White (Shiro), 1=Blue (Ao)
            $table->tinyInteger('seed')->unsigned()->nullable();
            $table->tinyInteger('score')->unsigned()->nullable();     // Waza-ari count — validated against rule_set.max_waza_ari
            $table->tinyInteger('penalties')->unsigned()->nullable();  // Shido count — validated against rule_set.max_shido
            $table->tinyInteger('result')->unsigned()->default(0);     // 0=Pending, 1=Win, 2=Loss, 3=WalkoverWin, 4=Disqualified
            $this->editorLogs($table);

            $table->foreign('match_id')->references('id')->on('tournament_matches')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('registration_id')->references('id')->on('tournament_registrations')->onDelete('restrict')->onUpdate('cascade');

            $table->unique(['match_id', 'position', 'deleted_at'], 'tmc_match_position_unique');
            $table->unique(['match_id', 'registration_id', 'deleted_at'], 'tmc_match_registration_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_match_competitors');
    }
};
