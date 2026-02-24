@php
  $videoCount = is_array($videos) ? count($videos) : 0;
  $gridClass = $videoCount > 1 ? 'md:grid-cols-2' : '';
@endphp

<div class="{{ $gridClass }} grid grid-cols-1 gap-8">
  @foreach ($videos as $video)
    <div class="flex h-full flex-col overflow-hidden bg-white shadow-lg">
      <div
        class="relative aspect-video w-full flex-shrink-0 [&>iframe]:absolute [&>iframe]:top-0 [&>iframe]:left-0 [&>iframe]:h-full [&>iframe]:w-full"
      >
        {!! wp_oembed_get($video['video_url']) !!}
      </div>

      @if (! empty($video['video_title']) || ! empty($video['video_description']))
        <div class="flex flex-grow flex-col p-6 md:p-8">
          @if (! empty($video['video_title']))
            <h3
              class="text-primary-900 font-heading mb-3 text-xl leading-tight font-bold md:text-2xl"
            >
              {{ $video['video_title'] }}
            </h3>
          @endif

          @if (! empty($video['video_description']))
            <div class="prose mt-auto max-w-none text-sm font-light text-gray-600 md:text-base">
              {!! nl2br(e($video['video_description'])) !!}
            </div>
          @endif
        </div>
      @endif
    </div>
  @endforeach
</div>
