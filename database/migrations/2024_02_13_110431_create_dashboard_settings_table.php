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
        Schema::create('dashboard_settings', function (Blueprint $table) {
            $table->id();
            $table->enum('category', ['privacy', 'notifications']);
            $table->enum('type', ['checkbox', 'text', 'number', 'date', 'time', 'datetime', 'file', 'email', 'phone']);
            $table->string('name')->unique();
            $table->string('display_name');
            $table->string('description')->nullable();
            $table->text('default_value');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dashboard_settings');
    }
};
