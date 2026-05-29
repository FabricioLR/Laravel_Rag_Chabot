<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class Ingestion
{
    public function getIndexedPostsIds(): array
    {
        try {
            $result = DB::connection('pgvector')->select("
                SELECT DISTINCT (metadata->>'source_post_id') AS id 
                FROM vectors 
                WHERE metadata->>'source' = 'wordpress'
            ");

            if (empty($result)) {
                return [];
            }

            $indexedPostsIds = [];

            foreach($result as $item){
                array_push($indexedPostsIds, $item->id);
            }

            return $indexedPostsIds;
        } catch (Exception $e) {
            Log::error('Failed to fetch indexed posts.', [
                'exception' => get_class($e),
                'message'   => $e->getMessage()
            ]);
            
            return [];
        }
    }
}