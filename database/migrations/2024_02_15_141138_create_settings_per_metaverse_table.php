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
        Schema::create('settings_per_metaverse', function (Blueprint $table) {
            $table->id();
            $table->foreignId('metaverse_id')->constrained('metaverses')->onDelete('cascade')->index()->name('settings_per_metaverse_metaverse_id_foreign');
            $table->foreignId('metaverse_setting_id')->constrained('metaverse_settings')->onDelete('cascade')->index()->name('settings_per_metaverse_metaverse_setting_id_foreign');
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings_per_metaverse', function (Blueprint $table) {
            $table->dropForeign(['settings_per_metaverse_metaverse_id_foreign']);
            $table->dropForeign(['settings_per_metaverse_metaverse_setting_id_foreign']);
        });
        Schema::dropIfExists('settings_per_metaverse');
    }
};
