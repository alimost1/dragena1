<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Baserow Video') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if($videoUrl)
                    <video controls class="w-full rounded" style="max-height: 500px;">
                        <source src="{{ $videoUrl }}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                @else
                    <p class="text-red-600">No video URL found for this row.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>