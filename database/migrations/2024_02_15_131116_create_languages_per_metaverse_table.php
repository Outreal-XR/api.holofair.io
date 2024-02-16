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
            $table->string('name')->unique();
            $table->unsignedBigInteger('metaverse_id');
            $table->foreign('metaverse_id')->references('id')->on('metaverses')->onDelete('cascade')->name('languages_per_metaverse_metaverse_id_foreign');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('languages_per_metaverse', function (Blueprint $table) {
            $table->dropForeign('languages_per_metaverse_metaverse_id_foreign');
        });
        Schema::dropIfExists('languages_per_metaverse');
    }
};
