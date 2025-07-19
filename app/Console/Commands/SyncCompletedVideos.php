<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Video;
use App\Services\BaserowService;
use Illuminate\Support\Facades\Log;

class SyncCompletedVideos extends Command
{
    protected $signature = 'videos:sync-completed {--video-id= : Sync specific video ID}';
    protected $description = 'Sync completed videos that are missing URLs from Baserow';

    public function handle()
    {
        $baserowService = new BaserowService();
        
        if ($videoId = $this->option('video-id')) {
            // Sync specific video
            $video = Video::find($videoId);
            if (!$video) {
                $this->error("Video with ID {$videoId} not found.");
                return 1;
            }
            
            $this->syncVideo($video, $baserowService);
        } else {
            // Sync all completed videos without URLs
            $videos = Video::where('status', 'completed')
                          ->whereNull('video_url')
                          ->get();

            $this->info("Found {$videos->count()} completed videos without URLs");

            if ($videos->isEmpty()) {
                $this->info('No videos to sync.');
                return 0;
            }

            $bar = $this->output->createProgressBar($videos->count());
            $bar->start();

            foreach ($videos as $video) {
                $this->syncVideo($video, $baserowService);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
        }

        $this->info('Sync completed!');
        return 0;
    }

    private function syncVideo(Video $video, BaserowService $baserowService): void
    {
        $videoUrl = $baserowService->getVideoUrl($video->id);
        
        if ($videoUrl) {
            $video->update(['video_url' => $videoUrl]);
            $this->info("✓ Updated video {$video->id} with URL: {$videoUrl}");
            Log::info("Synced video URL for video {$video->id}: {$videoUrl}");
        } else {
            $this->warn("✗ No URL found for video {$video->id}");
            Log::warning("No video URL found in Baserow for video {$video->id}");
        }
    }
} 