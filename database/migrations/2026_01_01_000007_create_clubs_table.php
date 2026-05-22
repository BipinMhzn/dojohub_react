<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use \Database\Traits\EditorTrait;

    public function up(): void
    {
        Schema::create('clubs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('abbreviation', 10);
            $table->string('logo_url')->nullable();
            $table->ulid('address_id')->nullable();
            $table->string('phone_number', 20)->nullable();
            $this->editorLogs($table);

            $table->foreign('address_id')->references('id')->on('addresses')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clubs');
    }
};
