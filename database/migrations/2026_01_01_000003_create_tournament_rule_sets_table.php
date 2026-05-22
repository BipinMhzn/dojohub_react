<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use \Database\Traits\EditorTrait;

    public function up(): void
    {
        Schema::create('tournament_rule_sets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');                                          // e.g. "IJF 2024", "Local Modified"
            $table->tinyInteger('max_waza_ari')->unsigned()->default(2);
            $table->tinyInteger('max_shido')->unsigned()->default(3);
            $table->integer('match_duration_seconds')->unsigned()->default(240);
            $table->integer('golden_score_seconds')->unsigned()->nullable(); // null = unlimited
            $this->editorLogs($table);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_rule_sets');
    }
};
