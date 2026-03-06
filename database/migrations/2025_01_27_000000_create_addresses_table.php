<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use \Database\Traits\EditorTrait;

    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('address');
            $table->string('city', 100);
            $table->string('state', 100);
            $table->string('country', 100);
            $this->editorLogs($table);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
