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
        DB::connection("pgvector")->statement('CREATE EXTENSION IF NOT EXISTS vector;');

        if (!Schema::hasTable('vectors')) {
            Schema::connection('pgvector')->create('vectors', function (Blueprint $table) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->text('text');
                $table->jsonb('metadata')->nullable();
                
                $table->vector('embedding', 1024);
                
                $table->timestamps();
            });
        }

        DB::connection("pgvector")->statement("
            CREATE OR REPLACE FUNCTION rrf_score(rank int, rrf_k int DEFAULT 50)
            RETURNS numeric
            LANGUAGE SQL
            IMMUTABLE PARALLEL SAFE
            AS $$
                SELECT COALESCE(1.0 / ($1 + $2), 0.0);
            $$;
        ");

        DB::connection("pgvector")->statement("
            CREATE INDEX IF NOT EXISTS vectors_text_fts_idx ON vectors 
            USING GIN (to_tsvector('portuguese', text));
        ");
        
        DB::connection("pgvector")->statement("
            CREATE INDEX IF NOT EXISTS vectors_embedding_hnsw_idx ON vectors 
            USING hnsw (embedding vector_cosine_ops) 
            WITH (ef_construction = 256);
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::connection("pgvector")->statement('DROP FUNCTION IF EXISTS rrf_score(int, int);');
        Schema::connection('pgvector')->dropIfExists('vectors');
    }
};
