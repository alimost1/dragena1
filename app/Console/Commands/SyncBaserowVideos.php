<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Video;
use App\Services\BaserowService;

class SyncBaserowVideos extends Command
{
    protected $signature = 'videos:sync-baserow';
    protected $description = 'Sync video URLs from Baserow';

    public function handle()
    {
        $baserowService = new BaserowService();
        
        $videos = Video::where('status', 'completed')
                      ->whereNull('video_url')
                      ->get();

        $this->info("Found {$videos->count()} videos to sync");

        foreach ($videos as $video) {
            $videoUrl = $baserowService->getVideoUrl($video->id);
            
            if ($videoUrl) {
                $video->update(['video_url' => $videoUrl]);
                $this->info("Updated video {$video->id} with URL: {$videoUrl}");
            } else {
                $this->warn("No URL found for video {$video->id}");
            }
        }

        $this->info('Sync completed!');
    }
}