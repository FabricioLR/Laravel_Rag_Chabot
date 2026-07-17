<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class Category
{
    /**
     * Fetch active categories from the WordPress MySQL instance.
     */
    public function getActiveCategories(int $parent): array
    {
        try {
            $wpTablePrefix = config('database.connections.wordpress.prefix', env('WP_DB_TABLE_PREFIX', 'wp_'));

            $categories = [];
            if ($parent == 0){
                $categories = DB::connection('wordpress')->select("
                    SELECT t.term_id as id, t.name, t.slug, tt.count as post_count
                    FROM {$wpTablePrefix}terms t
                    INNER JOIN {$wpTablePrefix}term_taxonomy tt ON t.term_id = tt.term_id
                    WHERE tt.taxonomy = 'category'
                    AND t.name REGEXP '^[0-9]+ - '
                    ORDER BY t.name ASC
                ");
            } else {
                $categories = DB::connection('wordpress')->select("
                    SELECT t.term_id as id, t.name, t.slug, tt.count as post_count
                    FROM {$wpTablePrefix}terms t
                    INNER JOIN {$wpTablePrefix}term_taxonomy tt ON t.term_id = tt.term_id
                    WHERE tt.taxonomy = 'category'
                    AND t.name REGEXP '^{$parent}\\.[0-9]+ - ' 
                    ORDER BY t.name ASC
                ");
            }

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

    public function getFormatedChildCategories(string $mainCategory, ?string $childCategory = null): string 
    {
        try {
            $wpTablePrefix = config('database.connections.wordpress.prefix', 'wp_');
            
            $bindings = [];
            $filter = "";

            if ($childCategory) {
                $rawCode = explode('-', $childCategory, 2)[0];
                $childCode = str_replace('-', '.', $rawCode);
                
                $filter = "AND t.name LIKE ?";
                $bindings[] = "{$childCode}.%";
            } elseif ($mainCategory && $mainCategory !== 'Geral') {
                $mainCode = trim(explode('-', $mainCategory, 2)[0]);
                
                $filter = "AND t.name LIKE ?";
                $bindings[] = "{$mainCode}.%";
            }

            $categories = DB::connection('wordpress')->select("
                SELECT t.name
                FROM {$wpTablePrefix}terms t
                INNER JOIN {$wpTablePrefix}term_taxonomy tt ON t.term_id = tt.term_id
                WHERE tt.taxonomy = 'category'
                {$filter}
                ORDER BY t.name ASC
            ", $bindings);

            $formated = "";
            foreach ($categories as $item) {
                $formated .= "- " . $item->name . "\n";
            }
            
            return $formated;
        } catch (Exception $e) {
            Log::error('Failed to retrieve WordPress child categories.', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ]);
            
            return "";
        }
    }
}