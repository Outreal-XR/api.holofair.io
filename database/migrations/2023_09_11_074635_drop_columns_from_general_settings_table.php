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
        if (Schema::hasColumn('general_settings', 'custom_landing_page')) {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->dropColumn(['custom_landing_page']);
            });
        }

        if (Schema::hasColumn('general_settings', 'custom_spawn_point')) {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->dropColumn('custom_spawn_point');
            });
        }

        if (Schema::hasColumn('general_settings', 'spawn_point')) {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->dropColumn('spawn_point');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
