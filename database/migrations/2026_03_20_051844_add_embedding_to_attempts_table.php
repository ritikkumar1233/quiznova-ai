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
        $connection = config('database.default');

        if (str_starts_with($connection, 'pgsql') || str_starts_with($connection, 'postgres') || $connection === 'pgsql' || $connection === 'postgres') {
            Schema::table('attempts', function (Blueprint $table) {
                $table->vector('embedding', dimensions: 1536)->nullable()->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = config('database.default');

        if (str_starts_with($connection, 'pgsql') || str_starts_with($connection, 'postgres') || $connection === 'pgsql' || $connection === 'postgres') {
            Schema::table('attempts', function (Blueprint $table) {
                $table->dropColumn('embedding');
            });
        }
    }
};
