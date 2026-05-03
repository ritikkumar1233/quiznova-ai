<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only attempt to enable pgvector when using a Postgres connection.
        $connection = config('database.default');

        if (str_starts_with($connection, 'pgsql') || str_starts_with($connection, 'postgres') || $connection === 'pgsql' || $connection === 'postgres') {
            Schema::ensureVectorExtensionExists();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
