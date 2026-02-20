@php
    $videoCount = is_array($videos) ? count($videos) : 0;
    $gridClass = $videoCount > 1 ? 'md:grid-cols-2' : '';
@endphp

<div class="grid grid-cols-1 {{ $gridClass }} gap-8">
    @foreach($videos as $video)
        <div class="bg-white overflow-hidden shadow-lg flex flex-col h-full">

            <div class="aspect-video w-full relative flex-shrink-0 [&>iframe]:w-full [&>iframe]:h-full [&>iframe]:absolute [&>iframe]:top-0 [&>iframe]:left-0">
                {!! wp_oembed_get($video['video_url']) !!}
            </div>

            @if(!empty($video['video_title']) || !empty($video['video_description']))
                <div class="p-6 md:p-8 flex-grow flex flex-col">
                    @if(!empty($video['video_title']))
                        <h3 class="text-xl md:text-2xl font-bold text-primary-900 mb-3 font-heading leading-tight">
                            {{ $video['video_title'] }}
                        </h3>
                    @endif

                    @if(!empty($video['video_description']))
                        <div class="prose max-w-none text-gray-600 font-light text-sm md:text-base mt-auto">
                            {!! nl2br(e($video['video_description'])) !!}
                        </div>
                    @endif
                </div>
            @endif

        </div>
    @endforeach
</div>
