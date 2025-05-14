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
        Schema::create('clubs_players', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('club_id');
            $table->ulid('player_id');
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $this->editorLogs($table);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clubs_players');
    }
};
