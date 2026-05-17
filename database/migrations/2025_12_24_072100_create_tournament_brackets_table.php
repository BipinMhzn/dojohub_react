<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use \Database\Traits\EditorTrait;

    public function up(): void
    {
        Schema::create('tournament_brackets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tournament_category_id');
            $table->tinyInteger('type')->unsigned();            // 0=Single Elimination, 1=Double Elimination, 2=Pool+Final, 3=Repechage (IJF)
            $table->tinyInteger('seeding_method')->unsigned()->default(0); // 0=Random, 1=Manual, 2=By Rank
            $table->tinyInteger('competitor_count')->unsigned()->nullable(); // set on generation
            $table->tinyInteger('round_count')->unsigned()->nullable();      // set on generation
            $table->tinyInteger('status')->unsigned()->default(0); // 0=Pending, 1=Active, 2=Completed
            $table->dateTime('generated_at')->nullable();
            $this->editorLogs($table);

            $table->foreign('tournament_category_id')->references('id')->on('tournament_categories')->onDelete('cascade')->onUpdate('cascade');

            $table->index('tournament_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_brackets');
    }
};
