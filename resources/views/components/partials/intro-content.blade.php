@if ($title || $content)
  <div class="mt-6 mb-8 flex items-center gap-4">
    @if ($icon)
      <img src="{{ $icon }}" alt="" class="h-32 w-32 flex-shrink-0 object-contain" />
    @endif

    <div class="flex-1">
      @if ($title)
        <p class="mb-2 text-sm font-bold tracking-wider md:text-base">
          {{ $title }}
        </p>
      @endif

      @if ($content)
        <div class="prose prose-p:my-0 leading-relaxed font-light text-current">
          {!! $content !!}
        </div>
      @endif
    </div>
  </div>
@endif
