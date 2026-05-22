<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pass 2: adds the three columns that could not be created in the initial tournament_matches migration
// because they either self-reference tournament_matches or reference tournament_match_competitors,
// which did not yet exist at that point.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournament_matches', function (Blueprint $table) {
            $table->ulid('winning_competitor_id')->nullable()->after('is_golden_score');
            $table->ulid('winner_next_match_id')->nullable()->after('winning_competitor_id');
            $table->ulid('loser_next_match_id')->nullable()->after('winner_next_match_id');

            $table->foreign('winning_competitor_id')->references('id')->on('tournament_match_competitors')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('winner_next_match_id')->references('id')->on('tournament_matches')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('loser_next_match_id')->references('id')->on('tournament_matches')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('tournament_matches', function (Blueprint $table) {
            $table->dropForeign(['winning_competitor_id']);
            $table->dropForeign(['winner_next_match_id']);
            $table->dropForeign(['loser_next_match_id']);
            $table->dropColumn(['winning_competitor_id', 'winner_next_match_id', 'loser_next_match_id']);
        });
    }
};
