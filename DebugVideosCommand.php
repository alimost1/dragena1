<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Video;
use App\Services\BaserowService;
use Exception;

class DebugVideosCommand extends Command
{
    protected $signature = 'videos:debug 
                            {--limit=10 : Number of videos to show}
                            {--status= : Filter by specific status}
                            {--with-urls : Show only videos with URLs}
                            {--without-urls : Show only videos without URLs}
                            {--test-baserow : Test Baserow connection}';
    
    protected $description = 'Debug video data and sync issues';

    public function handle()
    {
        $this->displayHeader();
        
        // Test Baserow connection if requested
        if ($this->option('test-baserow')) {
            $this->testBaserowConnection();
            $this->newLine();
        }
        
        $this->displayDatabaseOverview();
        $this->displayVideoSamples();
        $this->displaySyncRecommendations();
        
        return 0;
    }

    private function displayHeader(): void
    {
        $this->line('🐛 <fg=cyan>Video Sync Debug Information</fg=cyan>');
        $this->line('═══════════════════════════════════════════════════════════════');
        $this->newLine();
    }

    private function testBaserowConnection(): void
    {
        $this->line('🔌 <fg=yellow>Testing Baserow Connection...</fg=yellow>');
        
        try {
            $baserowService = new BaserowService();
            
            // Test getting first row
            $firstRowUrl = $baserowService->getVideoUrlFromFirstRow();
            
            if ($firstRowUrl) {
                $this->line("✅ Baserow connection successful!");
                $this->line("🎬 First row video URL: <fg=green>{$firstRowUrl}</fg=green>");
            } else {
                $this->line("⚠️  Baserow connected but no video URL found in first row");
            }
            
            // Test getting all completed videos
            $completedVideos = $baserowService->getAllCompletedVideos();
            $this->line("📊 Found <fg=blue>" . count($completedVideos) . "</fg=blue> rows with video URLs in Baserow");
            
        } catch (Exception $e) {
            $this->line("❌ Baserow connection failed: <fg=red>{$e->getMessage()}</fg=red>");
            $this->suggestBaserowFixes();
        }
    }

    private function displayDatabaseOverview(): void
    {
        $this->line('📊 <fg=cyan>Database Overview</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        $totalVideos = Video::count();
        $this->line("📹 Total videos in database: <fg=yellow>{$totalVideos}</fg=yellow>");
        
        if ($totalVideos === 0) {
            $this->line("❌ <fg=red>No videos found in database!</fg=red>");
            $this->line("   Make sure videos are being created properly.");
            return;
        }
        
        // Status breakdown
        $statusCounts = Video::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
            
        $this->newLine();
        $this->line('📈 <fg=cyan>Status Breakdown:</fg=cyan>');
        foreach ($statusCounts as $status => $count) {
            $color = $this->getStatusColor($status);
            $this->line("   • <fg={$color}>{$status}</fg={$color}>: {$count}");
        }
        
        // URL status
        $this->newLine();
        $videosWithUrls = Video::whereNotNull('video_url')->count();
        $videosWithoutUrls = Video::whereNull('video_url')->count();
        
        $this->line('🔗 <fg=cyan>URL Status:</fg=cyan>');
        $this->line("   • With URLs: <fg=green>{$videosWithUrls}</fg=green>");
        $this->line("   • Without URLs: <fg=red>{$videosWithoutUrls}</fg=red>");
        
        $this->newLine();
    }

    private function displayVideoSamples(): void
    {
        $this->line('🔍 <fg=cyan>Video Samples</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        $limit = (int) $this->option('limit');
        $status = $this->option('status');
        $withUrls = $this->option('with-urls');
        $withoutUrls = $this->option('without-urls');
        
        $query = Video::query();
        
        if ($status) {
            $query->where('status', $status);
        }
        
        if ($withUrls) {
            $query->whereNotNull('video_url');
        } elseif ($withoutUrls) {
            $query->whereNull('video_url');
        }
        
        $videos = $query->orderBy('created_at', 'desc')->limit($limit)->get();
        
        if ($videos->isEmpty()) {
            $this->line("❌ No videos found with current filters");
            return;
        }
        
        $headers = ['ID', 'Status', 'Has URL', 'URL Preview', 'Created'];
        $rows = [];
        
        foreach ($videos as $video) {
            $urlPreview = $video->video_url 
                ? (strlen($video->video_url) > 40 ? substr($video->video_url, 0, 37) . '...' : $video->video_url)
                : 'None';
                
            $rows[] = [
                $video->id,
                $this->colorizeStatus($video->status),
                $video->video_url ? '✅' : '❌',
                $urlPreview,
                $video->created_at->format('Y-m-d H:i')
            ];
        }
        
        $this->table($headers, $rows);
        $this->newLine();
    }

    private function displaySyncRecommendations(): void
    {
        $this->line('💡 <fg=cyan>Sync Recommendations</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        $completedWithoutUrls = Video::where('status', 'completed')->whereNull('video_url')->count();
        $completedWithUrls = Video::where('status', 'completed')->whereNotNull('video_url')->count();
        $totalCompleted = Video::where('status', 'completed')->count();
        
        if ($completedWithoutUrls > 0) {
            $this->line("✅ Ready to sync: <fg=green>{$completedWithoutUrls}</fg=green> completed videos without URLs");
            $this->line("   Command: <fg=yellow>php artisan videos:sync-completed</fg=yellow>");
        }
        
        if ($completedWithUrls > 0) {
            $this->line("🔄 Force sync available: <fg=blue>{$completedWithUrls}</fg=blue> completed videos with existing URLs");
            $this->line("   Command: <fg=yellow>php artisan videos:sync-completed --force</fg=yellow>");
        }
        
        if ($totalCompleted === 0) {
            $this->line("⚠️  No completed videos found. Possible issues:");
            $this->line("   • Videos might have different status values");
            $this->line("   • Videos might still be processing");
            $this->line("   • Check your video creation/update logic");
        }
        
        $this->newLine();
        $this->line('🧪 <fg=cyan>Testing Commands:</fg=cyan>');
        $this->line("   • Test Baserow: <fg=yellow>php artisan baserow:analyze --first-row</fg=yellow>");
        $this->line("   • Dry run sync: <fg=yellow>php artisan videos:sync-completed --dry-run</fg=yellow>");
        $this->line("   • Sync specific video: <fg=yellow>php artisan videos:sync-completed --video-id=123</fg=yellow>");
        $this->line("   • Use first row for all: <fg=yellow>php artisan videos:sync-completed --first-row --dry-run</fg=yellow>");
    }

    private function suggestBaserowFixes(): void
    {
        $this->newLine();
        $this->line('🔧 <fg=yellow>Baserow Configuration Checklist:</fg=yellow>');
        $this->line('   1. Check .env file has: BASEROW_TOKEN, BASEROW_TABLE_ID');
        $this->line('   2. Verify Baserow API token has proper permissions');
        $this->line('   3. Confirm table ID is correct');
        $this->line('   4. Check if "Final Video URL" column exists in your table');
        $this->line('   5. Ensure Baserow API is accessible from your server');
    }

    private function getStatusColor(string $status): string
    {
        return match (strtolower($status)) {
            'completed' => 'green',
            'processing', 'pending' => 'yellow',
            'failed', 'error' => 'red',
            default => 'white'
        };
    }

    private function colorizeStatus(string $status): string
    {
        $color = $this->getStatusColor($status);
        return "<fg={$color}>{$status}</fg={$color}>";
    }
}