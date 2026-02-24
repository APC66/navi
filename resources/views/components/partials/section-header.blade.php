<div class="section-header {{ $align }} mb-10 tracking-widest uppercase">
  <{{ $tag }} class="{{ $sizeClass }} font-heading leading-tight font-bold">
    @if ($highlight)
      <span class="{{ $highlightColor }} font-light">
        {{ $highlight }}
      </span>
      @if ($highlightBreak)
        <br />
      @endif
    @endif

    @if ($suffix)
      <span class="text-white">
        {{ $suffix }}
      </span>
    @endif
  </{{ $tag }}>
</div>
