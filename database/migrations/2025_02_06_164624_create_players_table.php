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
        Schema::create('players', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->tinyInteger('gender')->unsigned();
            $table->date('dob');
            $table->decimal('weight', 5,2)->unsigned();
            $table->string('profile_photo_url')->nullable();
            $table->string('phone_number', 20)->nullable();
            $this->editorLogs($table);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
