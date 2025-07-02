<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle the n8n callback.
     */
    public function n8nCallback(Request $request)
    {
        // Validate the request
        $request->validate([
            'video_id' => 'required|exists:videos,id',
            'status' => 'required|in:completed,failed',
            'video_url' => 'required_if:status,completed|nullable|url',
            'error_message' => 'required_if:status,failed|nullable|string',
        ]);

        try {
            // Find the video
            $video = Video::findOrFail($request->video_id);

            // Update the video status
            $video->status = $request->status;
            
            if ($request->status === 'completed') {
                $video->video_url = $request->video_url;
            } else {
                $video->error_message = $request->error_message;
            }

            $video->save();

            return response()->json(['message' => 'Webhook processed successfully']);
        } catch (\Exception $e) {
            Log::error('Failed to process n8n webhook: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
