<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use \Database\Traits\EditorTrait;

    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->tinyInteger('gender')->unsigned();           // 0=Male, 1=Female
            $table->date('dob');
            $table->decimal('weight', 5, 2);
            $table->char('nationality_code', 2)->nullable();
            $table->ulid('current_belt_rank_id')->nullable();
            $table->ulid('address_id')->nullable();
            $table->string('email')->nullable();
            $table->string('profile_photo_url')->nullable();
            $table->string('phone_number', 20)->nullable();
            $this->editorLogs($table);

            $table->foreign('nationality_code')->references('code')->on('countries')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('current_belt_rank_id')->references('id')->on('belt_ranks')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('address_id')->references('id')->on('addresses')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
