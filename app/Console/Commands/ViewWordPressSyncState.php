<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

#[Signature('app:view-word-press-sync-state {--clear}')]
#[Description('Command description')]
class ViewWordPressSyncState extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('clear')) {
            Cache::forget('wp_last_execution');
            Cache::forget('wp_last_indexed_posts');
            
            $this->warn('🔄 WordPress synchronization states successfully cleared!');
            return Command::SUCCESS;
        }

        $lastExecution = Cache::get('wp_last_execution');
        $lastIndexedPosts = Cache::get('wp_last_indexed_posts');

        $this->info('=== WORDPRESS INGESTION WORKFLOW STATE ===');
        $this->newLine();

        // 2. Render Last Execution Timestamp Info
        if ($lastExecution) {
            $carbonDate = Carbon::parse($lastExecution);
            $this->comment('📅 Last Execution Pointer (GMT):');
            $this->line("   Raw Payload:   {$lastExecution}");
            $this->line("   Human Readable: " . $carbonDate->diffForHumans() . " ({$carbonDate->timezone('America/Sao_Paulo')->format('d/m/Y H:i:s')} BRT)");
        } else {
            $this->error('❌ [wp_last_execution]: No timestamp registered yet (Never executed or Cache cleared).');
        }

        $this->newLine();

        // 3. Render Recently Processed Exclusion List Table
        $this->comment('🆔 Recently Indexed Post IDs (NOT IN Exclusion List):');
        if (!empty($lastIndexedPosts) && $lastIndexedPosts !== [0]) {
            // Transform array items into a clean structural array for the native layout renderer
            $tableRows = [];
            foreach ($lastIndexedPosts as $index => $id) {
                if ($id !== 0) {
                    $tableRows[] = ['Index' => $index + 1, 'Post ID' => $id];
                }
            }
            
            // Outputs a clean, responsive ASCII table directly inside your container terminal stdout
            $this->table(['#', 'Post ID Table Reference'], $tableRows);
            $this->line("   Total items tracked in current window: " . count($tableRows));
        } else {
            $this->line('   [wp_last_indexed_posts]: Empty or defaulting to [0]. No IDs locked in current time window.');
        }

        $this->newLine();
        $this->info('💡 Quick-Tip: To completely reset this tracking data, run: php artisan wp:sync-state --clear');
        
        return Command::SUCCESS;
    }
}
