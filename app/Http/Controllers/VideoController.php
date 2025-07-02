<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\Response;

class VideoController extends Controller
{
    /**
     * Display a listing of the videos.
     */
    public function index(): View
    {
        $videos = Auth::user()->videos()->latest()->paginate(12);
        return view('videos.index', compact('videos'));
    }

    /**
     * Show the form for creating a new video.
     */
    public function create(): View|RedirectResponse
    {
        $user = Auth::user();
        
        // Check if user has tokens before showing the form
        if ($user->tokens <= 0) {
            return redirect()->route('videos.index')
                ->with('error', 'You do not have enough tokens to generate a video.');
        }
        
        return view('videos.create');
    }

    /**
     * Store a newly created video in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
        ], [
            'images.required' => 'Please select at least one image.',
            'images.max' => 'You can upload a maximum of 10 images.',
            'images.*.max' => 'Each image must be smaller than 5MB.',
            'images.*.mimes' => 'Only JPEG, PNG, JPG, GIF, and WebP images are allowed.',
        ]);

        $user = Auth::user();

        // Use database transaction for data consistency
        return DB::transaction(function () use ($request, $user) {
            // Double-check token availability
            if ($user->tokens <= 0) {
                return redirect()->route('videos.create')
                    ->with('error', 'You do not have enough tokens to generate a video.');
            }

            // Deduct token first
            $user->decrement('tokens');

            // Create video record
            $video = Video::create([
                'user_id' => $user->id,
                'status' => 'processing',
            ]);

            try {
                // Store images with better organization
                $imagePaths = $this->storeImages($request->file('images'), $video->id);

                // Send request to n8n webhook
                $this->sendToN8nWebhook($video, $user, $imagePaths);

                Log::info('Video generation started', [
                    'video_id' => $video->id,
                    'user_id' => $user->id,
                    'image_count' => count($imagePaths)
                ]);

                return redirect()->route('videos.index')
                    ->with('success', 'Video generation started successfully!');

            } catch (\Exception $e) {
                // Refund token and update video status
                $user->increment('tokens');
                $video->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage()
                ]);

                Log::error('Video generation failed', [
                    'video_id' => $video->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);

                return redirect()->route('videos.create')
                    ->with('error', 'Failed to start video generation. Please try again.');
            }
        });
    }

    /**
     * Display the specified video.
     */
    public function show(Video $video): View|RedirectResponse
    {
        // Ensure the user can only view their own videos
        if ($video->user_id !== Auth::id()) {
            abort(403, 'You are not authorized to view this video.');
        }

        return view('videos.show', compact('video'));
    }

    /**
     * Delete the specified video.
     */
    public function destroy(Video $video): RedirectResponse
    {
        // Ensure the user can only delete their own videos
        if ($video->user_id !== Auth::id()) {
            abort(403, 'You are not authorized to delete this video.');
        }

        // Delete associated files if they exist
        if ($video->video_url) {
            $videoPath = str_replace(asset('storage/'), '', $video->video_url);
            Storage::disk('public')->delete($videoPath);
        }

        // Delete temporary images
        Storage::disk('public')->deleteDirectory('temp/' . $video->id);

        $video->delete();

        return redirect()->route('videos.index')
            ->with('success', 'Video deleted successfully.');
    }

    /**
     * Store uploaded images.
     */
    private function storeImages(array $images, int $videoId): array
    {
        $imagePaths = [];
        
        foreach ($images as $index => $image) {
            $filename = sprintf('%d_%d_%s.%s', 
                $videoId, 
                $index + 1, 
                uniqid(), 
                $image->getClientOriginalExtension()
            );
            
            $path = $image->storeAs('temp/' . $videoId, $filename, 'public');
            $imagePaths[] = asset('storage/' . $path);
        }
        
        return $imagePaths;
    }

    /**
     * Send video generation request to n8n webhook.
     */
    private function sendToN8nWebhook(Video $video, $user, array $imagePaths): void
    {
        $authToken = config('services.n8n.auth_token');
        $webhookUrl = config('services.n8n.webhook_url');
        
        if (!$authToken || !$webhookUrl) {
            throw new \Exception('n8n service configuration is missing.');
        }

        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $authToken,
                'Content-Type' => 'application/json',
            ])
            ->post($webhookUrl, [
                'video_id' => $video->id,
                'user_id' => $user->id,
                'user_email' => $user->email,
                'images' => $imagePaths,
                'callback_url' => url('/webhook/n8n/callback'), // Fixed: use url() instead of route()
                'created_at' => $video->created_at->toDateTimeString(), // Fixed: use toDateTimeString()
            ]);

        if (!$response->successful()) {
            $errorMessage = sprintf(
                'n8n service returned %d: %s', 
                $response->status(), 
                $response->body()
            );
            throw new \Exception($errorMessage);
        }
    }
}
