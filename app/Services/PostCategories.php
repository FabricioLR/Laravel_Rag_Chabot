<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class PostCategories
{
    /**
     * Fetch active categories from the WordPress MySQL instance.
     */
    public function getActiveCategories(): array
    {
        Log::info('Fetching active WordPress taxonomies for chat filtering selection.');

        try {
            $wpTablePrefix = config('database.connections.wordpress.prefix', env('WP_DB_TABLE_PREFIX', 'wp_'));

            $categories = DB::connection('wordpress')->select("
                SELECT t.term_id as id, t.name, t.slug, tt.count as post_count
                FROM {$wpTablePrefix}terms t
                INNER JOIN {$wpTablePrefix}term_taxonomy tt ON t.term_id = tt.term_id
                WHERE tt.taxonomy = 'category'
                  AND tt.count > 0
                ORDER BY t.name ASC
            ");

            return array_map(function ($item) {
                return [
                    'id'    => (int) $item->id,
                    'name'  => $item->name,
                    'value' => $item->slug, 
                    'count' => (int) $item->post_count,
                ];
            }, $categories);

        } catch (Exception $e) {
            Log::error('Failed to retrieve WordPress categories.', [
                'message' => $e->getMessage()
            ]);
            
            return [];
        }
    }
}