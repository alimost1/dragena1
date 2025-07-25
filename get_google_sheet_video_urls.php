<?php

// 1. Fetch the last video URL from the public Google Sheet
// Build the CSV export URL
$url = "https://docs.google.com/spreadsheets/d/e/2PACX-1vSgp2rASIP_Fz24uCeKQtjkxPMwZ5w7mQ4S2EVwVP60wIj154Hs-nYxwH6hSR0gq-Cr5GXaDAUZs2nV/pub?gid=0&single=true&output=csv";

function fetch_csv($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // <--- Add this line
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

$csv = fetch_csv($url);

if ($csv === false) {
    die('Failed to fetch Google Sheet. Check the Sheet ID and sharing settings.');
}

// Parse CSV rows
$rows = array_map('str_getcsv', explode("\n", $csv));

// Skip the header row
array_shift($rows);

// Collect video URLs (assuming they are in the first column)
$videoUrls = [];
foreach ($rows as $row) {
    if (isset($row[0])) {
        $url = trim($row[0]);
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $videoUrls[] = $url;
        }
    }
}

if (empty($videoUrls)) {
    die('No valid video URLs found in the Google Sheet.');
}

$lastVideoUrl = end($videoUrls);

// 2. Bootstrap Laravel and update the last video in the database
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Contracts\Console\Kernel;

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Video;

$lastVideo = Video::orderBy('id', 'desc')->first();

if (!$lastVideo) {
    die("No videos found in the database.\n");
}

$lastVideo->video_url = $lastVideoUrl;
$lastVideo->status = 'completed';
$lastVideo->save();

// Refresh the model to get the latest data from database
$lastVideo->refresh();

// Debug output to confirm the update
echo "Updated last video (ID: {$lastVideo->id}) with URL: {$lastVideoUrl}\n";
echo "Status after update: {$lastVideo->status}\n";
echo "Video URL after update: {$lastVideo->video_url}\n"; 