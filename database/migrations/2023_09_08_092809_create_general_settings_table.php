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
        Schema::create('general_settings', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("metaverse_id")->unsigned();
            $table->foreign("metaverse_id")->references("id")->on("metaverses")->onDelete("cascade");
            $table->boolean("custom_landing_page")->default(false);
            $table->boolean("allow_public_chat")->default(false);
            $table->boolean("allow_direct_messages")->default(false);
            $table->boolean("allow_direct_calls")->default(false);
            $table->boolean("custom_spawn_point")->default(false);
            $table->string("spawn_point")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_settings');
    }
};
