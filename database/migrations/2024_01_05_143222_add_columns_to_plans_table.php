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
        Schema::table('plans', function (Blueprint $table) {
            $table->string('stripe_plan_id')->after('name')->nullable();
            $table->string('description')->after('stripe_plan_id')->unique()->nullable();
            $table->string('lookup_key')->after('description')->unique()->nullable();
            $table->enum('interval', ['month', 'year'])->after('price')->nullable();
            $table->dropColumn('duration_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('stripe_plan_id');
            $table->dropColumn('description');
            $table->dropColumn('lookup_key');
            $table->dropColumn('interval');
            $table->enum('duration_type', ['monthly', 'yearly'])->after('price')->nullable();
        });
    }
};
