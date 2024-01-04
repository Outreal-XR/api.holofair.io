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
        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('plan_id')->index();
            $table->date('start_at')->default(now());
            $table->date('end_at')->nullable();
            $table->boolean('is_canceled')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_expired')->default(false);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['plan_id']);
        });
        Schema::dropIfExists('user_subscriptions');
    }
};
