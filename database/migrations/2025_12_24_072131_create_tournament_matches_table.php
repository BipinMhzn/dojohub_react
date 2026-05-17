<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use \Database\Traits\EditorTrait;

    public function up(): void
    {
        Schema::create('tournament_matches', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('bracket_id');
            $table->tinyInteger('bracket_section')->unsigned()->default(0); // 0=Main, 1=Repechage, 2=Bronze, 3=Final
            $table->tinyInteger('round_number')->unsigned();
            $table->string('round_name', 50);                               // "Round of 16", "Quarterfinal", "Semifinal", "Final"
            $table->tinyInteger('match_number')->unsigned();
            $table->tinyInteger('mat_number')->unsigned()->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->tinyInteger('status')->unsigned()->default(0);          // 0=Pending, 1=Active, 2=Completed
            $table->ulid('winner_id')->nullable();
            $table->ulid('winner_next_match_id')->nullable();
            $table->tinyInteger('winner_next_position')->nullable();        // 1 or 2
            $table->ulid('loser_next_match_id')->nullable();
            $table->tinyInteger('loser_next_position')->nullable();         // 1 or 2
            $table->text('notes')->nullable();
            $this->editorLogs($table);

            $table->foreign('bracket_id')->references('id')->on('tournament_brackets')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('winner_id')->references('id')->on('players')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('winner_next_match_id')->references('id')->on('tournament_matches')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('loser_next_match_id')->references('id')->on('tournament_matches')->onDelete('restrict')->onUpdate('cascade');

            $table->index(['bracket_id', 'bracket_section', 'round_number', 'match_number'], 'tm_bracket_position_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_matches');
    }
};
