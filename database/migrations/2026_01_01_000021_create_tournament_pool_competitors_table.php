<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use \Database\Traits\EditorTrait;

    public function up(): void
    {
        Schema::create('tournament_pool_competitors', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('pool_id');
            $table->ulid('registration_id');
            $table->tinyInteger('finishing_position')->unsigned()->nullable();
            $this->editorLogs($table);

            $table->foreign('pool_id')->references('id')->on('tournament_pools')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('registration_id')->references('id')->on('tournament_registrations')->onDelete('restrict')->onUpdate('cascade');

            $table->unique(['pool_id', 'registration_id', 'deleted_at'], 'tpc_pool_registration_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_pool_competitors');
    }
};
