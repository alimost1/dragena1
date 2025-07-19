<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$video = App\Models\Video::find(57);

if ($video) {
    echo "Video 57 found:\n";
    echo "Status: " . $video->status . "\n";
    echo "URL: " . ($video->video_url ?: 'null') . "\n";
    echo "Created: " . $video->created_at . "\n";
    echo "User ID: " . $video->user_id . "\n";
    
    if ($video->video_url) {
        echo "\n✅ Video is ready to play!\n";
    } else {
        echo "\n❌ Video URL is missing\n";
    }
} else {
    echo "Video 57 not found\n";
} 