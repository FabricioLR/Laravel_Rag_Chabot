<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generation_telemetries', function (Blueprint $table) {
            $table->unsignedInteger('rewrite_prompt_tokens')->nullable()->after('rewritten_query');
            $table->unsignedInteger('rewrite_completion_tokens')->nullable()->after('rewrite_prompt_tokens');
            $table->unsignedInteger('rewrite_total_tokens')->nullable()->after('rewrite_completion_tokens');
            $table->unsignedInteger('rewrite_duration_ms')->nullable()->after('rewrite_total_tokens');
        });
    }

    public function down(): void
    {
        Schema::table('generation_telemetries', function (Blueprint $table) {
            $table->dropColumn([
                'rewrite_prompt_tokens',
                'rewrite_completion_tokens',
                'rewrite_total_tokens',
                'rewrite_duration_ms',
            ]);
        });
    }
};