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
                        <a href="{{ route('videos.index') }}" class="text-blue-500 hover:underline">
                            Back to Videos
                        </a>
                    </div>

                    <div class="mb-6">
                        <p class="text-sm text-gray-600 mb-2">
                            <strong>Created:</strong> {{ $video->created_at->format('M d, Y H:i') }}
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

                    @if ($video->status === 'processing')
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
                    @elseif ($video->status === 'completed')
                        <div class="bg-gray-100 p-4 rounded-lg mb-6">
                            <h4 class="font-medium mb-4">Your Video</h4>
                            <div class="aspect-w-16 aspect-h-9">
                                <video 
                                    controls 
                                    class="w-full h-auto rounded shadow-lg" 
                                    poster="{{ asset('images/video-poster.jpg') }}"
                                >
                                    <source src="{{ $video->video_url }}" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            </div>
                            <div class="mt-4 flex justify-center">
                                <a 
                                    href="{{ $video->video_url }}" 
                                    download 
                                    class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                                >
                                    Download Video
                                </a>
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
                </div>
            </div>
        </div>
    </div>
</x-app-layout>