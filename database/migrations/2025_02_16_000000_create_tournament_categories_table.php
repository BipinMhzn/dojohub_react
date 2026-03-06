<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use \Database\Traits\EditorTrait;

    public function up(): void
    {
        Schema::create('tournament_categories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tournament_id');
            $table->string('name')->nullable(); // auto-generated if null: "{gender} {age_category} {weight_category}"
            $table->tinyInteger('gender')->unsigned();          // 0=Male, 1=Female
            $table->tinyInteger('age_category')->unsigned();    // See Age Category enum
            $table->tinyInteger('min_age')->unsigned();
            $table->tinyInteger('max_age')->unsigned();
            $table->tinyInteger('weight_category')->unsigned(); // See Weight Category enum
            $table->integer('min_weight')->unsigned();
            $table->integer('max_weight')->unsigned();
            $table->tinyInteger('status')->unsigned()->default(0); // 0=Pending, 1=Seeding, 2=Active, 3=Completed
            $this->editorLogs($table);

            $table->foreign('tournament_id')->references('id')->on('tournaments')->onDelete('cascade')->onUpdate('cascade');

            $table->index('tournament_id');
            $table->unique(['tournament_id', 'gender', 'age_category', 'weight_category'], 'tc_division_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_categories');
    }
};
