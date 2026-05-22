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
        Schema::create('tournament_matches', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('bracket_id');
            $table->ulid('pool_id')->nullable();         // set only for pool-stage matches
            $table->ulid('mat_id')->nullable();          // assigned tatami mat
            $table->tinyInteger('bracket_section')->unsigned()->default(0); // 0=Main, 1=Repechage, 2=Bronze, 3=GoldFinal
            $table->tinyInteger('round_number')->unsigned();
            $table->string('round_name', 50);           // "Round of 16", "Quarterfinal", "Semifinal", "Final"
            $table->tinyInteger('match_number')->unsigned();
            $table->tinyInteger('status')->unsigned()->default(0);          // 0=Pending, 1=Active, 2=Completed
            $table->tinyInteger('match_outcome')->unsigned()->nullable();    // 0=Ippon, 1=WazaAri, 2=Decision, 3=HansokuMake, 4=Walkover, 5=Bye, 6=GoldenScore
            $table->boolean('is_golden_score')->default(false);
            $table->integer('golden_score_duration_seconds')->unsigned()->nullable();
            $table->tinyInteger('winner_next_position')->unsigned()->nullable(); // 0=White, 1=Blue — added here; FK added in pass 2
            $table->tinyInteger('loser_next_position')->unsigned()->nullable();  // 0=White, 1=Blue
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->text('notes')->nullable();
            $this->editorLogs($table);

            $table->foreign('bracket_id')->references('id')->on('tournament_brackets')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('pool_id')->references('id')->on('tournament_pools')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('mat_id')->references('id')->on('venue_mats')->onDelete('restrict')->onUpdate('cascade');

            $table->index(['bracket_id', 'bracket_section', 'round_number', 'match_number'], 'tm_bracket_position_index');
            $table->index('status');
            $table->index('scheduled_at');
            $table->index('mat_id');
        });

        DB::statement('ALTER TABLE tournament_matches ADD CONSTRAINT chk_tm_winner_pos CHECK (winner_next_position IS NULL OR winner_next_position IN (0,1))');
        DB::statement('ALTER TABLE tournament_matches ADD CONSTRAINT chk_tm_loser_pos CHECK (loser_next_position IS NULL OR loser_next_position IN (0,1))');
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_matches');
    }
};
