<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BaserowService
{
    private $apiUrl;
    private $token;
    private $tableId;

    public function __construct()
    {
        $this->apiUrl = config('services.baserow.api_url');
        $this->token = config('services.baserow.database_token');
        $this->tableId = config('services.baserow.table_id');
    }

    public function getVideoUrl($videoId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Token ' . $this->token,
                'Content-Type' => 'application/json',
            ])->get("{$this->apiUrl}/api/database/rows/table/{$this->tableId}/", [
                'filters' => json_encode([
                    'filter_type' => 'AND',
                    'filters' => [
                        [
                            'field' => 'video_id',
                            'type' => 'equal',
                            'value' => $videoId
                        ]
                    ]
                ])
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['results'])) {
                    // Use 'Final Video URL' field name as shown in your Baserow screenshot
                    return $data['results'][0]['Final Video URL'] ?? null;
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Baserow API error: ' . $e->getMessage());
            return null;
        }
    }

    public function updateVideoUrl($videoId, $videoUrl)
    {
        try {
            // Find the row first
            $response = Http::withHeaders([
                'Authorization' => 'Token ' . $this->token,
                'Content-Type' => 'application/json',
            ])->get("{$this->apiUrl}/api/database/rows/table/{$this->tableId}/", [
                'filters' => json_encode([
                    'filter_type' => 'AND',
                    'filters' => [
                        [
                            'field' => 'video_id',
                            'type' => 'equal',
                            'value' => $videoId
                        ]
                    ]
                ])
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['results'])) {
                    $rowId = $data['results'][0]['id'];
                    
                    // Update the row
                    $updateResponse = Http::withHeaders([
                        'Authorization' => 'Token ' . $this->token,
                        'Content-Type' => 'application/json',
                    ])->patch("{$this->apiUrl}/api/database/rows/table/{$this->tableId}/{$rowId}/", [
                        'video_url' => $videoUrl
                    ]);

                    return $updateResponse->successful();
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Baserow update error: ' . $e->getMessage());
            return false;
        }
    }
}