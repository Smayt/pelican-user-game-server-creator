<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ugsc_egg_images', function (Blueprint $table) {
            $table->boolean('grid_protected')->default(false);
            $table->boolean('banner_protected')->default(false);
            $table->boolean('list_protected')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('ugsc_egg_images', function (Blueprint $table) {
            $table->dropColumn(['grid_protected', 'banner_protected', 'list_protected']);
        });
    }
};
