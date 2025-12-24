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
        Schema::create('tournament_matches', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tournament_id');

            // Match Hierarchy
            $table->string('bracket_type', 20)->default('main'); // main, losers (for double elim)
            $table->integer('round_number')->unsigned();
            $table->string('round_name', 50);
            $table->integer('match_order')->unsigned(); // Position within round

            // Participants (nullable for TBD/bye scenarios)
            $table->ulid('player1_id')->nullable();
            $table->ulid('player2_id')->nullable();
            $table->integer('player1_seed')->nullable();
            $table->integer('player2_seed')->nullable();

            // Results
            $table->ulid('winner_id')->nullable();
            $table->integer('player1_score')->nullable();
            $table->integer('player2_score')->nullable();

            // Match Scheduling
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->tinyInteger('status')->unsigned()->default(0); // PENDING, ONGOING, COMPLETED

            // Progression (where winner/loser advance to)
            $table->ulid('winner_next_match_id')->nullable();
            $table->ulid('loser_next_match_id')->nullable(); // For double elimination
            $table->tinyInteger('winner_position')->nullable(); // 1 or 2 (player1 or player2 slot)
            $table->tinyInteger('loser_position')->nullable(); // For double elimination

            // Additional metadata
            $table->text('notes')->nullable();

            $this->editorLogs($table);

            // Foreign keys
            $table->foreign('tournament_id')->references('id')->on('tournaments')->onDelete('cascade');
            $table->foreign('player1_id')->references('id')->on('players')->onDelete('set null');
            $table->foreign('player2_id')->references('id')->on('players')->onDelete('set null');
            $table->foreign('winner_id')->references('id')->on('players')->onDelete('set null');
            $table->foreign('winner_next_match_id')->references('id')->on('tournament_matches')->onDelete('set null');
            $table->foreign('loser_next_match_id')->references('id')->on('tournament_matches')->onDelete('set null');

            // Indexes
            $table->index(['tournament_id', 'round_number', 'match_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_matches');
    }
};
