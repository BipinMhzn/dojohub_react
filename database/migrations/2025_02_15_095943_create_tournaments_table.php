<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    use \Database\Traits\EditorTrait;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('logo_url')->nullable();
            $table->tinyInteger('level')->unsigned();
            $table->dateTime('start_at');
            $table->dateTime('end_at')->nullable();
            $table->string('weight_category', 50);
            $table->integer('min_weight')->unsigned();
            $table->integer('max_weight')->unsigned();
            $table->string('age_category', 50);
            $table->tinyInteger('min_age')->unsigned();
            $table->tinyInteger('max_age')->unsigned();
            $table->tinyInteger('gender')->unsigned();
            $table->tinyInteger('status')->unsigned();
            $table->ulid('venue_id');
            $this->editorLogs($table);

            $table->foreign('venue_id')->references('id')->on('venues')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
