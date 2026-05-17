<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use \Database\Traits\EditorTrait;

    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('logo_url')->nullable();
            $table->tinyInteger('level')->unsigned();   // 1=Local, 2=Regional, 3=National, 4=International
            $table->dateTime('start_at');
            $table->dateTime('end_at')->nullable();
            $table->tinyInteger('status')->unsigned()->default(0); // 0=Scheduled, 1=In Progress, 2=Completed, 3=Cancelled
            $table->ulid('venue_id');
            $this->editorLogs($table);

            $table->foreign('venue_id')->references('id')->on('venues')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
