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
        Schema::create('tournament_categories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tournament_id');
            $table->string('name')->nullable();             // auto-generated from enum labels if null
            $table->tinyInteger('gender')->unsigned();      // 0=Male, 1=Female
            $table->tinyInteger('age_category')->unsigned();
            $table->tinyInteger('min_age')->unsigned();
            $table->tinyInteger('max_age')->unsigned();
            $table->tinyInteger('weight_category')->unsigned();
            $table->decimal('min_weight', 5, 2);
            $table->decimal('max_weight', 5, 2);
            $table->tinyInteger('status')->unsigned()->default(0); // 0=Pending, 1=Seeding, 2=Active, 3=Completed
            $this->editorLogs($table);

            $table->foreign('tournament_id')->references('id')->on('tournaments')->onDelete('restrict')->onUpdate('cascade');

            $table->index('tournament_id');
            $table->index('status');
            $table->unique(['tournament_id', 'gender', 'age_category', 'weight_category', 'deleted_at'], 'tc_division_unique');
        });

        DB::statement('ALTER TABLE tournament_categories ADD CONSTRAINT chk_tc_age CHECK (min_age <= max_age)');
        DB::statement('ALTER TABLE tournament_categories ADD CONSTRAINT chk_tc_weight CHECK (min_weight <= max_weight)');
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_categories');
    }
};
