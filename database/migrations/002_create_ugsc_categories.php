<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ugsc_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Insert default categories
        DB::table('ugsc_categories')->insert([
            ['name' => 'Popular',    'slug' => 'popular',    'icon' => 'tabler-star',          'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'FPS',        'slug' => 'fps',        'icon' => 'tabler-crosshair',      'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sandbox',    'slug' => 'sandbox',    'icon' => 'tabler-building',       'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Survival',   'slug' => 'survival',   'icon' => 'tabler-heart',          'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Simulation', 'slug' => 'simulation', 'icon' => 'tabler-settings',       'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Voice',      'slug' => 'voice',      'icon' => 'tabler-microphone',     'sort_order' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Other',      'slug' => 'other',      'icon' => 'tabler-dots',           'sort_order' => 6, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ugsc_categories');
    }
};
