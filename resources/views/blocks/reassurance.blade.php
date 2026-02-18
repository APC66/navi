<section class="{{ $block->classes }} pt-30 pb-40 relative overflow-hidden bg-primary-1000 group dark-section">

    @if($bg_image)
        <div class="absolute inset-0 z-0">
            <img src="{{ $bg_image }}" alt="" class="w-full h-full object-cover opacity-30 mix-blend-overlay">
            <div class="absolute inset-0 bg-gradient-to-b from-primary-1000 via-transparent to-primary-1000 opacity-80"></div>
        </div>
    @endif

    <div class="container mx-auto px-4 relative z-10">

        <div class="text-center mb-16">
            @if($title_group)
                <x-partials.section-header
                    :group="$title_group"
                    :is-dark="true"
                />
            @endif

            @if($subheader)
                <x-partials.sub-header
                    :subtitle="$subheader['subtitle']"
                    :content="$subheader['content']"
                />
            @endif
        </div>

        @if($items)
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-x-8 gap-y-12">
                @foreach($items as $item)
                    <div class="flex flex-col items-center text-center group/item hover:-translate-y-1 transition-transform duration-300">
                        @if($item['icon'])
                            <div class="mb-4 h-24 w-24 flex items-center justify-center">
                                <img src="{{ $item['icon'] }}" alt="" class="max-h-full max-w-full object-contain filter brightness-0 invert">
                            </div>
                        @endif

                        <h4 class="max-w-[14ch] text-center text-secondary font-bold uppercase tracking-wider text-balance mb-2 font-heading text-sm md:text-base">
                            {{ $item['title'] }}
                        </h4>

                        <p class="text-white text-sm leading-relaxed">
                            {!! nl2br(e($item['text'])) !!}
                        </p>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</section>
