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
        Schema::create('collaborators', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->enum('role', ['owner', 'admin', 'editor', 'viewer'])->default('viewer');
            $table->string('token')->nullable();
            $table->timestamp('token_expiry')->nullable();
            $table->bigInteger('invited_by')->unsigned();
            $table->foreign('invited_by')->references('id')->on('users')->onDelete('cascade');
            $table->bigInteger('metaverse_id')->unsigned();
            $table->foreign('metaverse_id')->references('id')->on('metaverses')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collaborators');
    }
};
