<div class="{{ $block->classes }} relative w-full h-screen overflow-hidden bg-primary-900">

    <div class="absolute inset-0 w-full h-full z-0">

        @if(!empty($video_desktop) && is_array($video_desktop))
            <video
                class="hidden md:block w-full h-full object-cover"
                autoplay
                muted
                loop
                playsinline
                poster="{{ $fallback_image ?? '' }}"
            >
                <source src="{{ $video_desktop['url'] }}" type="{{ $video_desktop['mime_type'] }}">
            </video>
        @endif

        @if(!empty($video_mobile) && is_array($video_mobile))
            <video
                class="block md:hidden w-full h-full object-cover"
                autoplay
                muted
                loop
                playsinline
                poster="{{ $fallback_image ?? '' }}"
            >
                <source src="{{ $video_mobile['url'] }}" type="{{ $video_mobile['mime_type'] }}">
            </video>
        @endif

        {{-- Placeholder pour l'éditeur Gutenberg si vide --}}
        @if(is_admin() && empty($video_desktop) && empty($video_mobile))
            <div class="absolute inset-0 flex items-center justify-center bg-primary-800/50 border-4 border-dashed border-white/20 m-4 rounded-xl">
                <div class="text-center text-white/70">
                    <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    <p class="font-bold text-lg">Bloc Hero Vidéo</p>
                    <p class="text-sm">Veuillez sélectionner les vidéos Desktop et Mobile dans la barre latérale.</p>
                </div>
            </div>
        @endif
    </div>
</div>
