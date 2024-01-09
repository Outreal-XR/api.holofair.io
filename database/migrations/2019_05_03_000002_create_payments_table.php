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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->decimal('price', 15, 2)->nullable();
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->string('st_customer_id')->nullable();
            $table->string('st_subscription_id')->nullable();
            $table->string('st_payment_intent_id')->nullable();
            $table->string('st_payment_method')->nullable();
            $table->string('st_payment_status')->nullable();
            $table->bigInteger('date')->nullable();
            $table->bigInteger('end_at')->nullable();
            $table->timestamps();

            $table->index(['subscription_id']);
            $table->index(['st_payment_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
