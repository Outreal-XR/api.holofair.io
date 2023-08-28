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
        Schema::create('invited_users', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->boolean('is_accepted')->default(false);
            $table->bigInteger('invited_by')->unsigned();
            $table->foreign('invited_by')->references('id')->on('users')->onDelete('cascade');
            $table->bigInteger('metaverse_id')->unsigned();
            $table->foreign('metaverse_id')->references('id')->on('metaverses')->onDelete('cascade');
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_view')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invited_users');
    }
};
