<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('event_team', function (Blueprint $table) {
        $table->id();
        $table->foreignId('event_id')->constrained()->cascadeOnDelete();
        $table->foreignId('team_id')->constrained()->cascadeOnDelete();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_team');
    }
};
