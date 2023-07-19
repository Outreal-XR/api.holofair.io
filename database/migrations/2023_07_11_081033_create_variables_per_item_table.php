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
        Schema::create('variables_per_item', function (Blueprint $table) {
            $table->id();
            $table->string('room');
            $table->string('guid');
            $table->string('key');
            $table->json('value');
            $table->unique(['room', 'guid', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variables_per_item');
    }
};
