<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generation_telemetries', function (Blueprint $table) {
            $table->id();
            
            // Connects directly to our main conversation record
            $table->foreignId('conversation_history_id')
                  ->constrained('conversation_histories')
                  ->onDelete('cascade');

            // LLM Configuration & Parameters
            $table->string('model');
            $table->decimal('temperature', 3, 2);
            $table->unsignedInteger('max_tokens');

            // Categorization & Metadata
            $table->string('main_category')->nullable();
            $table->string('child_category')->nullable();

            // Prompts (Using mediumText in case of massive context injection)
            $table->mediumText('system_prompt');
            $table->mediumText('compiled_prompt');

            // Token Breakdown
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);

            // Performance Latency (Milliseconds)
            $table->unsignedInteger('llm_duration_ms');
            $table->unsignedInteger('embedding_duration_ms')->nullable();
            $table->unsignedInteger('database_duration_ms')->nullable();
            $table->unsignedInteger('total_duration_ms');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generation_telemetries');
    }
};