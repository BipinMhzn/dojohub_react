<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use \Database\Traits\EditorTrait;

    public function up(): void
    {
        Schema::create('tournament_match_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('match_competitor_id');
            $table->tinyInteger('event_type')->unsigned(); // 0=Ippon, 1=WazaAri, 2=Shido, 3=HansokuMake, 4=HansokuMakeLight, 5=Kinsa, 6=Note
            $table->integer('elapsed_seconds')->unsigned();
            $table->dateTime('occurred_at');
            $table->string('notes')->nullable();
            $this->editorLogs($table);

            $table->foreign('match_competitor_id')->references('id')->on('tournament_match_competitors')->onDelete('restrict')->onUpdate('cascade');

            $table->index(['match_competitor_id', 'elapsed_seconds'], 'tme_competitor_elapsed_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_match_events');
    }
};
