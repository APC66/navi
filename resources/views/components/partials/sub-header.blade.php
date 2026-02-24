<div class="sub-header {{ $align }} mx-auto mt-6">
  @if ($subtitle)
    <h2 class="mb-4 text-2xl font-light tracking-wide text-white uppercase md:text-3xl">
      {{ $subtitle }}
    </h2>
  @endif

  @if ($content)
    <div
      class="prose prose-invert prose-lg {{ $align === 'text-center' ? 'text-center' : '' }} mx-auto leading-relaxed font-light text-white"
    >
      {!! $content !!}
    </div>
  @endif
</div>
