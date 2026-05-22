<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use \Database\Traits\EditorTrait;

    public function up(): void
    {
        Schema::create('venue_mats', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('venue_id');
            $table->tinyInteger('mat_number')->unsigned();
            $table->string('display_name')->nullable();  // e.g. "Tatami A", "Mat 1"
            $this->editorLogs($table);

            $table->foreign('venue_id')->references('id')->on('venues')->onDelete('restrict')->onUpdate('cascade');

            $table->unique(['venue_id', 'mat_number', 'deleted_at'], 'vm_venue_mat_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_mats');
    }
};
