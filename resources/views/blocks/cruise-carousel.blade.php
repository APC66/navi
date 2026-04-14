<section class="{{ $block->classes }} group/section relative overflow-hidden py-12 md:py-20">
  @if ($bg_image)
    <div class="absolute inset-0 z-0">
      <img   decoding="sync"
             fetchpriority="high"
             loading="eager"
             src="{{ $bg_image }}"
             alt=""
             class="h-full w-full object-cover" />
    </div>
    <div class="absolute right-0 bottom-20 left-0">
      <img src="@asset('resources/images/waves.svg')" alt="" class="z-10 h-auto w-full" />
    </div>
  @endif

  <div class="relative mx-auto max-w-[1920px] px-4">
    @if (! empty($title_group['highlight']) || ! empty($title_group['suffix']))
      <div data-aos="fade-up" data-aos-duration="600">
        <x-partials.section-header :group="$title_group" />
      </div>
    @endif

    <div class="relative mx-10" data-aos="fade-up" data-aos-duration="700" data-aos-delay="150">
      <button
        class="swiper-button-prev-custom text-primary-1000 bg-secondary absolute top-1/2 -left-4 z-20 flex h-12 w-12 -translate-y-1/2 transform items-center justify-center rounded-full shadow-lg transition-all duration-300 hover:scale-110 hover:bg-white disabled:invisible disabled:opacity-0 md:-left-6 lg:-left-12"
      >
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M15 19l-7-7 7-7"
          ></path>
        </svg>
      </button>

      <button
        class="swiper-button-next-custom text-primary-1000 bg-secondary absolute top-1/2 -right-4 z-20 flex h-12 w-12 -translate-y-1/2 transform items-center justify-center rounded-full shadow-lg transition-all duration-300 hover:scale-110 hover:bg-white disabled:invisible disabled:opacity-0 md:-right-6 lg:-right-12"
      >
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 5l7 7-7 7"
          ></path>
        </svg>
      </button>

      <div class="swiper cruise-swiper !overflow-visible" id="{{ $block_id }}">
        <div class="swiper-wrapper">
          @if ($cruises->isEmpty())
            <div class="swiper-slide w-full">
              <p class="text-center text-white italic">Aucune croisière disponible.</p>
            </div>
          @else
            @foreach ($cruises as $cruise)
              <div class="swiper-slide h-auto">
                <article
                  class="group/card relative h-[470px] w-full overflow-hidden rounded-3xl shadow-lg transition-all duration-300 hover:shadow-2xl"
                >
                  <a href="{{ $cruise->permalink }}" class="absolute inset-0 z-10">
                    <span class="sr-only">Voir la croisière {!! $cruise->title !!}</span>
                  </a>

                  @if ($cruise->thumbnail_url)
                    <img
                      src="{{ $cruise->thumbnail_url }}"
                      alt="{{ $cruise->title }}"
                      class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover/card:scale-110"
                    />
                  @else
                    <div class="bg-primary-800 absolute inset-0 flex items-center justify-center">
                      <svg
                        class="text-primary-600 h-16 w-16 opacity-50"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                        ></path>
                      </svg>
                    </div>
                  @endif

                  <div
                    class="from-primary-900 via-primary-1000/90 absolute inset-0 bg-gradient-to-t to-transparent opacity-90 transition-opacity duration-300 group-hover/card:opacity-0"
                  ></div>

                  <div
                    class="pointer-events-none absolute inset-x-0 bottom-0 z-20 flex h-full flex-col items-center justify-end p-6 text-center transition group-hover/card:opacity-0"
                  >
                    <h3
                      class="font-heading mb-2 text-xl leading-tight font-bold tracking-wide text-white uppercase drop-shadow-md"
                    >
                      {!! $cruise->title !!}
                    </h3>
                    <div class="font-elms flex flex-col items-center">
                      <span class="text-primary-100 mb-1 text-sm font-light opacity-90">
                        au départ de
                      </span>
                      @php
                        $ports = get_the_terms($cruise->ID, 'harbor');
                      @endphp

                      @if ($ports && ! is_wp_error($ports))
                        <span class="text-secondary text-lg font-light">
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
      <div class="swiper-pagination relative !bottom-0 mt-8 flex justify-center md:hidden"></div>
    </div>

    @if ($cta)
      <div
        class="mt-12 text-center"
        data-aos="fade-up"
        data-aos-duration="600"
        data-aos-delay="200"
      >
        <x-partials.button :group="$cta" class="w-full" />
      </div>
    @endif
  </div>
</section>
