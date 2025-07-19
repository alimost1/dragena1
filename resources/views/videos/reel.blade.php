<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Reel Video') }}
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="max-w-sm mx-auto sm:px-4 lg:px-6">
            @if ($video->isReadyToPlay())
                <!-- Reel Video Player -->
                <div class="mb-6">
                    <x-reel-video-player 
                        :videoUrl="$video->video_url"
                        :videoId="$video->id"
                        :autoplay="true"
                        :loop="true"
                        :muted="true"
                    />
                </div>

                <!-- Video Info -->
                <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Video #{{ $video->id }}</h3>
                            <p class="text-sm text-gray-500">{{ $video->created_at->format('M d, Y H:i') }}</p>
                        </div>
                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">
                            Completed
                        </span>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex space-x-3">
                        <button 
                            onclick="document.querySelector('video').currentTime = 0; document.querySelector('video').play()"
                            class="flex-1 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
                        >
                            Replay
                        </button>
                        <a 
                            href="{{ $video->video_url }}" 
                            download 
                            class="flex-1 px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors text-center"
                        >
                            Download
                        </a>
                    </div>
                </div>

                <!-- Share Section -->
                <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
                    <h4 class="font-medium text-gray-900 mb-3">Share</h4>
                    <div class="flex space-x-3">
                        <button 
                            onclick="navigator.share ? navigator.share({title: 'Check out this video!', url: window.location.href}) : copyToClipboard(window.location.href)"
                            class="flex-1 px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors"
                        >
                            Share
                        </button>
                        <button 
                            onclick="copyToClipboard('{{ $video->video_url }}')"
                            class="flex-1 px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition-colors"
                        >
                            Copy URL
                        </button>
                    </div>
                </div>

            @elseif ($video->isProcessing())
                <!-- Processing State -->
                <div class="bg-yellow-50 rounded-lg p-6 text-center">
                    <div class="animate-spin h-12 w-12 text-yellow-500 mx-auto mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-yellow-800 mb-2">Processing Your Reel</h3>
                    <p class="text-yellow-700 text-sm mb-4">Your video is being generated. This may take a few minutes.</p>
                    <button 
                        onclick="window.location.reload()" 
                        class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors"
                    >
                        Refresh Status
                    </button>
                </div>

            @elseif ($video->status === 'completed' && empty($video->video_url))
                <!-- Completed but missing URL -->
                <div class="bg-blue-50 rounded-lg p-6 text-center">
                    <div class="animate-spin h-12 w-12 text-blue-500 mx-auto mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-blue-800 mb-2">Video completed! Fetching video URL...</h3>
                    <p class="text-blue-700 text-sm mb-4">Your video has been generated and we're retrieving it from storage.</p>
                    <div class="flex space-x-3">
                        <button 
                            onclick="window.location.reload()" 
                            class="flex-1 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
                        >
                            Refresh & Fetch Video
                        </button>
                        <form method="POST" action="{{ route('videos.sync', $video) }}" class="inline">
                            @csrf
                            <button type="submit" class="flex-1 px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                                Force Sync
                            </button>
                        </form>
                    </div>
                </div>

            @else
                <!-- Error State -->
                <div class="bg-red-50 rounded-lg p-6 text-center">
                    <div class="text-red-500 mb-4">
                        <svg class="h-12 w-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-red-800 mb-2">Video Generation Failed</h3>
                    <p class="text-red-700 text-sm mb-4">
                        {{ $video->error_message ?? 'An unknown error occurred during video generation.' }}
                    </p>
                    <a 
                        href="{{ route('videos.create') }}" 
                        class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors"
                    >
                        Try Again
                    </a>
                </div>
            @endif

            <!-- Navigation -->
            <div class="flex justify-between items-center">
                <a href="{{ route('videos.index') }}" class="text-blue-500 hover:underline">
                    ← Back to Videos
                </a>
                <form method="POST" action="{{ route('videos.destroy', $video) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this video?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-500 hover:underline">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function copyToClipboard(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    // Show success message
                    const button = event.target;
                    const originalText = button.textContent;
                    button.textContent = 'Copied!';
                    button.classList.add('bg-green-600');
                    setTimeout(() => {
                        button.textContent = originalText;
                        button.classList.remove('bg-green-600');
                    }, 2000);
                });
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
            }
        }
    </script>
</x-app-layout> 