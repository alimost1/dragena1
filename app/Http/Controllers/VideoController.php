<?php

namespace App\Http\Controllers;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Services\BaserowService;

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
        try {
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'main_topic' => 'required|string|max:1000',
                'duration' => 'required|in:8,10,15,30,60',
                'generative_style' => 'required|in:hyper-realistic,artistic,cartoon,cinematic,abstract',
                'video_type' => 'required|in:user_idea,template',
                'tss' => 'required|in:af_alloy,other',
                'ai_image' => 'required|string|in:together.ai,upload_image',
                'images' => 'required|array|min:1|max:10',
                'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
            ], [
                'images.required' => 'Please select at least one image.',
                'images.max' => 'You can upload a maximum of 10 images.',
                'images.*.max' => 'Each image must be smaller than 5MB.',
                'images.*.mimes' => 'Only JPEG, PNG, JPG, GIF, and WebP images are allowed.',
            ]);
        } catch (ValidationException $e) {
            return redirect()->route('videos.create')
                ->withErrors($e->errors())
                ->withInput();
        }

        $user = Auth::user();
    
        // Use database transaction for data consistency
        return DB::transaction(function () use ($request, $user, $validatedData) {
            // Double-check token availability with fresh user data
            $user->refresh();
            if ($user->tokens <= 0) {
                return redirect()->route('videos.create')
                    ->with('error', 'You do not have enough tokens to generate a video.');
            }
    
            // Deduct token first
            $user->decrement('tokens');
    
            // Create video record with validated data
            $video = Video::create([
                'user_id' => $user->id,
                'title' => $validatedData['title'],
                'main_topic' => $validatedData['main_topic'],
                'duration' => (int) $validatedData['duration'],
                'generative_style' => $validatedData['generative_style'],
                'video_type' => $validatedData['video_type'],
                'tss' => $validatedData['tss'],
                'ai_image' => $validatedData['ai_image'],
                'status' => 'processing',
            ]);
    
            try {
                // Store images with better organization
                $imagePaths = $this->storeImages($request->file('images'), $video->id);
    
                // Send request to n8n webhook with all form data
                $this->sendToN8nWebhook($video, $user, $imagePaths, $validatedData);
    
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
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
    
                return redirect()->route('videos.create')
                    ->with('error', 'Failed to start video generation. Please try again.');
            }
        });
    }

    /**
     * Display the specified video.
     */
    public function show(Video $video): View
    {
        // Ensure the user can only view their own videos
        if ($video->user_id !== Auth::id()) {
            abort(403, 'You are not authorized to view this video.');
        }

        // If video is completed but no video_url, try to fetch from Baserow
        if ($video->status === 'completed' && empty($video->video_url)) {
            $baserowService = new BaserowService();
            $videoUrl = $baserowService->getVideoUrl($video->id);
            
            if ($videoUrl) {
                $video->update(['video_url' => $videoUrl]);
                $video->refresh();
            }
        }

        return view('videos.show', compact('video'));
    }

    /**
     * Display the latest video for the authenticated user.
     */
    public function latest(): View
    {
        $user = Auth::user();
        $video = Video::where('user_id', $user->id)->latest()->first();

        if (!$video) {
            return redirect()->route('videos.index')->with('error', 'No videos found.');
        }

        // If video is completed but no video_url, try to fetch from Baserow
        if ($video->status === 'completed' && empty($video->video_url)) {
            $baserowService = new \App\Services\BaserowService();
            $videoUrl = $baserowService->getVideoUrl($video->id);
            if ($videoUrl) {
                $video->update(['video_url' => $videoUrl]);
                $video->refresh();
            }
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

        try {
            // Delete associated files if they exist
            if ($video->video_url) {
                $videoPath = str_replace(asset('storage/'), '', $video->video_url);
                if (Storage::disk('public')->exists($videoPath)) {
                    Storage::disk('public')->delete($videoPath);
                }
            }

            // Delete temporary images
            $tempPath = 'temp/' . $video->id;
            if (Storage::disk('public')->exists($tempPath)) {
                Storage::disk('public')->deleteDirectory($tempPath);
            }

            $video->delete();

            return redirect()->route('videos.index')
                ->with('success', 'Video deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete video', [
                'video_id' => $video->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('videos.index')
                ->with('error', 'Failed to delete video. Please try again.');
        }
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
    private function sendToN8nWebhook(Video $video, $user, array $imagePaths, array $validatedData): void
    {
        $authToken = config('services.n8n.auth_token');
        $webhookUrl = config('services.n8n.webhook_url');
        
        if (!$authToken) {
            throw new \Exception('n8n auth token is not configured. Please set N8N_AUTH_TOKEN in your .env file.');
        }
        
        if (!$webhookUrl) {
            throw new \Exception('n8n webhook URL is not configured. Please set N8N_WEBHOOK_URL in your .env file.');
        }
        
        // Log the configuration for debugging
        Log::info('Sending to n8n webhook', [
            'webhook_url' => $webhookUrl,
            'video_id' => $video->id,
            'has_auth_token' => !empty($authToken)
        ]);
    
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $authToken,
                    'Content-Type' => 'application/json',
                ])
                ->post($webhookUrl, [
                    'video_id' => $video->id,
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'title' => $validatedData['title'],
                    'main_topic' => $validatedData['main_topic'],
                    'duration' => (int) $validatedData['duration'],
                    'generative_style' => $validatedData['generative_style'],
                    'video_type' => $validatedData['video_type'],
                    'tss' => $validatedData['tss'],
                    'ai_image' => $validatedData['ai_image'],
                    'images' => $imagePaths,
                    'callback_url' => url('/webhook/n8n/callback'), // Use url() helper instead of route()
                    'created_at' => $video->created_at->toDateTimeString(),
                ]);
        
            if (!$response->successful()) {
                $errorMessage = sprintf(
                    'n8n service returned %d: %s. URL: %s', 
                    $response->status(), 
                    $response->body(),
                    $webhookUrl
                );
                
                Log::error('n8n webhook failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'url' => $webhookUrl
                ]);
                
                throw new \Exception($errorMessage);
            }
            
            Log::info('n8n webhook successful', [
                'video_id' => $video->id,
                'response_status' => $response->status()
            ]);
            
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \Exception('Failed to connect to n8n service. Please check if n8n is running and accessible at: ' . $webhookUrl);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            throw new \Exception('n8n request failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle n8n webhook callback.
     */
    public function handleN8nCallback(Request $request): JsonResponse
    {
        // Log that the webhook was received
        Log::info('n8n webhook callback received', [
            'method' => $request->method(),
            'url' => $request->url(),
            'headers' => $request->headers->all(),
            'body' => $request->all()
        ]);
        
        try {
            // Validate webhook data
            $validatedData = $request->validate([
                'video_id' => 'required|integer|exists:videos,id',
                'status' => 'required|in:completed,failed',
                'video_url' => 'nullable|url',
                'error_message' => 'nullable|string|max:1000',
            ]);
            
            // Log the incoming webhook data
            Log::info('n8n webhook received', $validatedData);
            
            $video = Video::find($validatedData['video_id']);
            
            if (!$video) {
                Log::error('Video not found in n8n callback', ['video_id' => $validatedData['video_id']]);
                return response()->json(['error' => 'Video not found'], 404);
            }
            
            $updateData = [
                'status' => $validatedData['status'],
            ];
            
            if ($validatedData['status'] === 'completed' && !empty($validatedData['video_url'])) {
                $updateData['video_url'] = $validatedData['video_url'];
            }
            
            if ($validatedData['status'] === 'failed' && !empty($validatedData['error_message'])) {
                $updateData['error_message'] = $validatedData['error_message'];
            }
            
            $video->update($updateData);
            
            Log::info('Video status updated via n8n callback', [
                'video_id' => $validatedData['video_id'],
                'status' => $validatedData['status'],
                'video_url' => $validatedData['video_url'] ?? null
            ]);
            
            return response()->json(['success' => true, 'message' => 'Video updated successfully']);
            
        } catch (ValidationException $e) {
            Log::error('Invalid webhook data received', [
                'errors' => $e->errors(),
                'data' => $request->all()
            ]);
            
            return response()->json([
                'error' => 'Invalid webhook data',
                'details' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all()
            ]);
            
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    public function showBaserowVideo()
    {
        $baserowService = new \App\Services\BaserowService();

        // Directly fetch row 126 from table 313
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Token ' . config('services.baserow.database_token'),
            'Content-Type' => 'application/json',
        ])->get(config('services.baserow.api_url') . '/api/database/rows/table/313/126/');

        $videoUrl = null;
        if ($response->successful()) {
            $data = $response->json();
            $videoUrl = $data['field_2611'] ?? null; // Use field_2611 for video
        }

        return view('videos.baserow', compact('videoUrl'));
    }
}