<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Video;
use App\Services\BaserowService;
use Illuminate\Support\Facades\Log;
use Exception;

class SyncCompletedVideos extends Command
{
    protected $signature = 'videos:sync-completed 
                            {--video-id= : Sync specific video ID}
                            {--first-row : Use video URL from first row for all videos}
                            {--force : Force sync even if video already has URL}
                            {--dry-run : Show what would be synced without making changes}';
    
    protected $description = 'Sync completed videos that are missing URLs from Baserow';

    private BaserowService $baserowService;

    public function handle()
    {
        try {
            $this->baserowService = new BaserowService();
        } catch (Exception $e) {
            $this->error("Failed to initialize Baserow service: " . $e->getMessage());
            return 1;
        }
        
        $isDryRun = $this->option('dry-run');
        $useFirstRow = $this->option('first-row');
        $force = $this->option('force');
        
        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }
        
        if ($videoId = $this->option('video-id')) {
            return $this->syncSpecificVideo($videoId, $isDryRun, $useFirstRow, $force);
        } else {
            return $this->syncAllVideos($isDryRun, $useFirstRow, $force);
        }
    }

    private function syncSpecificVideo(int $videoId, bool $isDryRun, bool $useFirstRow, bool $force): int
    {
        $video = Video::find($videoId);
        if (!$video) {
            $this->error("Video with ID {$videoId} not found.");
            return 1;
        }
        
        $this->info("Syncing video ID: {$videoId}");
        $result = $this->syncVideo($video, $isDryRun, $useFirstRow, $force);
        
        if ($result) {
            $this->info('✅ Sync completed successfully!');
            return 0;
        } else {
            $this->error('❌ Sync failed!');
            return 1;
        }
    }

    private function syncAllVideos(bool $isDryRun, bool $useFirstRow, bool $force): int
    {
        // First, let's check what videos exist in the database
        $this->displayDatabaseStats();
        
        // Build query based on options
        $query = Video::where('status', 'completed');
        
        if (!$force) {
            $query->whereNull('video_url');
        }
        
        $videos = $query->get();

        $this->info("Found {$videos->count()} videos to sync with current filters");

        if ($videos->isEmpty()) {
            $this->warn('No videos to sync with current criteria.');
            $this->suggestAlternativeOptions();
            return 0;
        }

        $bar = $this->output->createProgressBar($videos->count());
        $bar->start();

        $successCount = 0;
        $failureCount = 0;

        foreach ($videos as $video) {
            if ($this->syncVideo($video, $isDryRun, $useFirstRow, $force)) {
                $successCount++;
            } else {
                $failureCount++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        
        $this->info("✅ Successfully synced: {$successCount}");
        if ($failureCount > 0) {
            $this->warn("❌ Failed to sync: {$failureCount}");
        }
        
        $this->info('Sync completed!');
        return $failureCount > 0 ? 1 : 0;
    }

    private function displayDatabaseStats(): void
    {
        $this->line('📊 <fg=cyan>Database Statistics:</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        $totalVideos = Video::count();
        $completedVideos = Video::where('status', 'completed')->count();
        $videosWithUrls = Video::whereNotNull('video_url')->count();
        $completedWithoutUrls = Video::where('status', 'completed')->whereNull('video_url')->count();
        
        $this->line("📹 Total videos: <fg=yellow>{$totalVideos}</fg=yellow>");
        $this->line("✅ Completed videos: <fg=green>{$completedVideos}</fg=green>");
        $this->line("🔗 Videos with URLs: <fg=blue>{$videosWithUrls}</fg=blue>");
        $this->line("❌ Completed without URLs: <fg=red>{$completedWithoutUrls}</fg=red>");
        
        // Show status breakdown
        $statusCounts = Video::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
            
        if (!empty($statusCounts)) {
            $this->newLine();
            $this->line('📈 <fg=cyan>Status Breakdown:</fg=cyan>');
            foreach ($statusCounts as $status => $count) {
                $this->line("   • {$status}: {$count}");
            }
        }
        
        $this->newLine();
    }

    private function suggestAlternativeOptions(): void
    {
        $this->newLine();
        $this->line('💡 <fg=cyan>Suggestions:</fg=cyan>');
        
        $totalCompleted = Video::where('status', 'completed')->count();
        $completedWithUrls = Video::where('status', 'completed')->whereNotNull('video_url')->count();
        
        if ($totalCompleted > 0 && $completedWithUrls > 0) {
            $this->line('   • Use <fg=yellow>--force</fg=yellow> to sync videos that already have URLs');
            $this->line('   • Command: <fg=green>php artisan videos:sync-completed --force --dry-run</fg=green>');
        }
        
        if ($totalCompleted === 0) {
            $this->line('   • No completed videos found. Check if video status is correct.');
            $this->line('   • Use: <fg=green>php artisan videos:debug-status</fg=green> (if available)');
        }
        
        $this->line('   • Use <fg=yellow>--first-row</fg=yellow> to assign first row URL to all videos');
        $this->line('   • Test Baserow connection: <fg=green>php artisan baserow:analyze --first-row</fg=green>');
        
        $this->newLine();
    }

    private function syncVideo(Video $video, bool $isDryRun, bool $useFirstRow, bool $force): bool
    {
        try {
            // Skip if video already has URL and not forcing
            if (!$force && $video->video_url) {
                $this->line("⏭️  Video {$video->id} already has URL: {$video->video_url}");
                return true;
            }

            $videoUrl = null;
            
            if ($useFirstRow) {
                $videoUrl = $this->baserowService->getVideoUrlFromFirstRow();
                $source = "first row";
            } else {
                $videoUrl = $this->baserowService->getVideoUrl($video->id);
                $source = "video ID {$video->id}";
            }
            
            if ($videoUrl) {
                if ($isDryRun) {
                    $this->info("🔍 Would update video {$video->id} with URL from {$source}: {$videoUrl}");
                } else {
                    $video->update(['video_url' => $videoUrl]);
                    $this->info("✅ Updated video {$video->id} with URL from {$source}: {$videoUrl}");
                    Log::info("Synced video URL for video {$video->id}: {$videoUrl}");
                }
                return true;
            } else {
                $this->warn("❌ No URL found for video {$video->id} in Baserow");
                Log::warning("No video URL found in Baserow for video {$video->id}");
                return false;
            }
            
        } catch (Exception $e) {
            $this->error("❌ Error syncing video {$video->id}: " . $e->getMessage());
            Log::error("Error syncing video {$video->id}: " . $e->getMessage());
            return false;
        }
    }
}