<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Video Details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium">Video #{{ $video->id }}</h3>
                        <div class="flex space-x-4">
                            <a href="{{ route('videos.index') }}" class="text-blue-500 hover:underline">
                                Back to Videos
                            </a>
                            @if ($video->status === 'completed')
                                <a href="{{ route('videos.reel', $video) }}" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
                                    View as Reel
                                </a>
                            @endif
                            <form method="POST" action="{{ route('videos.destroy', $video) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this video? This action cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
                                    Delete Video
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="mb-6">
                        <p class="text-sm text-gray-600 mb-2">
                            <strong>Created:</strong> {{ $video->created_at->format('M d, Y H:i') }}
                        </p>
                        <p class="text-sm text-gray-600 mb-2">
                            <strong>Language:</strong> 
                            <span class="capitalize">{{ $video->language }}</span>
                        </p>
                        <p class="text-sm text-gray-600 mb-2">
                            <strong>Status:</strong> 
                            @if ($video->status === 'processing')
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Processing</span>
                            @elseif ($video->status === 'completed')
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Completed</span>
                            @else
                                <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">Failed</span>
                            @endif
                        </p>
                    </div>

                    @if ($video->isProcessing())
                        <div class="bg-yellow-50 p-6 rounded-lg text-center">
                            <svg class="animate-spin h-10 w-10 text-yellow-500 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="text-yellow-800 font-medium">Your video is being generated...</p>
                            <p class="text-yellow-700 text-sm mt-2">This may take a few minutes. You can check back later.</p>
                            <button 
                                onclick="window.location.reload()" 
                                class="mt-4 px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600"
                            >
                                Refresh Status
                            </button>
                        </div>
                    @elseif ($video->isReadyToPlay())
                        <div class="bg-gray-100 p-4 rounded-lg mb-6">
                            <h4 class="font-medium mb-4">Your Video</h4>
                            <div class="max-w-4xl mx-auto">
                                <div class="aspect-w-16 aspect-h-9 bg-black rounded-lg overflow-hidden">
                                    <video 
                                        controls 
                                        preload="metadata"
                                        class="w-full h-full object-cover" 
                                        poster="{{ asset('images/video-poster.jpg') }}"
                                        controlsList="nodownload"
                                    >
                                        <source src="{{ $video->video_url }}" type="video/mp4">
                                        <source src="{{ $video->video_url }}" type="video/webm">
                                        Your browser does not support the video tag.
                                    </video>
                                </div>
                            </div>
                            <div class="mt-4 flex justify-between items-center">
                                <div class="text-sm text-gray-600">
                                    <p><strong>Duration:</strong> Auto-detected</p>
                                    <p><strong>Format:</strong> MP4</p>
                                </div>
                                <div class="flex space-x-2">
                                    <button 
                                        onclick="document.querySelector('video').currentTime = 0; document.querySelector('video').play()"
                                        class="px-3 py-1 bg-green-500 text-white rounded text-sm hover:bg-green-600"
                                    >
                                        Replay
                                    </button>
                                    <a 
                                        href="{{ $video->video_url }}" 
                                        download 
                                        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                                    >
                                        Download Video
                                    </a>
                                </div>
                            </div>
                        </div>
                    @elseif ($video->status === 'completed' && empty($video->video_url))
                        <div class="bg-blue-50 p-6 rounded-lg text-center">
                            <svg class="animate-spin h-10 w-10 text-blue-500 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="text-blue-800 font-medium">Video completed! Fetching video URL...</p>
                            <p class="text-blue-700 text-sm mt-2">Your video has been generated and we're retrieving it from storage.</p>
                            <div class="flex space-x-3">
                                <button 
                                    onclick="window.location.reload()" 
                                    class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                                >
                                    Refresh & Fetch Video
                                </button>
                                <form method="POST" action="{{ route('videos.sync', $video) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
                                        Force Sync
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="bg-red-50 p-6 rounded-lg">
                            <h4 class="font-medium text-red-800 mb-2">Video Generation Failed</h4>
                            <p class="text-red-700 text-sm">
                                {{ $video->error_message ?? 'An unknown error occurred during video generation.' }}
                            </p>
                            <div class="mt-4">
                                <a 
                                    href="{{ route('videos.create') }}" 
                                    class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600"
                                >
                                    Try Again
                                </a>
                            </div>
                        </div>
                    @endif
                    
                    <!-- Delete Video Section -->
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <div class="bg-red-50 p-4 rounded-lg">
                            <h4 class="font-medium text-red-800 mb-2">Delete Video</h4>
                            <p class="text-red-700 text-sm mb-4">
                                Once you delete this video, it will be permanently removed and cannot be recovered.
                            </p>
                            <form method="POST" action="{{ route('videos.destroy', $video) }}" onsubmit="return confirm('Are you sure you want to delete this video? This action cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                                    Delete Video Permanently
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>