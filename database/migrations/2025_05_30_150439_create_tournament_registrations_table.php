<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use \Database\Traits\EditorTrait;

    public function up(): void
    {
        Schema::create('tournament_registrations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tournament_category_id');
            $table->ulid('club_id');
            $table->ulid('player_id');
            $table->tinyInteger('player_age')->unsigned();
            $table->decimal('player_weight', 5, 2)->unsigned();
            $table->tinyInteger('status')->unsigned()->default(0); // 0=Registered, 1=Confirmed, 2=Withdrawn
            $this->editorLogs($table);

            $table->foreign('tournament_category_id')->references('id')->on('tournament_categories')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('club_id')->references('id')->on('clubs')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('player_id')->references('id')->on('players')->onDelete('restrict')->onUpdate('cascade');

            $table->unique(['tournament_category_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_registrations');
    }
};
