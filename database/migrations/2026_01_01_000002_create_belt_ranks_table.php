<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('belt_ranks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 10)->unique();   // 6K, 5K, … 1K, 1D, … 10D
            $table->string('name');                  // Rokkyu, Gokyu, … Shodan, … Judan
            $table->tinyInteger('kind')->unsigned(); // 0=Kyu, 1=Dan
            $table->string('color', 50)->nullable();
            $table->tinyInteger('sort_order')->unsigned();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('belt_ranks');
    }
};
