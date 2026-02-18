<div class="sub-header mx-auto {{ $align }} mt-6">
    @if($subtitle)
        <h2 class="text-2xl md:text-3xl text-white font-light uppercase tracking-wide mb-4">
            {{ $subtitle }}
        </h2>
    @endif

    @if($content)
        <div class="prose prose-invert font-light text-white prose-lg mx-auto {{ $align === 'text-center' ? 'text-center' : '' }} leading-relaxed">
            {!! $content !!}
        </div>
    @endif
</div>
