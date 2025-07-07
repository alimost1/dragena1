<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Videos') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium">Your Generated Videos</h3>
                        <a href="{{ route('videos.create') }}" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                            Create New Video
                        </a>
                    </div>

                    @if (session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (count($videos) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach ($videos as $video)
                                <div class="border rounded-lg overflow-hidden">
                                    <div class="p-4">
                                        <h4 class="font-medium mb-2">Video #{{ $video->id }}</h4>
                                        <div class="text-sm text-gray-600 mb-2">
                                            Created: {{ $video->created_at->format('M d, Y H:i') }}
                                        </div>
                                        <div class="mb-2">
                                            Status: 
                                            @if ($video->status === 'processing')
                                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Processing</span>
                                            @elseif ($video->status === 'completed')
                                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Completed</span>
                                            @else
                                                <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">Failed</span>
                                            @endif
                                        </div>
                                        <div class="mt-4">
                                            <a href="{{ route('videos.show', $video) }}" class="text-blue-500 hover:underline">
                                                View Details
                                            </a>
                                            <form method="POST" action="{{ route('videos.destroy', $video) }}" class="inline ml-4" onsubmit="return confirm('Are you sure you want to delete this video? This action cannot be undone.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:underline">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <p class="text-gray-500">You haven't created any videos yet.</p>
                            <a href="{{ route('videos.create') }}" class="mt-4 inline-block px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                                Create Your First Video
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>