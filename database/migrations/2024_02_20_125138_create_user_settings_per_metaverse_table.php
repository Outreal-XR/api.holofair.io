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
        Schema::create('user_settings_per_metaverse', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->index()->name('user_settings_per_metaverse_user_id_foreign');
            $table->foreignId('metaverse_id')->constrained('metaverses')->onDelete('cascade')->index()->name('user_settings_per_metaverse_metaverse_id_foreign');
            $table->foreignId('metaverse_setting_id')->constrained('metaverse_settings')->onDelete('cascade')->index()->name('user_settings_per_metaverse_metaverse_setting_id_foreign');
            $table->string('value');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_settings_per_metaverse');
    }
};
