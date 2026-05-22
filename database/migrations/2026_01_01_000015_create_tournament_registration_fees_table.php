<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use \Database\Traits\EditorTrait;

    public function up(): void
    {
        Schema::create('tournament_registration_fees', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tournament_category_id');
            $table->decimal('amount', 10, 2)->unsigned();
            $table->char('currency', 3);    // ISO 4217 e.g. NPR, USD
            $this->editorLogs($table);

            $table->foreign('tournament_category_id')->references('id')->on('tournament_categories')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_registration_fees');
    }
};
