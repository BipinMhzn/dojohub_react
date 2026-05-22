<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use \Database\Traits\EditorTrait;

    public function up(): void
    {
        Schema::create('tournament_registration_payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('registration_id');
            $table->decimal('amount_paid', 10, 2);
            $table->char('currency', 3);    // ISO 4217
            $table->dateTime('paid_at')->nullable();
            $table->tinyInteger('method')->unsigned(); // 0=Cash, 1=Bank, 2=Online
            $table->string('reference')->nullable();
            $this->editorLogs($table);

            $table->foreign('registration_id')->references('id')->on('tournament_registrations')->onDelete('restrict')->onUpdate('cascade');

            $table->index('registration_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_registration_payments');
    }
};
