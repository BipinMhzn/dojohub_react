<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use \Database\Traits\EditorTrait;

    public function up(): void
    {
        Schema::create('tournament_results', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('registration_id');
            $table->tinyInteger('position')->unsigned(); // 1=Gold, 2=Silver, 3=Bronze, 5=5th, 7=7th, etc.
            $this->editorLogs($table);

            $table->foreign('registration_id')->references('id')->on('tournament_registrations')->onDelete('restrict')->onUpdate('cascade');

            $table->unique('registration_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_results');
    }
};
