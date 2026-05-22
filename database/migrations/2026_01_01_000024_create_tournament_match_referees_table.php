<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use \Database\Traits\EditorTrait;

    public function up(): void
    {
        Schema::create('tournament_match_referees', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('match_id');
            $table->ulid('referee_id');
            $table->tinyInteger('role')->unsigned(); // 0=Center, 1=Side, 2=Video
            $this->editorLogs($table);

            $table->foreign('match_id')->references('id')->on('tournament_matches')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('referee_id')->references('id')->on('referees')->onDelete('restrict')->onUpdate('cascade');

            $table->unique(['match_id', 'role', 'deleted_at'], 'tmr_match_role_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_match_referees');
    }
};
