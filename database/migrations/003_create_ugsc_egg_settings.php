<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ugsc_egg_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('egg_id')->unique();
            $table->foreign('egg_id')->references('id')->on('eggs')->cascadeOnDelete();
            $table->unsignedInteger('category_id')->nullable();
            $table->foreign('category_id')->references('id')->on('ugsc_categories')->nullOnDelete();
            $table->boolean('hidden')->default(false);
            $table->boolean('slots_mode')->default(false);
            $table->boolean('popular')->default(false);
            $table->unsignedInteger('ram_base')->default(1024);
            $table->unsignedInteger('ram_max')->default(4096);
            $table->unsignedInteger('cpu_base')->default(100);
            $table->unsignedInteger('cpu_max')->default(200);
            $table->unsignedInteger('disk')->default(10240);
            $table->unsignedInteger('min_players')->default(2);
            $table->unsignedInteger('max_players')->default(10);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ugsc_egg_settings');
    }
};
