<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use \Database\Traits\EditorTrait;

    public function up(): void
    {
        Schema::create('tournament_pools', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('bracket_id');
            $table->string('name', 50);     // e.g. "Pool A", "Pool B"
            $this->editorLogs($table);

            $table->foreign('bracket_id')->references('id')->on('tournament_brackets')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_pools');
    }
};
