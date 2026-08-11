<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('allowed_domains', 'client_tokens');

        Schema::table('client_tokens', function (Blueprint $table) {
            $table->dropUnique('allowed_domains_domain_unique'); 
            $table->dropColumn('domain');
        });

        Schema::create('allowed_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_token_id')
                ->constrained('client_tokens')
                ->onDelete('cascade');
            $table->string('domain');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['client_token_id', 'domain']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allowed_domains');

        Schema::table('client_tokens', function (Blueprint $table) {
            $table->string('domain')->nullable();
        });

        Schema::rename('client_tokens', 'allowed_domains');
    }
};