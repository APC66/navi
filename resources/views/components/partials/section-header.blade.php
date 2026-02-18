<div class="section-header uppercase {{ $align }} mb-10 tracking-widest">
    <{{$tag}} class="{{ $sizeClass }} font-bold leading-tight font-heading">
        @if($highlight)
            <span class="{{ $highlightColor }} font-light">
                {{ $highlight }}
            </span>
            @if($highlightBreak)
                <br>
            @endif
        @endif

        @if($suffix)
            <span class="text-white">
                {{ $suffix }}
            </span>
        @endif
    </{{$tag}}>
</div>
