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
        Schema::create('clubs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('abbreviation', 10);
            $table->string('logo_url')->nullable();
            $table->string('address')->nullable();
            $table->string('phone_number', 20)->nullable();
            $this->editorLogs($table);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clubs');
    }
};
