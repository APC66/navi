@if($title || $content)
<div class="flex items-center gap-4 mb-8 mt-6">
    @if($icon)
    <img src="{{ $icon }}" alt="" class="w-32 h-32 object-contain flex-shrink-0">
    @endif

    <div class="flex-1">
        @if($title)
        <p class="text-sm md:text-base font-bold tracking-wider mb-2">
            {{ $title }}
        </p>
        @endif

        @if($content)
        <div class="leading-relaxed font-light prose prose-p:my-0 text-current">
            {!! $content !!}
        </div>
        @endif
    </div>
</div>
@endif
