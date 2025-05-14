<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use \Database\Traits\EditorTrait;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('venues', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('address');
            $table->string('city', 100);
            $table->string('state', 100);
            $table->string('country', 100);
            $this->editorLogs($table);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venues');
    }
};
