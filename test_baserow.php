<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Baserow API connection...\n";

$apiUrl = config('services.baserow.api_url');
$token = config('services.baserow.database_token');
$tableId = config('services.baserow.table_id');

echo "API URL: " . $apiUrl . "\n";
echo "Token: " . (strlen($token) > 10 ? substr($token, 0, 10) . '...' : 'not set') . "\n";
echo "Table ID: " . $tableId . "\n\n";

if (!$token) {
    echo "ERROR: Baserow token not configured\n";
    exit(1);
}

try {
    $response = \Illuminate\Support\Facades\Http::withHeaders([
        'Authorization' => 'Token ' . $token,
        'Content-Type' => 'application/json',
    ])->get("{$apiUrl}/api/database/rows/table/{$tableId}/", [
        'user_field_names' => true,
        'size' => 5,
        'order_by' => '-id'
    ]);

    echo "Response Status: " . $response->status() . "\n";
    
    if ($response->successful()) {
        $data = $response->json();
        echo "Found " . count($data['results']) . " rows\n\n";
        
        foreach ($data['results'] as $index => $row) {
            echo "Row " . ($index + 1) . ":\n";
            echo "  ID: " . $row['id'] . "\n";
            echo "  Video ID: " . ($row['video_id'] ?? 'not set') . "\n";
            echo "  Final Video URL: " . ($row['Final Video URL'] ?? 'not set') . "\n";
            echo "  Created: " . ($row['created'] ?? 'not set') . "\n";
            echo "\n";
        }
    } else {
        echo "ERROR: " . $response->body() . "\n";
    }
} catch (\Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
} 