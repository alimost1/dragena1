@props(['videoUrl', 'videoId', 'poster' => null, 'autoplay' => false, 'loop' => true, 'muted' => true])

<div 
    x-data="reelVideoPlayer()" 
    x-init="init('{{ $videoUrl }}', {{ $autoplay ? 'true' : 'false' }}, {{ $loop ? 'true' : 'false' }}, {{ $muted ? 'true' : 'false' }})"
    class="reel-video-container relative w-full max-w-sm mx-auto bg-gray-900 rounded-lg overflow-hidden shadow-lg"
    style="aspect-ratio: 9/16; max-height: 70vh;"
>
    <!-- Video Element -->
    <video 
        x-ref="video"
        class="w-full h-full object-contain bg-black"
        preload="metadata"
        @if($poster) poster="{{ $poster }}" @endif
        @if($autoplay) autoplay @endif
        @if($loop) loop @endif
        @if($muted) muted @endif
        playsinline
        webkit-playsinline
        x-on:loadedmetadata="onVideoLoaded"
        x-on:timeupdate="onTimeUpdate"
        x-on:ended="onVideoEnded"
        x-on:play="onPlay"
        x-on:pause="onPause"
        x-on:volumechange="onVolumeChange"
    >
        <source src="{{ $videoUrl }}" type="video/mp4">
        <source src="{{ $videoUrl }}" type="video/webm">
        Your browser does not support the video tag.
    </video>

    <!-- Overlay Controls -->
    <div 
        x-show="showControls" 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent pointer-events-none"
    >
        <!-- Top Controls -->
        <div class="absolute top-0 left-0 right-0 p-4 flex justify-between items-center pointer-events-auto">
            <button 
                @click="toggleMute"
                class="text-white hover:text-gray-300 transition-colors"
                x-html="isMuted ? '🔇' : '🔊'"
            ></button>
            <button 
                @click="toggleFullscreen"
                class="text-white hover:text-gray-300 transition-colors"
            >
                ⛶
            </button>
        </div>

        <!-- Center Play/Pause Button -->
        <div class="absolute inset-0 flex items-center justify-center pointer-events-auto">
            <button 
                x-show="!isPlaying"
                @click="togglePlay"
                class="bg-white/20 backdrop-blur-sm rounded-full p-4 text-white hover:bg-white/30 transition-all"
            >
                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z"/>
                </svg>
            </button>
        </div>

        <!-- Bottom Controls -->
        <div class="absolute bottom-0 left-0 right-0 p-4 pointer-events-auto">
            <!-- Progress Bar -->
            <div class="mb-3">
                <div class="w-full bg-white/20 rounded-full h-1">
                    <div 
                        class="bg-white h-1 rounded-full transition-all duration-100"
                        :style="`width: ${progress}%`"
                    ></div>
                </div>
            </div>

            <!-- Bottom Controls Row -->
            <div class="flex justify-between items-center">
                <div class="text-white text-sm">
                    <span x-text="formatTime(currentTime)"></span>
                    <span>/</span>
                    <span x-text="formatTime(duration)"></span>
                </div>
                
                <div class="flex space-x-3">
                    <button 
                        @click="toggleLoop"
                        class="text-white hover:text-gray-300 transition-colors"
                        :class="{ 'text-blue-400': isLooped }"
                    >
                        🔄
                    </button>
                    <button 
                        @click="togglePlay"
                        class="text-white hover:text-gray-300 transition-colors"
                        x-html="isPlaying ? '⏸️' : '▶️'"
                    ></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div 
        x-show="isLoading"
        class="absolute inset-0 flex items-center justify-center bg-black/50"
    >
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-white"></div>
    </div>

    <!-- Error Message -->
    <div 
        x-show="hasError"
        class="absolute inset-0 flex items-center justify-center bg-black/50"
    >
        <div class="text-white text-center p-4">
            <p class="text-lg font-medium mb-2">Video Error</p>
            <p class="text-sm opacity-75">Unable to load video</p>
            <button 
                @click="retryLoad"
                class="mt-3 px-4 py-2 bg-white/20 rounded hover:bg-white/30 transition-colors"
            >
                Retry
            </button>
        </div>
    </div>
