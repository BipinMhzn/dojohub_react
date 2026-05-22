<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use \Database\Traits\EditorTrait;

    public function up(): void
    {
        Schema::create('referees', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('full_name');
            $table->char('nationality_code', 2)->nullable();
            $table->tinyInteger('certification_level')->unsigned()->nullable(); // 0=Club, 1=National, 2=Continental, 3=International
            $this->editorLogs($table);

            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('nationality_code')->references('code')->on('countries')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referees');
    }
};
