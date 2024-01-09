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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained();
            $table->foreignId('payment_id')->nullable()->constrained('payments');
            $table->foreignId('user_id')->constrained();
            $table->string('session_id')->unique();
            $table->string('status');
            $table->timestamps();

            $table->index(['payment_id']);
            $table->index(['user_id']);
            $table->index(['plan_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
