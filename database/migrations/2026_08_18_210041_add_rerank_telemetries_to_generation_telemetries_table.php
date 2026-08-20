<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generation_telemetries', function (Blueprint $table) {
            $table->unsignedInteger('rerank_duration_ms')->nullable();
            $table->unsignedInteger('rerank_total_tokens')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('generation_telemetries', function (Blueprint $table) {
            $table->dropColumn(['rerank_duration_ms', 'rerank_total_tokens']);
        });
    }
};