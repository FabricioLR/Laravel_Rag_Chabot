<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generation_telemetries', function (Blueprint $table) {
            $table->string('origin')->nullable()->after('conversation_history_id');
        });
    }

    public function down(): void
    {
        Schema::table('generation_telemetries', function (Blueprint $table) {
            $table->dropColumn('origin');
        });
    }
};