{{-- Chargement explicite du script JS pour ce bloc --}}
@php
    \Roots\bundle('resources/js/blocks/cruise-carousel.js')->enqueue();
@endphp

<section class="{{ $block->classes }} py-12 md:py-20 relative group/section overflow-hidden">
    @if($bg_image)
        <div class="absolute inset-0 z-0">
            <img src="{{ $bg_image }}" alt="" class="w-full h-full object-cover">
        </div>
        <div class="absolute left-0 right-0 bottom-20">
            <img src="@asset('resources/images/waves.svg')" alt="" class="w-full h-auto z-10">
        </div>
    @endif
    <div class="mx-auto px-4 relative max-w-[1920px]">
        @if(!empty($title_group['highlight']) || !empty($title_group['suffix']))
            <x-partials.section-header
                :group="$title_group"
            />
        @endif
        <div class="relative mx-10">
            <button class="swiper-button-prev-custom absolute top-1/2 -translate-y-1/2 -left-4 md:-left-6 lg:-left-12 z-20 w-12 h-12 rounded-full bg-secondary shadow-lg text-primary flex items-center justify-center hover:bg-white transition-all duration-300 disabled:opacity-0 disabled:invisible transform hover:scale-110">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </button>

            <button class="swiper-button-next-custom absolute top-1/2 -translate-y-1/2 -right-4 md:-right-6 lg:-right-12 z-20 w-12 h-12 rounded-full bg-secondary shadow-lg text-primary flex items-center justify-center hover:bg-white transition-all duration-300 disabled:opacity-0 disabled:invisible transform hover:scale-110">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </button>

            <div class="swiper cruise-swiper !overflow-visible" id="{{ $block_id }}">
                <div class="swiper-wrapper">
                    @if($cruises->isEmpty())
                        <div class="swiper-slide w-full">
                            <p class="text-gray-500 italic text-center">Aucune croisière disponible.</p>
                        </div>
                    @else
                        @foreach($cruises as $cruise)
                             <div class="swiper-slide h-auto">
                                <article class="relative w-full h-[470px] rounded-3xl overflow-hidden group/card shadow-lg hover:shadow-2xl transition-all duration-300">
                                    <a href="{{ $cruise->permalink }}" class="absolute inset-0 z-10">
                                        <span class="sr-only">Voir la croisière {{ $cruise->title }}</span>
                                    </a>
                                    @if($cruise->thumbnail_url)
                                        <img src="{{ $cruise->thumbnail_url }}"
                                             alt="{{ $cruise->title }}"
                                             class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover/card:scale-110">
                                    @else
                                        <div class="absolute inset-0 bg-primary-800 flex items-center justify-center">
                                            <svg class="w-16 h-16 text-primary-600 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        </div>
                                    @endif

                                    <div class="absolute inset-0 bg-gradient-to-t from-primary-800 via-primary-800/70 to-transparent opacity-90 transition-opacity duration-300 group-hover/card:opacity-0"></div>

                                    <div class="absolute inset-x-0 bottom-0 p-6 flex flex-col items-center text-center justify-end h-full pointer-events-none z-20">
                                        <h3 class="text-xl font-bold text-white uppercase tracking-wide leading-tight mb-2 font-heading drop-shadow-md">
                                            {{ $cruise->title }}
                                        </h3>
                                        <div class="flex flex-col items-center font-elms">
                                            <span class="text-primary-100 font-light text-sm mb-1 opacity-90">au départ de</span>
                                            @php $ports = get_the_terms($cruise->ID, 'harbor'); @endphp
                                            @if($ports && !is_wp_error($ports))
                                                <span class="text-secondary font-light text-lg">
                                                    {{ $ports[0]->name }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
            <div class="swiper-pagination md:hidden mt-8 relative flex justify-center !bottom-0"></div>
        </div>
        @if($cta)
            <div class="mt-12 text-center">
                <x-partials.button :group="$cta" class="w-full" />
            </div>
        @endif
    </div>
</section>
