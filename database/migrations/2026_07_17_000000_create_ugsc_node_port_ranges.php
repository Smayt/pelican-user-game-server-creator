<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ugsc_node_port_ranges', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('node_id')->unique();
            $table->foreign('node_id')->references('id')->on('nodes')->cascadeOnDelete();
            $table->string('ports');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ugsc_node_port_ranges');
    }
};
