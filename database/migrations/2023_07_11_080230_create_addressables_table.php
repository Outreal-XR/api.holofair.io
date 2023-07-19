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
        Schema::create('addressables', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 37);
            $table->text('description')->nullable();
            $table->text('thumbnail')->nullable();
            $table->enum('type', ['scenes', 'avatars'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addressables');
    }
};
