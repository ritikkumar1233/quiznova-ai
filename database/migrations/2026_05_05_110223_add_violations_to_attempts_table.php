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
        Schema::table('attempts', function (Blueprint $table) {
            $table->unsignedTinyInteger('violations')
                ->default(0)
                ->after('score')
                ->comment('Fullscreen violation count during exam');
            $table->timestamp('disqualified_at')
                ->nullable()
                ->after('completed_at')
                ->comment('Set when anti-cheating policy disqualifies the attempt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attempts', function (Blueprint $table) {
            $table->dropColumn(['violations', 'disqualified_at']);
        });
    }
};
