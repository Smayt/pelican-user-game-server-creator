<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('ugsc_server_permissions');
    }

    public function down(): void
    {
        // Not reversible - table structure was replaced
    }
};
