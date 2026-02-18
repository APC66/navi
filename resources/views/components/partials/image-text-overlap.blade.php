<div class="relative w-full py-16 md:py-24 bg-primary-900 overflow-hidden">
    <div class="container mx-auto px-4 relative z-10">
        <div class="flex flex-col lg:flex-row items-center gap-12 lg:gap-20 {{ $invert ? 'lg:flex-row-reverse' : '' }}">

            <div class="w-full lg:w-1/4 relative group perspective-1000">
                <div class="relative aspect-[4/5] w-full mx-auto">
                    @if($imageBack)
                        <div class="absolute max-w-[280px] inset-0 transform rotate-8 translate-x-16 translate-y-4 transition-transform duration-700 group-hover:rotate-6 rounded-card overflow-hidden shadow-2xl  z-10">
                            <img src="{{ $imageBack }}" alt="" class="w-full h-full object-cover">
                        </div>
                    @endif

                    @if($imageFront)
                        <div class="absolute max-w-[280px] inset-0 transform -rotate-8 transition-transform duration-700 group-hover:-rotate-6 rounded-card overflow-hidden shadow-2xl z-0 ">
                            <img src="{{ $imageFront }}" alt="{{ strip_tags($title) }}" class="w-full h-full object-cover">
                        </div>
                    @else
                        <div class="absolute inset-0 bg-primary-800 rounded-card flex items-center justify-center ">
                            <svg class="w-24 h-24 text-primary-600 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                    @endif
                </div>
            </div>

            <div class="w-full lg:w-3/4 text-center {{ $invert ? 'lg:text-right' : 'lg:text-left' }}">
                @if($title)
                    <h2 class="text-3xl md:text-xl lg:text-2xl font-bold text-white mb-6 font-heading leading-relaxed">
                        {!! $title !!}
                    </h2>
                @endif

                @if($content)
                    <div class="font-light leading-relaxed max-w-none">
                        {!! $content !!}
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>
