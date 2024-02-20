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
        Schema::create('languages_per_metaverse', function (Blueprint $table) {
            $table->id();
            $table->foreignId('metaverse_id')->constrained()->onDelete('cascade')->index()->name('languages_per_metaverse_metaverse_id_foreign');
            $table->foreignId('language_id')->constrained()->onDelete('cascade')->index()->name('languages_per_metaverse_language_id_foreign');
            $table->unique(['metaverse_id', 'language_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('languages_per_metaverse');
    }
};
