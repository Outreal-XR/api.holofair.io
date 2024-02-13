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
        Schema::create('user_dashboard_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->index()->name('user_dashboard_settings_user_id_foreign');
            $table->foreignId('dashboard_setting_id')->constrained('dashboard_settings')->onDelete('cascade')->index()->name('user_dashboard_settings_dashboard_setting_id_foreign');
            $table->text('value');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_dashboard_settings', function (Blueprint $table) {
            $table->dropForeign(['user_dashboard_settings_user_id_foreign']);
            $table->dropForeign(['user_dashboard_settings_dashboard_setting_id_foreign']);
        });
        Schema::dropIfExists('user_dashboard_settings');
    }
};
