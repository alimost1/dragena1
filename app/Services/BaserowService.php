<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class BaserowService
{
    private string $baseUrl;
    private string $token;
    private string $tableId;
    private string $videoUrlColumn;

    public function __construct()
    {
        $this->baseUrl = config('baserow.base_url', 'http://69.10.53.215:85');
        $this->token = config('baserow.token');
        $this->tableId = config('baserow.table_id');
        $this->videoUrlColumn = config('baserow.video_url_column', 'Final Video URL');
        
        if (!$this->token) {
            throw new Exception('Baserow token not configured');
        }
        
        if (!$this->tableId) {
            throw new Exception('Baserow table ID not configured');
        }
    }

    /**
     * Get video URL from Baserow by video ID
     */
    public function getVideoUrl(int $videoId): ?string
    {
        try {
            // Search for the row with matching video ID
            $response = Http::withHeaders([
                'Authorization' => 'Token ' . $this->token,
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/api/database/rows/table/{$this->tableId}/", [
                'search' => $videoId,
                'size' => 200 // Adjust based on your needs
            ]);

            if (!$response->successful()) {
                Log::error("Baserow API error: " . $response->body());
                return null;
            }

            $data = $response->json();
            
            if (empty($data['results'])) {
                Log::info("No rows found for video ID: {$videoId}");
                return null;
            }

            // Look through results to find exact match
            foreach ($data['results'] as $row) {
                // Check if any field contains the video ID
                if ($this->rowContainsVideoId($row, $videoId)) {
                    $videoUrl = $this->extractVideoUrl($row);
                    if ($videoUrl) {
                        Log::info("Found video URL for ID {$videoId}: {$videoUrl}");
                        return $videoUrl;
                    }
                }
            }

            // If no exact match found, try getting the first row (as requested)
            if (count($data['results']) > 0) {
                $firstRow = $data['results'][0];
                $videoUrl = $this->extractVideoUrl($firstRow);
                if ($videoUrl) {
                    Log::info("Using first row video URL for ID {$videoId}: {$videoUrl}");
                    return $videoUrl;
                }
            }

            return null;

        } catch (Exception $e) {
            Log::error("Error fetching video URL from Baserow: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get video URL from first row (as specified in requirements)
     */
    public function getVideoUrlFromFirstRow(): ?string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Token ' . $this->token,
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/api/database/rows/table/{$this->tableId}/", [
                'size' => 1,
                'page' => 1
            ]);

            if (!$response->successful()) {
                Log::error("Baserow API error: " . $response->body());
                return null;
            }

            $data = $response->json();
            
            if (empty($data['results'])) {
                Log::info("No rows found in Baserow table");
                return null;
            }

            $firstRow = $data['results'][0];
            return $this->extractVideoUrl($firstRow);

        } catch (Exception $e) {
            Log::error("Error fetching first row from Baserow: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all completed videos from Baserow
     */
    public function getAllCompletedVideos(): array
    {
        try {
            $allRows = [];
            $page = 1;
            $size = 200;

            do {
                $response = Http::withHeaders([
                    'Authorization' => 'Token ' . $this->token,
                    'Content-Type' => 'application/json',
                ])->get("{$this->baseUrl}/api/database/rows/table/{$this->tableId}/", [
                    'size' => $size,
                    'page' => $page
                ]);

                if (!$response->successful()) {
                    Log::error("Baserow API error: " . $response->body());
                    break;
                }

                $data = $response->json();
                $allRows = array_merge($allRows, $data['results']);
                
                $page++;
            } while (count($data['results']) === $size);

            // Filter rows that have video URLs
            $completedVideos = [];
            foreach ($allRows as $row) {
                $videoUrl = $this->extractVideoUrl($row);
                if ($videoUrl) {
                    $completedVideos[] = [
                        'row_id' => $row['id'],
                        'video_url' => $videoUrl,
                        'row_data' => $row
                    ];
                }
            }

            return $completedVideos;

        } catch (Exception $e) {
            Log::error("Error fetching all videos from Baserow: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if row contains the video ID
     */
    private function rowContainsVideoId(array $row, int $videoId): bool
    {
        foreach ($row as $key => $value) {
            if (is_string($value) && strpos($value, (string)$videoId) !== false) {
                return true;
            }
            if (is_numeric($value) && (int)$value === $videoId) {
                return true;
            }
        }
        return false;
    }

    /**
     * Extract video URL from row data
     */
    private function extractVideoUrl(array $row): ?string
    {
        // Try to find the Final Video URL column by name
        if (isset($row[$this->videoUrlColumn]) && !empty($row[$this->videoUrlColumn])) {
            $url = $row[$this->videoUrlColumn];
            if (is_array($url)) {
                // Handle file/attachment fields
                return isset($url[0]['url']) ? $url[0]['url'] : null;
            }
            return is_string($url) ? $url : null;
        }

        // Try common field names if exact match not found
        $possibleFields = [
            'final_video_url',
            'video_url',
            'url',
            'Final Video URL',
            'video',
            'Video URL'
        ];

        foreach ($possibleFields as $field) {
            if (isset($row[$field]) && !empty($row[$field])) {
                $url = $row[$field];
                if (is_array($url)) {
                    return isset($url[0]['url']) ? $url[0]['url'] : null;
                }
                if (is_string($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                    return $url;
                }
            }
        }

        // Look for any field that contains a valid URL
        foreach ($row as $key => $value) {
            if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
                // Check if it looks like a video URL
                if (preg_match('/\.(mp4|avi|mov|wmv|flv|webm|mkv)$/i', $value) ||
                    strpos($value, 'video') !== false ||
                    strpos($value, 'mp4') !== false) {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * Update video URL in Baserow
     */
    public function updateVideoUrl(int $rowId, string $videoUrl): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Token ' . $this->token,
                'Content-Type' => 'application/json',
            ])->patch("{$this->baseUrl}/api/database/rows/table/{$this->tableId}/{$rowId}/", [
                $this->videoUrlColumn => $videoUrl
            ]);

            if ($response->successful()) {
                Log::info("Updated Baserow row {$rowId} with video URL: {$videoUrl}");
                return true;
            } else {
                Log::error("Failed to update Baserow row {$rowId}: " . $response->body());
                return false;
            }

        } catch (Exception $e) {
            Log::error("Error updating Baserow row {$rowId}: " . $e->getMessage());
            return false;
        }
    }
}