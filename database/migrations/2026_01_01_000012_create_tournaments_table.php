<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use \Database\Traits\EditorTrait;

    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('logo_url')->nullable();
            $table->tinyInteger('level')->unsigned();                        // 1=Local, 2=Regional, 3=National, 4=International
            $table->tinyInteger('status')->unsigned()->default(0);           // 0=Scheduled, 1=InProgress, 2=Completed, 3=Cancelled
            $table->ulid('venue_id');
            $table->ulid('rule_set_id')->nullable();
            $table->string('timezone', 64)->nullable();                      // IANA e.g. "Asia/Kathmandu"
            $table->dateTime('start_at');
            $table->dateTime('end_at')->nullable();
            $this->editorLogs($table);

            $table->foreign('venue_id')->references('id')->on('venues')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('rule_set_id')->references('id')->on('tournament_rule_sets')->onDelete('restrict')->onUpdate('cascade');

            $table->index('venue_id');
            $table->index('status');
            $table->index('start_at');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE tournaments ADD CONSTRAINT chk_tournament_dates CHECK (end_at IS NULL OR start_at <= end_at)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
