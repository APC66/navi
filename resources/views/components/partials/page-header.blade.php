<div class="relative w-full h-[300px] md:h-[400px] overflow-hidden bg-primary-900">
    @if($image)
        <img src="{{ $image }}" alt="{{ strip_tags($title) }}" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-primary-900/20"></div>
    @else
        <div class="absolute inset-0 bg-gradient-to-r from-primary-900 to-primary-800"></div>
    @endif
</div>

<div class="w-full bg-[#182646]">
    <div class="container mx-auto px-4 relative z-10 pt-8 pb-12">
        <div class="mb-6 flex items-center">
            <a href="{{ home_url('/') }}" class="hover:text-secondary transition-colors">Accueil</a>

            <span class="px-4">/</span>

            <span class="text-secondary truncate max-w-[200px] md:max-w-none">
            {!! $title !!}
            </span>
        </div>

        <h1 class="text-4xl md:text-6xl font-bold uppercase mb-6 text-center font-heading leading-tight">
            @if($highlight)
                <span class="{{ $highlightColor }} font-light">{!! $highlight !!}</span>
            @endif
            {!! $title !!}
        </h1>

        @if($subtitle)
            <div class="text-lg md:text-xl text-gray-600 max-w-4xl font-light leading-relaxed border-l-4 border-secondary pl-6">
                {!! $subtitle !!}
            </div>
        @endif
    </div>
</div>

