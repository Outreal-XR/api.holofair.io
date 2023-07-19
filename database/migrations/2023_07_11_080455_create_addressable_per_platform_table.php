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
        Schema::create('addressable_per_platform', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('addressableid')->nullable();
            $table->unsignedBigInteger('platformid')->nullable();
            $table->text('url')->nullable();
            $table->timestamps();

            $table->foreign('addressableid')->references('id')->on('addressables');
            $table->foreign('platformid')->references('id')->on('platforms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addressable_per_platform');

        Schema::table('addressable_per_platform', function (Blueprint $table) {
            $table->dropForeign(['addressableid']);
            $table->dropForeign(['platformid']);
        });
    }
};
