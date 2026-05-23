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
        Schema::create('clubs_coaches', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('club_id');
            $table->ulid('coach_id');
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $this->editorLogs($table);

            $table->foreign('club_id')->references('id')->on('clubs')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('coach_id')->references('id')->on('coaches')->onDelete('restrict')->onUpdate('cascade');

            $table->index(['club_id', 'coach_id']);
            $table->index('coach_id');
            $table->index('club_id');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE clubs_coaches ADD CONSTRAINT chk_cc_dates CHECK (ended_at IS NULL OR started_at <= ended_at)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('clubs_coaches');
    }
};
