<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use \Database\Traits\EditorTrait;

    public function up(): void
    {
        Schema::create('tournament_weigh_ins', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('registration_id');
            $table->decimal('weight', 5, 2);
            $table->dateTime('weighed_at');
            $table->unsignedBigInteger('weighed_by')->nullable(); // official who recorded it
            $table->boolean('made_weight');
            $table->string('notes')->nullable();
            $this->editorLogs($table);

            $table->foreign('registration_id')->references('id')->on('tournament_registrations')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('weighed_by')->references('id')->on('users')->onDelete('restrict')->onUpdate('cascade');

            $table->index(['registration_id', 'weighed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_weigh_ins');
    }
};