</div>

<style>
.reel-video-container {
    max-height: 80vh;
}

.reel-video-container video {
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
}

/* Custom scrollbar for webkit browsers */
.reel-video-container::-webkit-scrollbar {
    display: none;
}

.reel-video-container {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>

<script>
function reelVideoPlayer() {
    return {
        video: null,
        isLoading: true,
        hasError: false,
        isPlaying: false,
        isMuted: true,
        isLooped: true,
        showControls: false,
        currentTime: 0,
        duration: 0,
        progress: 0,
        controlsTimeout: null,

        init(videoUrl, autoplay, loop, muted) {
            this.video = this.$refs.video;
            this.isLooped = loop;
            
            // Set up touch/swipe events
            this.setupTouchEvents();
            
            // Auto-hide controls
            this.setupControlsAutoHide();
            
            // Intersection Observer for auto-play on scroll
            this.setupIntersectionObserver();
        },

        setupTouchEvents() {
            let startX = 0;
            let startY = 0;
            let startTime = 0;

            this.$el.addEventListener('touchstart', (e) => {
                startX = e.touches[0].clientX;
                startY = e.touches[0].clientY;
                startTime = Date.now();
            });

            this.$el.addEventListener('touchend', (e) => {
                const endX = e.changedTouches[0].clientX;
                const endY = e.changedTouches[0].clientY;
                const endTime = Date.now();
                const duration = endTime - startTime;
                const distanceX = Math.abs(endX - startX);
                const distanceY = Math.abs(endY - startY);

                // If it's a quick tap (less than 200ms) and small movement
                if (duration < 200 && distanceX < 10 && distanceY < 10) {
                    this.togglePlay();
                }

                // Show controls on any touch
                this.showControlsTemporarily();
            });
        },

        setupControlsAutoHide() {
            this.$el.addEventListener('mousemove', () => {
                this.showControlsTemporarily();
            });

            this.$el.addEventListener('mouseleave', () => {
                this.hideControls();
            });
        },

        setupIntersectionObserver() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        // Auto-play when video comes into view
                        if (this.video.paused) {
                            this.video.play().catch(() => {
                                // Auto-play failed, that's okay
                            });
                        }
                    } else {
                        // Pause when video goes out of view
                        if (!this.video.paused) {
                            this.video.pause();
                        }
                    }
                });
            }, {
                threshold: 0.5
            });

            observer.observe(this.$el);
        },

        showControlsTemporarily() {
            this.showControls = true;
            
            if (this.controlsTimeout) {
                clearTimeout(this.controlsTimeout);
            }
            
            this.controlsTimeout = setTimeout(() => {
                this.hideControls();
            }, 3000);
        },

        hideControls() {
            this.showControls = false;
        },

        onVideoLoaded() {
            this.isLoading = false;
            this.duration = this.video.duration;
            this.hasError = false;
        },

        onTimeUpdate() {
            this.currentTime = this.video.currentTime;
            this.progress = (this.currentTime / this.duration) * 100;
        },

        onVideoEnded() {
            this.isPlaying = false;
        },

        onPlay() {
            this.isPlaying = true;
        },

        onPause() {
            this.isPlaying = false;
        },

        onVolumeChange() {
            this.isMuted = this.video.muted;
        },

        togglePlay() {
            if (this.video.paused) {
                this.video.play().catch(() => {
                    console.log('Play failed');
                });
            } else {
                this.video.pause();
            }
        },

        toggleMute() {
            this.video.muted = !this.video.muted;
        },

        toggleLoop() {
            this.isLooped = !this.isLooped;
            this.video.loop = this.isLooped;
        },

        toggleFullscreen() {
            if (document.fullscreenElement) {
                document.exitFullscreen();
            } else {
                this.$el.requestFullscreen().catch(() => {
                    console.log('Fullscreen failed');
                });
            }
        },

        retryLoad() {
            this.isLoading = true;
            this.hasError = false;
            this.video.load();
        },

        formatTime(seconds) {
            if (isNaN(seconds)) return '0:00';
            
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        }
    }
}
</script> 