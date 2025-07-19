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
    // First, let's get the table structure
    $tableResponse = \Illuminate\Support\Facades\Http::withHeaders([
        'Authorization' => 'Token ' . $token,
        'Content-Type' => 'application/json',
    ])->get("{$apiUrl}/api/database/tables/{$tableId}/");

    echo "Table Response Status: " . $tableResponse->status() . "\n";
    
    if ($tableResponse->successful()) {
        $tableData = $tableResponse->json();
        echo "Table Name: " . $tableData['name'] . "\n";
        echo "Fields:\n";
        foreach ($tableData['fields'] as $field) {
            echo "  - " . $field['name'] . " (Type: " . $field['type'] . ")\n";
        }
        echo "\n";
    } else {
        echo "ERROR getting table structure: " . $tableResponse->body() . "\n";
    }

    // Now try to get rows without ordering
    $response = \Illuminate\Support\Facades\Http::withHeaders([
        'Authorization' => 'Token ' . $token,
        'Content-Type' => 'application/json',
    ])->get("{$apiUrl}/api/database/rows/table/{$tableId}/", [
        'user_field_names' => true,
        'size' => 5
    ]);

    echo "Rows Response Status: " . $response->status() . "\n";
    
    if ($response->successful()) {
        $data = $response->json();
        echo "Found " . count($data['results']) . " rows\n\n";
        
        foreach ($data['results'] as $index => $row) {
            echo "Row " . ($index + 1) . ":\n";
            foreach ($row as $key => $value) {
                echo "  " . $key . ": " . (is_string($value) ? $value : json_encode($value)) . "\n";
            }
            echo "\n";
        }
    } else {
        echo "ERROR getting rows: " . $response->body() . "\n";
    }
} catch (\Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
} 