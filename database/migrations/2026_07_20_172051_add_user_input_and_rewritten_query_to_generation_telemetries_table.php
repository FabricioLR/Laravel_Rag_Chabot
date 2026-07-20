<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generation_telemetries', function (Blueprint $table) {
            $table->text('user_input')->nullable()->after('conversation_history_id');
            $table->text('rewritten_query')->nullable()->after('user_input');
        });
    }

    public function down(): void
    {
        Schema::table('generation_telemetries', function (Blueprint $table) {
            $table->dropColumn(['user_input', 'rewritten_query']);
        });
    }
};