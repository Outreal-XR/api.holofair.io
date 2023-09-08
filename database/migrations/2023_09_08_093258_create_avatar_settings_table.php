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
        Schema::create('avatar_settings', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("metaverse_id")->unsigned();
            $table->foreign("metaverse_id")->references("id")->on("metaverses")->onDelete("cascade");
            $table->boolean("ready_player_me")->default(false);
            $table->boolean("holofair_avatar")->default(false);
            $table->bigInteger("holofair_avatar_id")->nullable();
            $table->boolean("custom_avatar")->default(false);
            $table->string("custom_avatar_url")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avatar_settings');
    }
};
