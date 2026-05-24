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
            $table->dateTime('registration_open_at')->nullable();
            $table->dateTime('registration_close_at')->nullable();
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->dateTime('weigh_in_start_at')->nullable();
            $table->dateTime('weigh_in_end_at')->nullable();
            $this->editorLogs($table);

            $table->foreign('tournament_id')->references('id')->on('tournaments')->onDelete('restrict')->onUpdate('cascade');

            $table->index('tournament_id');
            $table->index('status');
            $table->index('registration_close_at');
            $table->index('start_at');
            $table->index('weigh_in_start_at');
            $table->unique(['tournament_id', 'gender', 'age_category', 'weight_category', 'deleted_at'], 'tc_division_unique');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE tournament_categories ADD CONSTRAINT chk_tc_age CHECK (min_age <= max_age)');
            DB::statement('ALTER TABLE tournament_categories ADD CONSTRAINT chk_tc_weight CHECK (min_weight <= max_weight)');
            DB::statement('ALTER TABLE tournament_categories ADD CONSTRAINT chk_tc_registration_window CHECK (registration_open_at IS NULL OR registration_close_at IS NULL OR registration_open_at <= registration_close_at)');
            DB::statement('ALTER TABLE tournament_categories ADD CONSTRAINT chk_tc_schedule CHECK (start_at IS NULL OR end_at IS NULL OR start_at <= end_at)');
            DB::statement('ALTER TABLE tournament_categories ADD CONSTRAINT chk_tc_weigh_in_window CHECK (weigh_in_start_at IS NULL OR weigh_in_end_at IS NULL OR weigh_in_start_at <= weigh_in_end_at)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_categories');
    }
};
