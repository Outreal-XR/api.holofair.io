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
        Schema::create('metaverses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('userid');
            $table->char('uuid', 37)->unique();
            $table->string('slug', 191);
            $table->string('name', 191)->unique();
            $table->text('description')->nullable();
            $table->text('thumbnail')->nullable();
            $table->text('url')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('userid')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metaverses');

        Schema::table('metaverses', function (Blueprint $table) {
            $table->dropForeign(['userid']);
        });
    }
};
