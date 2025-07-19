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
            // First try to find by video_id if it exists
            $response = Http::withHeaders([
                'Authorization' => 'Token ' . $this->token,
                'Content-Type' => 'application/json',
            ])->get("{$this->apiUrl}/api/database/rows/table/{$this->tableId}/", [
                'user_field_names' => true,
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
                    return $data['results'][0]['Final Video URL'] ?? null;
                }
            }

            // If no video_id field or no match, get the most recent completed video
            $response = Http::withHeaders([
                'Authorization' => 'Token ' . $this->token,
                'Content-Type' => 'application/json',
            ])->get("{$this->apiUrl}/api/database/rows/table/{$this->tableId}/", [
                'user_field_names' => true,
                'size' => 1,
                'order_by' => 'id'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['results'])) {
                    $row = $data['results'][0];
                    $videoUrl = $row['Final Video URL'] ?? null;
                    
                    if ($videoUrl) {
                        Log::info("Found video URL for video {$videoId} from most recent Baserow row: {$videoUrl}");
                        return $videoUrl;
                    }
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