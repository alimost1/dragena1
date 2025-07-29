<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create New Video') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium mb-6">Create New Video</h3>

                    @if (session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="mb-6 p-4 bg-yellow-50 rounded-lg">
                        <p class="text-sm text-yellow-800">
                            <strong>Available Tokens:</strong> {{ Auth::user()->tokens }}
                        </p>
                        <p class="text-sm text-yellow-800 mt-2">
                            Creating a video will use 1 token from your account.
                        </p>
                    </div>

                    <form action="{{ route('videos.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                                Video Title
                            </label>
                            <input 
                                type="text" 
                                name="title" 
                                id="title" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                required
                            >
                            @error('title')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="main_topic" class="block text-sm font-medium text-gray-700 mb-2">
                                Main Topic
                            </label>
                            <textarea 
                                name="main_topic" 
                                id="main_topic" 
                                rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Describe the main topic or theme for your video..."
                                required
                            ></textarea>
                            @error('main_topic')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="duration" class="block text-sm font-medium text-gray-700 mb-2">
                                Video Duration
                            </label>
                            <select 
                                name="duration" 
                                id="duration"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                required
                            >
                                <option value="8" selected>8 seconds</option>
                                <option value="10">10 seconds</option>
                                <option value="15">15 seconds</option>
                                <option value="30">30 seconds</option>
                                <option value="60">60 seconds</option>
                            </select>
                            @error('duration')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="generative_style" class="block text-sm font-medium text-gray-700 mb-2">
                                Generative Style
                            </label>
                            <select 
                                name="generative_style" 
                                id="generative_style"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                required
                            >
                                <option value="hyper-realistic" selected>Hyper-realistic</option>
                                <option value="artistic">Artistic</option>
                                <option value="cartoon">Cartoon</option>
                                <option value="cinematic">Cinematic</option>
                                <option value="abstract">Abstract</option>
                            </select>
                            @error('generative_style')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="video_type" class="block text-sm font-medium text-gray-700 mb-2">
                                Video Type
                            </label>
                            <select 
                                name="video_type" 
                                id="video_type"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                required
                            >
                                <option value="user_idea" selected>From User Idea</option>
                                <option value="template">From Template</option>
                            </select>
                            @error('video_type')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                       
                        <div>
                            <label for="tss" class="block text-sm font-medium text-gray-700 mb-2">
                                TSS
                            </label>
                            <select 
                                name="tss" 
                                id="tss"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                required
                            >
                                <option value="af_alloy" selected>af_alloy</option>
                                <option value="other">Other</option>
                            </select>
                            @error('tss')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="language" class="block text-sm font-medium text-gray-700 mb-2">
                                Language
                            </label>
                            <select 
                                name="language" 
                                id="language"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                required
                            >
                                <option value="english" selected>English</option>
                                <option value="arabic">Arabic</option>
                                <option value="french">French</option>
                            </select>
                            @error('language')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="ai_image" class="block text-sm font-medium text-gray-700 mb-2">
                                AI Image
                            </label>
                            <select 
                                name="ai_image" 
                                id="ai_image"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                required
                                onchange="toggleImageUpload()"
                            >
                                <option value="together.ai" selected>together.ai</option>
                                <option value="upload_image">Upload Image</option>
                            </select>
                            @error('ai_image')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Conditional Image Upload Section -->
                        <div id="upload-images-section" style="display: none;">
                            <label for="images" class="block text-sm font-medium text-gray-700 mb-2">
                                Upload Images (Select multiple files)
                            </label>
                            <input 
                                type="file" 
                                name="images[]" 
                                id="images" 
                                multiple 
                                accept="image/png, image/jpeg, image/jpg, image/gif"
                                class="block w-full text-sm text-gray-500
                                    file:mr-4 file:py-2 file:px-4
                                    file:rounded-full file:border-0
                                    file:text-sm file:font-semibold
                                    file:bg-blue-50 file:text-blue-700
                                    hover:file:bg-blue-100"
                            >
                            @error('images.*')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Remove the default-images-section entirely -->

                        <div id="image-preview" class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 hidden">
                            <!-- Preview images will be displayed here -->
                        </div>

                        <div class="flex items-center justify-between pt-4">
                            <a href="{{ route('videos.index') }}" class="text-gray-500 hover:underline">
                                Cancel
                            </a>
                            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600" id="submit-btn" disabled>
                                Generate Video
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleImageUpload() {
            const aiImageSelect = document.getElementById('ai_image');
            const uploadSection = document.getElementById('upload-images-section');
            const uploadInput = document.getElementById('images');
            
            if (aiImageSelect.value === 'upload_image') {
                uploadSection.style.display = 'block';
                uploadInput.required = true;
            } else {
                // Hide image upload when together.ai is selected
                uploadSection.style.display = 'none';
                uploadInput.required = false;
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize the display state
            toggleImageUpload();
            const input = document.getElementById('images');
            const preview = document.getElementById('image-preview');
            const submitBtn = document.getElementById('submit-btn');
            const aiImageSelect = document.getElementById('ai_image');

            // Enable submit button if together.ai is selected
            function updateSubmitButton() {
                if (aiImageSelect.value === 'together.ai') {
                    submitBtn.disabled = false;
                } else if (aiImageSelect.value === 'upload_image') {
                    submitBtn.disabled = input.files.length === 0;
                }
            }

            // Update submit button state when AI image selection changes
            aiImageSelect.addEventListener('change', function() {
                updateSubmitButton();
            });

            input.addEventListener('change', function() {
                preview.innerHTML = '';
                preview.classList.remove('hidden');

                if (this.files.length > 0) {
                    submitBtn.disabled = false;
                    
                    Array.from(this.files).forEach(file => {
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            const div = document.createElement('div');
                            div.className = 'relative';
                            
                            div.innerHTML = `
                                <img src="${e.target.result}" class="w-full h-32 object-cover rounded" />
                                <p class="text-xs mt-1 truncate">${file.name}</p>
                            `;
                            
                            preview.appendChild(div);
                        }
                        
                        reader.readAsDataURL(file);
                    });
                } else {
                    if (aiImageSelect.value === 'upload_image') {
                        submitBtn.disabled = true;
                    }
                    preview.classList.add('hidden');
                }
            });

            // Initialize submit button state
            updateSubmitButton();
        });
    </script>
</x-app-layout>