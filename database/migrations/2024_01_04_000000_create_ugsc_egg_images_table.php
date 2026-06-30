<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ugsc_egg_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('egg_id')->unique();
            $table->unsignedInteger('steam_app_id')->nullable();
            $table->string('grid_path')->nullable();
            $table->string('banner_path')->nullable();
            $table->string('list_path')->nullable();
            $table->timestamps();

            $table->foreign('egg_id')->references('id')->on('eggs')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ugsc_egg_images');
    }
};
