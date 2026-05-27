<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

#[Signature('app:view-word-press-sync-state {--clear}')]
#[Description('View or clear the WordPress synchronization state keys.')]
class ViewWordPressSyncState extends Command
{
    public function handle()
    {
        if ($this->option('clear')) {
            Cache::forget('wp_last_execution');
            Cache::forget('wp_last_indexed_posts');
            
            $this->line('WordPress synchronization states successfully cleared.');
            return Command::SUCCESS;
        }

        $lastExecution = Cache::get('wp_last_execution');
        $lastIndexedPosts = Cache::get('wp_last_indexed_posts') ?? [];

        $this->line('=== WORDPRESS INGESTION WORKFLOW STATE ===');
        $this->newLine();

        if ($lastExecution) {
            $carbonDate = Carbon::parse($lastExecution);
            $this->line('Last Execution Pointer (GMT):');
            $this->line("  Raw Payload:    {$lastExecution}");
            $this->line("  Human Readable: " . $carbonDate->diffForHumans() . " ({$carbonDate->timezone('America/Sao_Paulo')->format('d/m/Y H:i:s')} BRT)");
        } else {
            $this->line('[wp_last_execution]: No timestamp registered yet (Never executed or Cache cleared).');
        }

        $this->newLine();

        $this->line('Recently Indexed Post IDs (NOT IN Exclusion List):');
        
        $filteredIds = array_filter($lastIndexedPosts, fn($id) => $id !== 0);

        if (!empty($filteredIds)) {
            foreach ($filteredIds as $id) {
                $this->line("  - Post ID: {$id}");
            }
            $this->line("  Total items tracked in current window: " . count($filteredIds));
        } else {
            $this->line('  [wp_last_indexed_posts]: Empty or defaulting to [0]. No IDs locked in current time window.');
        }

        $this->newLine();
        $this->line('Quick-Tip: To completely reset this tracking data, run: php artisan app:view-word-press-sync-state --clear');
        
        return Command::SUCCESS;
    }
}