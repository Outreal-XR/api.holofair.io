<?php

use Brick\Math\BigInteger;
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
        Schema::create('metaverse_settings', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("metaverse_id")->unsigned();
            $table->foreign("metaverse_id")->references("id")->on("metaverses")->onDelete("cascade");
            $table->bigInteger("setting_id")->unsigned();
            $table->foreign("setting_id")->references("id")->on("settings")->onDelete("cascade");
            $table->text("value")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("metaverse_settings",  function (Blueprint $table) {
            $table->dropForeign(["metaverse_id"]);
            $table->dropForeign(["setting_id"]);
        });

        Schema::dropIfExists('metaverse_settings');
    }
};
