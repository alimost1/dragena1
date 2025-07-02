<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Dragena') }}</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    <div class="min-h-screen bg-gray-100">
        <nav class="bg-white border-b border-gray-100 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center space-x-2">
                            <h1 class="text-3xl font-bold text-gray-900">{{ config('app.name', 'Dragena') }}</h1>
                        </div>
                    </div>
                    <div class="flex items-center">
                        @auth
                            <span class="text-sm text-gray-500 mr-4">Tokens: {{ Auth::user()->tokens }}</span>
                            <a href="{{ route('dashboard') }}" class="text-sm text-gray-700 underline">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm text-gray-700 underline">Log in</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="ml-4 text-sm text-gray-700 underline">Register</a>
                            @endif
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <main class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                @auth
                    <div class="mb-8 flex justify-between items-center">
                        <h2 class="text-2xl font-semibold text-gray-800">Your Videos</h2>
                        <a href="{{ route('videos.create') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md">
                            Create New Video
                        </a>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @forelse (Auth::user()->videos()->latest()->paginate(12) as $video)
                            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                                <div class="p-6">
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="text-lg font-medium text-gray-900">Video #{{ $video->id }}</h3>
                                        <span class="px-2 py-1 text-sm rounded-full {{ $video->status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ ucfirst($video->status) }}
                                        </span>
                                    </div>
                                    
                                    <div class="text-sm text-gray-500 mb-4">
                                        Created {{ $video->created_at->diffForHumans() }}
                                    </div>

                                    @if($video->status === 'completed' && $video->video_url)
                                        <video class="w-full rounded-md mb-4" controls>
                                            <source src="{{ $video->video_url }}" type="video/mp4">
                                            Your browser does not support the video tag.
                                        </video>
                                    @endif

                                    <div class="flex justify-end space-x-3">
                                        <a href="{{ route('videos.show', $video) }}" class="text-blue-600 hover:text-blue-800">View Details</a>
                                        <form action="{{ route('videos.destroy', $video) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800" onclick="return confirm('Are you sure you want to delete this video?')">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full text-center py-12 bg-white rounded-lg shadow-sm">
                                <p class="text-gray-500">You haven't created any videos yet.</p>
                                <a href="{{ route('videos.create') }}" class="mt-4 inline-block text-blue-600 hover:text-blue-800">
                                    Create your first video
                                </a>
                            </div>
                        @endforelse
                    </div>
                @else
                    <div class="text-center py-12 bg-white rounded-lg shadow-sm">
                        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Welcome to {{ config('app.name', 'Dragena') }}</h2>
                        <p class="text-gray-600 mb-8">Create amazing videos from your images with our AI-powered platform.</p>
                        <div class="space-x-4">
                            <a href="{{ route('login') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-md">
                                Get Started
                            </a>
                            <a href="{{ route('register') }}" class="text-blue-600 hover:text-blue-800">
                                Create an account
                            </a>
                        </div>
                    </div>
                @endauth
            </div>
        </main>
    </div>
</body>
</html>