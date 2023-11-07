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
        Schema::create('addressable_per_metaverse', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('addressableid')->nullable();
            $table->unsignedBigInteger('metaverseid')->nullable();
            $table->timestamps();
            $table->foreign('addressableid')->references('id')->on('addressables')->onDelete('cascade');
            $table->foreign('metaverseid')->references('id')->on('metaverses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addressable_per_metaverse');

        Schema::table('addressable_per_metaverse', function (Blueprint $table) {
            $table->dropForeign(['addressableid']);
            $table->dropForeign(['metaverseid']);
        });
    }
};
