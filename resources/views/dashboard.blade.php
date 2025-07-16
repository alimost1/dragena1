<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{ __("You're logged in!") }}
                    <div class="mt-6">
                        <a href="{{ route('videos.latest') }}" class="inline-block px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-semibold shadow">
                            Watch Your Latest Video
                        </a>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('baserow.video') }}" class="inline-block px-6 py-2 bg-green-600 text-white rounded hover:bg-green-700 font-semibold shadow">
                            Watch Baserow Video (Row 126)
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
